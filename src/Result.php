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

use Widmogrod\FantasyLand\Functor;

/**
 * Represents the Result of a successful parse.
 */
class Result implements Functor
{
    /**
     * The result of the parse.
     * @var mixed
     */
    protected $result;
    
    /**
     * The remaining input.
     * @var Input
     */
    protected $rest;

    public function __construct($result, Input $rest)
    {
        $this->result = $result;
        $this->rest = $rest;
    }

    /**
     * Returns the parse result.
     *
     * @return  mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Returns the remaining input.
     *
     * @return  Input
     */
    public function getRest() : Input
    {
        return $this->rest;
    }

    /**
     * @inheritDoc
     */
    public function map(callable $function)
    {
        return new static($function($this->getResult()), $this->getRest());
    }
}
