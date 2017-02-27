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

final class Input
{
    protected $string;
    protected $offset = 0;
    protected $bytes;

    public function __construct(string $string, $offset = 0)
    {
        $this->string = $string;
        $this->offset = $offset;
        
        $this->bytes = strlen($this->string) - $this->offset;
    }

    public function bytes(int $limit = 0) : string
    {
        if ($number < 0) {
            throw new InvalidArgumentException('You must request at least 1 byte.');
        }

        if ($limit !== 0) {
            return substr($this->string, $this->offset, $limit);
        }

        return substr($this->string, $this->offset);
    }

    public function chars(int $limit = 0) : string
    {
        if ($number < 0) {
            throw new InvalidArgumentException('You must request at least 1 char.');
        }

        if ($limit !== 0) {
            return mb_substr($this->string, $this->offset, $limit);
        }

        return mb_substr($this->string, $this->offset);
    }

    public function advanceBytes(int $number) : self
    {
        if ($number < 1) {
            throw new InvalidArgumentException('You must advance at least 1 byte.');
        }

        return new self($this->string, $this->offset + $number);
    }

    public function advanceChars(int $number) : self
    {
        if ($number < 1) {
            throw new InvalidArgumentException('You must advance at least 1 char.');
        }

        $bytes = strlen($this->chars(1));

        return $this->advanceBytes($bytes);
    }
    
    public function length() : int
    {
        return $this->bytes;
    }
}
