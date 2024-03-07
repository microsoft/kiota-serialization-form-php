<?php

namespace Microsoft\Kiota\Serialization\tests;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Serialization\Form\FormParseNode;
use Microsoft\Kiota\Serialization\Form\FormParseNodeFactory;
use Microsoft\Kiota\Serialization\Tests\Samples\Person;
use PHPUnit\Framework\TestCase;

class FormParseNodeTest extends TestCase
{
    public function testCanParseInput(): void
    {
        $parseNode = (new FormParseNodeFactory())
        ->getRootParseNode((new FormParseNodeFactory())->getValidContentType(), Utils::streamFor('name=Silas+Kenneth&age=23&password=123212&numbers=1&numbers=2&numbers=3'));
    }
}