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

namespace Bastelstube\ParserCombinator;

use Widmogrod\FantasyLand\Applicative;
use Widmogrod\FantasyLand\Apply;
use Widmogrod\FantasyLand\Functor;
use Widmogrod\FantasyLand\Monad;
use Widmogrod\Monad\Either;

/**
 * Represents a single Parser.
 */
abstract class Parser implements
    Functor,
    Applicative,
    Monad
{
    /**
     * Runs this parser on the given Input.
     *
     * @param   Input   $input
     * @return  Either\Either<Result>
     */
    abstract public function run(Input $input) : Either\Either;
    
    public static function parse(Parser $parser, Input $input) : Either\Either
    {
        return $parser->run($input)->map(function ($parseResult) {
            return $parseResult->getResult();
        });
    }

    /**
     * Alias for Parser::run().
     *
     * @see Parser::run()
     */
    public function __invoke(Input $input) : Either\Either
    {
        return $this->run($input);
    }

    /**
     * @inheritDoc
     */
    public function map(callable $function)
    {
        return Parser::of($function)->ap($this);
    }

    /**
     * Returns a Parser that never consumes input and always returns $value.
     *
     * @param   mixed   $value
     * @return  Parser
     */
    public static function of($value)
    {
        return new class($value) extends Parser {
            private $value;

            public function __construct($value) {
                $this->value = $value;
            }

            public function run(Input $input) : Either\Either
            {
                return new Either\Right(new ParseResult(new Result($this->value, $input), false));
            }
        };
    }

    /**
     * @inheritDoc
     */
    public function ap(Apply $b)
    {
        return $this->bind(function ($f) use ($b) {
            return $b->bind(function ($x) use ($f) {
                return Parser::of($f($x));
            });
        });
    }

    /**
     * Returns a combined parser that returns the result of the left parser ($this).
     *
     * @param   Apply   $b  The right parser.
     * @return  Parser
     * @see Parser::ap()
     */
    public function apL(Apply $b) : Parser
    {
        return \Widmogrod\Functional\liftA2(function ($a) {
            return $a;
        }, $this, $b);
    }

    /**
     * Returns a combined parser that returns the result of the right parser ($b).
     *
     * @param   Apply   $b  The right parser.
     * @return  Parser
     * @see Parser::ap()
     */
    public function apR(Apply $b)
    {
        return \Widmogrod\Functional\liftA2(function ($_, $b) {
            return $b;
        }, $this, $b);
    }

    /**
     * Runs the left parser ($this), passes the result to $function
     * and runs the returned parser on the rest of the input.
     *
     * @inheritDoc
     */
    public function bind(callable $function)
    {
        return new class($this, $function) extends Parser {
            private $parent;
            private $function;

            public function __construct(Parser $parent, callable $function)
            {
                $this->parent = $parent;
                $this->function = $function;
            }

            public function run(Input $input) : Either\Either
            {
                return $this->parent->run($input)
                ->bind(function (ParseResult $result) : Either\Either {
                    $consumed = $result->hasConsumed();
                    $result = $result->getResult();

                    $function = $this->function;
                    $resultB = $function($result->getResult())->run($result->getRest());

                    return \Widmogrod\Monad\Either\doubleMap(function (ParseResult $left) use ($consumed) {
                        return new ParseResult($left->getResult(), $left->hasConsumed() || $consumed);
                    }, \Widmogrod\Functional\identity, $resultB);
                });
            }

            public function __toString()
            {
                return $this->parent.' >>=';
            }
        };
    }

    public function __toString()
    {
        return get_called_class().'(???)';
    }
}
