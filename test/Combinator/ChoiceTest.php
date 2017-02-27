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

namespace Bastelstube\ParserCombinator\Test\Combinator;

use PHPUnit_Framework_TestCase as TestCase;
use Bastelstube\ParserCombinator;

class ChoiceTest extends \PHPUnit_Framework_TestCase
{
    public function testFirstChoice()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $b = new ParserCombinator\Parser\Byte('b');
        $parser = new ParserCombinator\Combinator\Choice($a, $b);
        $input = new ParserCombinator\Input('a');

        $parser($input)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertSame('a', $result->getResult());
            $this->assertSame('', $result->getRest()->bytes());
        });
    }

    public function testSecondChoice()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $b = new ParserCombinator\Parser\Byte('b');
        $parser = new ParserCombinator\Combinator\Choice($a, $b);
        $input = new ParserCombinator\Input('b');

        $parser($input)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertSame('b', $result->getResult());
            $this->assertSame('', $result->getRest()->bytes());
        });
    }

    public function testDoesNotParse()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $b = new ParserCombinator\Parser\Byte('b');
        $parser = new ParserCombinator\Combinator\Choice($a, $b);
        $input = new ParserCombinator\Input('c');

        $parser($input)->either(function ($message) {
            $this->assertTrue(true);
        }, function ($result) {
            $this->fail($result->getResult());
        });
    }
}
