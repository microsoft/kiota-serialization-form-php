<?php

namespace Microsoft\Kiota\Serialization\tests;

use Microsoft\Kiota\Serialization\Form\FormParseNode;
use PHPUnit\Framework\TestCase;

class FormParseNodeTest extends TestCase
{
    public function testCanParseInput(): void
    {
        $parseNode = new FormParseNode("name=Silas+Kenneth&age=23&password=123212&numbers=1&numbers=2&numbers=3");
    }
}