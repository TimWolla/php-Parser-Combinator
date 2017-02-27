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

namespace Bastelstube\ParserCombinator\Parser;

use Bastelstube\ParserCombinator\Input;
use Bastelstube\ParserCombinator\Parser;
use Bastelstube\ParserCombinator\Result;
use Widmogrod\Monad\Either;

/**
 * Matches the given string.
 */
class StringP extends Parser
{
    protected $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function run(Input $input) : Either\Either
    {
        if ($input->length() < ($length = strlen($this->string))) return new Either\Left('End of input reached while trying to parse '.$this.'.');
        if ($input->bytes($length) !== $this->string) return new Either\Left('Unable to match '.$this.', found '.$input->chars(3).'.');

        return new Either\Right(new Result($input->bytes($length), $input->advanceBytes($length)));
    }

    public function __toString()
    {
        return 'String('.$this->string.')';
    }
}
