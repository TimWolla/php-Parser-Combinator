<?php declare(strict_types=1);
/*
The MIT License (MIT)

Copyright (c) 2017 Tim Düsterhus

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Bastelstube\ParserCombinator\Test\Integration;

use Bastelstube\ParserCombinator;

class JsonTest extends \PHPUnit\Framework\TestCase
{
    protected static $json;

    public static function setUpBeforeClass()
    {
        $null = (new ParserCombinator\Parser\StringP('null'))->map(function () {
            return null;
        });

        $true = (new ParserCombinator\Parser\StringP('true'))->map(function () {
            return true;
        });
        $false = (new ParserCombinator\Parser\StringP('false'))->map(function () {
            return false;
        });
        $bool = new ParserCombinator\Combinator\Choice($true, $false);

        $digit1 = new ParserCombinator\Parser\SatisfyChar(function ($char) : bool {
            return preg_match('/^[1-9]$/u', $char) > 0;
        });
        $zero = new ParserCombinator\Parser\Char('0');
        $digit = new ParserCombinator\Combinator\Choice($digit1, $zero);
        $digits = (new ParserCombinator\Combinator\Many($digit, 1))->map(function ($results) {
            return implode('', $results);
        });
        $frac = (new ParserCombinator\Parser\Char('.'))->map(\Widmogrod\Functional\curry(function ($a, $b) {
            return $a.$b;
        }))->ap($digits);
        $e = new ParserCombinator\Combinator\Choice(
            new ParserCombinator\Parser\StringP('e'),
            new ParserCombinator\Parser\StringP('e+'),
            new ParserCombinator\Parser\StringP('e-'),
            new ParserCombinator\Parser\StringP('E'),
            new ParserCombinator\Parser\StringP('E+'),
            new ParserCombinator\Parser\StringP('E-')
        );
        $exp = $e->map(\Widmogrod\Functional\curry(function ($a, $b) {
            return $a.$b;
        }))->ap($digits);
        $optMinus = new ParserCombinator\Combinator\Choice(
            new ParserCombinator\Parser\Char('-'),
            ParserCombinator\Parser::of('')
        );
        $int = new ParserCombinator\Combinator\Choice(
            $optMinus->map(\Widmogrod\Functional\curry(function ($a, $b, $c) {
                return $a.$b.$c;
            }))->ap($digit1)->ap($digits),
            $optMinus->map(\Widmogrod\Functional\curry(function ($a, $b) {
                return $a.$b;
            }))->ap($digit1)
        );
        $number = new ParserCombinator\Combinator\Choice(
            $int->map(\Widmogrod\Functional\curry(function ($a, $b, $c) {
                return floatval($a.$b.$c);
            }))->ap($frac)->ap($exp),
            $int->map(\Widmogrod\Functional\curry(function ($a, $b) {
                return floatval($a.$b);
            }))->ap($frac),
            $int->map(\Widmogrod\Functional\curry(function ($a, $b) {
                return floatval($a.$b);
            }))->ap($exp),
            $int->map('intval')
        );

        $simpleChar = new ParserCombinator\Parser\SatisfyChar(function ($char) {
            return $char !== '"' && $char !== '\\';
        });
        $escaped = (new ParserCombinator\Parser\Char('\\'))->apR(new ParserCombinator\Combinator\Choice(
            new ParserCombinator\Parser\Char('"'),
            new ParserCombinator\Parser\Char('\\')
            // TODO
        ));
        $char = new ParserCombinator\Combinator\Choice(
            $simpleChar,
            $escaped
        );
        $chars = (new ParserCombinator\Combinator\Many($char, 1))->map(function ($results) {
            return implode('', $results);
        });

        $string = (new ParserCombinator\Parser\Char('"'))->apR(new ParserCombinator\Combinator\Choice(
            $chars,
            ParserCombinator\Parser::of('')
        ))->apL(new ParserCombinator\Parser\Char('"'));

        $array = ParserCombinator\Parser\Failure::get();
        $object = ParserCombinator\Parser\Failure::get();
        $value = new ParserCombinator\Combinator\Choice(
            $string,
            $number,
            $bool,
            $null,
            new ParserCombinator\RefParser($array),
            new ParserCombinator\RefParser($object)
        );

        $array = (new ParserCombinator\Parser\Char('['))->apR(
            new ParserCombinator\Combinator\Choice(
                $value->map(function ($a) {
                    return function ($b) use ($a) {
                        return array_merge([$a], $b);
                    };
                })->ap(new ParserCombinator\Combinator\Many(
                    (new ParserCombinator\Parser\Char(','))->apR($value)
                )),
                ParserCombinator\Parser::of([])
            )
        )->apL(new ParserCombinator\Parser\Char(']'));
        
        $pair = $string->map(\Widmogrod\Functional\curry(function ($a, $b) {
            return [$a => $b];
        }))->apL(new ParserCombinator\Parser\Char(':'))->ap($value);
        
        $object = (new ParserCombinator\Parser\Char('{'))->apR(
            new ParserCombinator\Combinator\Choice(
                $pair->map(\Widmogrod\Functional\curry(function ($a, $b) {
                    return array_merge($a, ...$b);
                }))->ap(new ParserCombinator\Combinator\Many(
                    (new ParserCombinator\Parser\Char(','))->apR($pair)
                )),
                ParserCombinator\Parser::of([])
            )
        )->apL(new ParserCombinator\Parser\Char('}'));

        self::$json = new ParserCombinator\Combinator\Choice(
            $array,
            $object
        );
    }
    
    /**
     * @dataProvider jsonProvider
     */
    public function testJson($json)
    {
        $parser = self::$json;

        $parser($json)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertTrue(true);
        });
    }

    public function jsonProvider()
    {
        return array_map(function ($json) {
            return [new ParserCombinator\Input(json_encode($json))];
        }, [
            [],
            [1],
            [1,2],
            [1,2,3],
            [""],
            ["a"],
            ["\""],
            ["\\"],
            ["\\\""],
            ["a" => "b"],
            ["a" => ""],
            [[]],
            ["a" => ["a"]],
            ["a" => ["a" => "a"]],
        ]);
    }
}
