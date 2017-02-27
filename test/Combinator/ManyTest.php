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

use Bastelstube\ParserCombinator;

class ManyTest extends \PHPUnit\Framework\TestCase
{
    public function testSingle()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $parser = (new ParserCombinator\Combinator\Many($a))->map(\Widmogrod\Functional\curry('implode', ['']));
        $input = new ParserCombinator\Input('a');

        $parser($input)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertSame('a', $result->getResult());
            $this->assertSame('', $result->getRest()->bytes());
        });
    }

    public function testMultiple()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $parser = (new ParserCombinator\Combinator\Many($a))->map(\Widmogrod\Functional\curry('implode', ['']));
        $input = new ParserCombinator\Input('aaa');

        $parser($input)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertSame('aaa', $result->getResult());
            $this->assertSame('', $result->getRest()->bytes());
        });
    }

    public function testMultipleRest()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $parser = (new ParserCombinator\Combinator\Many($a))->map(\Widmogrod\Functional\curry('implode', ['']));
        $input = new ParserCombinator\Input('aaab');

        $parser($input)->either(function ($message) {
            $this->fail($message);
        }, function ($result) {
            $this->assertSame('aaa', $result->getResult());
            $this->assertSame('b', $result->getRest()->bytes());
        });
    }

    public function testMinimum()
    {
        $a = new ParserCombinator\Parser\Byte('a');
        $parser = (new ParserCombinator\Combinator\Many($a, 1))->map(\Widmogrod\Functional\curry('implode', ['']));
        $input = new ParserCombinator\Input('');

        $parser($input)->either(function ($message) {
            $this->assertTrue(true);
        }, function ($result) {
            $this->fail($result->getResult());
        });
    }
}
