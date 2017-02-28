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

use Widmogrod\Monad\Either;

class Label extends Parser
{
    /**
     * Inner parser.
     * @var Parser
     */
    protected $parser;

    protected $label;

    public function __construct(Parser $parser, string $label)
    {
        $this->parser = $parser;
        $this->label = $label;
    }

    /**
     * @inheritDoc
     */
    public function run(Input $input) : Either\Either
    {
        return \Widmogrod\Monad\Either\doubleMap(function (ParseResult $left) {
            return new ParseResult($this->label, $left->hasConsumed());
        }, \Widmogrod\Functional\identity, $this->parser->run($input));
    }
}
