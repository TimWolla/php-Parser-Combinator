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

namespace Bastelstube\ParserCombinator\Combinator;

use Bastelstube\ParserCombinator\Input;
use Bastelstube\ParserCombinator\ParseResult;
use Bastelstube\ParserCombinator\Parser;
use Bastelstube\ParserCombinator\Result;
use Widmogrod\Monad\Either;

/**
 * Greedily matches the given Parser multiple times. Fails if less than $min matches were achieved.
 */
class Many extends Parser
{
    protected $parser;
    protected $min;

    public function __construct(Parser $parser, $min = 0)
    {
        $this->parser = $parser;
        $this->min = $min;
    }

    /**
     * @inheritDoc
     */
    public function run(Input $input) : Either\Either
    {
        $results = [];

        do {
            $result = $this->parser->run($input);

            $continue = $result->either(function ($left) {
                return false;
            }, function ($right) use (&$results, &$input) {
                $result = $right->getResult();
                $results[] = $result->getResult();
                $input = $result->getRest();
                
                return true;
            });
        }
        while ($continue);

        if (count($results) >= $this->min) {
            return new Either\Right(new ParseResult(new Result($results, $input), count($results) > 0));
        }

        return new Either\Left(new ParseResult($result->extract()->getResult(), count($results) > 0 || $result->extract()->hasConsumed()));
    }
}
