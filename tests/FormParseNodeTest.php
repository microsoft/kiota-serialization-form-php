<?php

namespace Microsoft\Kiota\Serialization\Tests;

use DateTime;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Microsoft\Kiota\Serialization\Form\FormParseNode;
use Microsoft\Kiota\Serialization\Form\FormParseNodeFactory;
use Microsoft\Kiota\Serialization\Tests\Samples\BioContentType;
use Microsoft\Kiota\Serialization\Tests\Samples\MaritalStatus;
use Microsoft\Kiota\Serialization\Tests\Samples\Person;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class FormParseNodeTest extends TestCase
{
    private ParseNode $parseNode;
    private StreamInterface $stream;

    protected function setUp(): void {
        $this->stream = Utils::streamFor('@odata.type=Missing&name=Silas+Kenneth&age=98&height=123.122&maritalStatus=complicated,single&type=html&type=json&type=plain&seen=true');
    }

    public function testGetIntegerValue(): void {
        $this->parseNode = new FormParseNode(1243.78);
        $expected = $this->parseNode->getIntegerValue();
        $this->assertEquals(null, $expected);
        $this->parseNode = new FormParseNode(1243);
        $expected = $this->parseNode->getIntegerValue();
        $this->assertEquals(1243, $expected);
    }

    public function testGetCollectionOfObjectValues(): void {
        $this->expectException(\RuntimeException::class);
        $str = Utils::streamFor('a=1&b=2&c=3');
        $this->parseNode = (new FormParseNodeFactory())->getRootParseNode('application/x-www-form-urlencoded', $str);

        /** @var array<Person> $expected */
        $expected = $this->parseNode->getCollectionOfObjectValues(array(Person::class, 'createFromDiscriminatorValue'));
    }

    /**
     * @throws \Exception
     */
    public function testGetObjectValue(): void {
        $this->parseNode = (new FormParseNodeFactory())->getRootParseNode('application/x-www-form-urlencoded', $this->stream);
        /** @var Person $expected */
        $expected = $this->parseNode->getObjectValue(array(Person::class, 'createFromDiscriminatorValue'));
        $this->assertInstanceOf(Person::class, $expected);
        $this->assertEquals('Silas Kenneth', $expected->getName());
        $this->assertInstanceOf(Enum::class, $expected->getMaritalStatus());
        $this->assertEquals(98, $expected->getAge());
        $this->assertEquals(123.122, $expected->getHeight());
        $this->assertEquals(true, $expected->getSeen());
        $this->assertEquals([new BioContentType('html'), new BioContentType('json'), new BioContentType('plain')], $expected->getBioContentType());
    }

    public function testGetFloatValue(): void {
        $this->parseNode = new FormParseNode(1243.12);
        $expected = $this->parseNode->getFloatValue();
        $this->assertEquals(1243.12, $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValues(): void {
        $this->parseNode = new FormParseNode([1921, 1212, 123, 45, 56]);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues();
        $this->assertEquals([1921, 1212,123,45,56], $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetAnyValue(): void {
        $this->parseNode = new FormParseNode(12);
        $expectedInteger = $this->parseNode->getAnyValue('int');
        $this->parseNode = new FormParseNode(12.009);
        $expectedFloat = $this->parseNode->getAnyValue('float');
        $this->parseNode = new FormParseNode((new DateTime('2022-01-27'))->format(DateTimeInterface::ATOM));
        $expectedDate = $this->parseNode->getAnyValue(Date::class);
        $this->parseNode = new FormParseNode("Silas Kenneth");
        $expectedString = $this->parseNode->getAnyValue('string');
        $this->assertEquals(12, $expectedInteger);
        $this->assertEquals(12.009, $expectedFloat);
        $this->assertEquals('2022-01-27', (string)$expectedDate);
        $this->assertEquals('Silas Kenneth', $expectedString);
    }

    public function testGetEnumValue(): void {
        $this->parseNode = new FormParseNode('married');
        /** @var Enum $expected */
        $expected = $this->parseNode->getEnumValue(MaritalStatus::class);
        $this->assertInstanceOf(Enum::class, $expected);
        $this->assertEquals('married', $expected->value());
        $this->parseNode = new FormParseNode('married,single');
        /** @var Enum $expected */
        $expected = $this->parseNode->getEnumValue(MaritalStatus::class);
        $this->assertInstanceOf(Enum::class, $expected);
        $this->assertEquals('married,single', $expected->value());
    }

    /**
     * @throws \Exception
     */
    public function testGetTimeOnlyValue(): void{
        $this->parseNode = new FormParseNode((new DateTime('2022-01-27T12:59:45.596117'))->format(DATE_ATOM));
        $expected = $this->parseNode->getTimeValue();
        $this->assertInstanceOf(Time::class, $expected);
        $this->assertEquals('12:59:45', (string)$expected);
    }

    /**
     * @throws \Exception
     */
    public function testGetDateOnlyValue(): void{
        $this->parseNode = new FormParseNode((new DateTime('2022-01-27T12:59:45.596117'))->format(DATE_ATOM));
        $expected = $this->parseNode->getDateValue();
        $this->assertInstanceOf(Date::class, $expected);
        $this->assertEquals('2022-01-27', (string)$expected);
    }

    public function testGetBooleanValue(): void {
        $this->parseNode = new FormParseNode('true');
        $expected = $this->parseNode->getBooleanValue();
        $this->assertEquals('bool', get_debug_type($expected));
        $this->assertEquals(true, $expected);
    }

    /**
     * @throws \Exception
     */
    public function testGetDateTimeValue(): void {
        $value = (new DateTime('2022-01-27T12:59:45.596117'))->format(DateTimeInterface::RFC3339);
        $this->parseNode = new FormParseNode($value);
        $expected = $this->parseNode->getDateTimeValue();
        $this->assertInstanceOf(DateTime::class, $expected);
        $this->assertEquals($value, $expected->format(DateTimeInterface::RFC3339));
    }

    /**
     */
    public function testGetStringValue(): void{
        $this->parseNode = new FormParseNode('Silas Kenneth was here');
        $expected = $this->parseNode->getStringValue();
        $this->assertEquals('Silas Kenneth was here', $expected);
    }

    public function testCallbacksAreCalled(): void {
        $this->parseNode = (new FormParseNodeFactory())->getRootParseNode('application/x-www-form-urlencoded', $this->stream);
        $assigned = false;
        $onAfterAssignValues = function ($result) use (&$assigned) {
            $assigned = true;
        };
        $this->parseNode->setOnAfterAssignFieldValues($onAfterAssignValues);
        $person = $this->parseNode->getObjectValue([Person::class, 'createFromDiscriminatorValue']);
        $this->assertTrue($assigned);
    }

    public function testGetBinaryContent(): void {
        $this->parseNode = new FormParseNode(100);
        $this->assertEquals("100", $this->parseNode->getBinaryContent());
    }

    public function testGetBinaryContentFromArray(): void {
        $this->parseNode = new FormParseNode($this->stream->getContents());
        $this->stream->rewind();
        $this->assertEquals($this->stream->getContents(), $this->parseNode->getBinaryContent());
    }
}
