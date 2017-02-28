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
use function Bastelstube\ParserCombinator\{char, choice, many, satisfyChar, stringP, tryP};
use function \Widmogrod\Functional\{curry, curryN};

class JsonTest extends \PHPUnit\Framework\TestCase
{
    protected static $json;

    public static function setUpBeforeClass()
    {
        $null = stringP('null')->map(function () {
            return null;
        });

        $true = stringP('true')->map(function () {
            return true;
        });
        $false = stringP('false')->map(function () {
            return false;
        });
        $bool = choice(
            $true,
            $false
        );

        $digit1 = satisfyChar(function ($char) : bool {
            return preg_match('/^[1-9]$/u', $char) > 0;
        });
        $zero = char('0');
        $digit = choice(
            tryP($digit1),
            $zero
        );
        $digits = many($digit, 1)->map(function ($results) {
            return implode('', $results);
        });
        $frac = char('.')->map(curry(function ($a, $b) {
            return $a.$b;
        }))->ap($digits);
        $e = choice(
            tryP(stringP('e')),
            tryP(stringP('e+')),
            tryP(stringP('e-')),
            tryP(stringP('E')),
            tryP(stringP('E+')),
            stringP('E-')
        );
        $exp = $e->map(curry(function ($a, $b) {
            return $a.$b;
        }))->ap($digits);
        $optMinus = choice(
            tryP(char('-')),
            ParserCombinator\Parser::of('')
        );
        $int = choice(
            tryP($optMinus->map(curry(function ($a, $b, $c) {
                return $a.$b.$c;
            }))->ap($digit1)->ap($digits)),
            $optMinus->map(curry(function ($a, $b) {
                return $a.$b;
            }))->ap($digit)
        );
        $number = choice(
            tryP($int->map(curry(function ($a, $b, $c) {
                return floatval($a.$b.$c);
            }))->ap($frac)->ap($exp)),
            tryP($int->map(curry(function ($a, $b) {
                return floatval($a.$b);
            }))->ap($frac)),
            tryP($int->map(curry(function ($a, $b) {
                return floatval($a.$b);
            }))->ap($exp)),
            $int->map('intval')
        );

        $simpleChar = satisfyChar(function ($char) {
            return $char !== '"' && $char !== '\\';
        });
        $hexDigit = satisfyChar(function ($char) {
            return preg_match('/^[0-9a-f]$/ui', $char) > 0;
        });
        $escaped = tryP(char('\\'))->apR(choice(
            tryP(char('"')),
            tryP(char('\\')),
            tryP(char('/')),
            tryP(char('r'))->map(function () { return "\r";   }),
            tryP(char('n'))->map(function () { return "\n";   }),
            tryP(char('b'))->map(function () { return "\x08"; }),
            tryP(char('t'))->map(function () { return "\t";   }),
            tryP(char('f'))->map(function () { return "\x0C"; }),
            char('u')->map(curryN(5, function ($_, ...$digits) {
                return html_entity_decode("&#x".implode('', $digits).";", ENT_NOQUOTES, 'UTF-8');
            }))->ap($hexDigit)->ap($hexDigit)->ap($hexDigit)->ap($hexDigit)
        ));
        $char = choice(
            $escaped,
            $simpleChar
        );
        $chars = many($char, 1)->map(function ($results) {
            return implode('', $results);
        });

        $string = tryP(char('"'))->apR(choice(
            tryP($chars),
            ParserCombinator\Parser::of('')
        ))->apL(char('"'));

        $array = ParserCombinator\Parser\Failure::get();
        $object = ParserCombinator\Parser\Failure::get();
        $value = choice(
            $string,
            tryP($number),
            $bool,
            $null,
            new ParserCombinator\RefParser($array),
            new ParserCombinator\RefParser($object)
        );

        $array = tryP(char('['))->apR(
            choice(
                $value->map(function ($a) {
                    return function ($b) use ($a) {
                        return array_merge([$a], $b);
                    };
                })->ap(many(
                    char(',')->apR($value)
                )),
                ParserCombinator\Parser::of([])
            )
        )->apL(char(']'));

        $pair = $string->map(curry(function ($a, $b) {
            return [$a => $b];
        }))->apL(char(':'))->ap($value);

        $object = tryP(char('{'))->apR(
            choice(
                $pair->map(curry(function ($a, $b) {
                    return array_merge($a, ...$b);
                }))->ap(many(
                    char(',')->apR($pair)
                )),
                ParserCombinator\Parser::of([])
            )
        )->apL(char('}'));

        self::$json = choice(
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
            $this->fail((string) $message);
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
            [true],
            [false],
            [null],
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
            ["\0"],
            ["a" => 0],
        ]);
    }
}
