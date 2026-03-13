<?php

namespace Microsoft\Kiota\Serialization\Form\Tests;

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
use Microsoft\Kiota\Serialization\Form\Tests\Samples\BioContentType;
use Microsoft\Kiota\Serialization\Form\Tests\Samples\MaritalStatus;
use Microsoft\Kiota\Serialization\Form\Tests\Samples\Person;
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
    public function testGetCollectionOfPrimitiveValuesStrings(): void {
        $this->parseNode = new FormParseNode(['hello', 'world', 'foo']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues();
        $this->assertEquals(['hello', 'world', 'foo'], $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesBooleans(): void {
        $this->parseNode = new FormParseNode(['true', 'true', 'false']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues('bool');
        $this->assertEquals([true, true, null], $expected);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesFloats(): void {
        $this->parseNode = new FormParseNode(['1.1', '2.2', '3.3']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues('float');
        $this->assertEqualsWithDelta([1.1, 2.2, 3.3], $expected, 0.0001);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesWithDateType(): void {
        $this->parseNode = new FormParseNode(['2022-01-27', '2023-06-15']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues(Date::class);
        $this->assertCount(2, $expected);
        $this->assertInstanceOf(Date::class, $expected[0]);
        $this->assertInstanceOf(Date::class, $expected[1]);
        $this->assertEquals('2022-01-27', (string)$expected[0]);
        $this->assertEquals('2023-06-15', (string)$expected[1]);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesWithTimeType(): void {
        $this->parseNode = new FormParseNode(['2022-01-27T12:30:00+00:00', '2022-01-27T08:15:45+00:00']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues(Time::class);
        $this->assertCount(2, $expected);
        $this->assertInstanceOf(Time::class, $expected[0]);
        $this->assertInstanceOf(Time::class, $expected[1]);
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesWithEnumType(): void {
        $this->parseNode = new FormParseNode(['married', 'single']);
        $expected = $this->parseNode->getCollectionOfPrimitiveValues(MaritalStatus::class);
        $this->assertCount(2, $expected);
        $this->assertInstanceOf(MaritalStatus::class, $expected[0]);
        $this->assertEquals('married', $expected[0]->value());
        $this->assertEquals('single', $expected[1]->value());
    }

    /**
     * @throws Exception
     */
    public function testGetCollectionOfPrimitiveValuesReturnsNullForNonArray(): void {
        $this->parseNode = new FormParseNode('not-an-array');
        $expected = $this->parseNode->getCollectionOfPrimitiveValues();
        $this->assertNull($expected);
    }

    public function testGetChildNodeReturnsNullForNonArray(): void {
        $this->parseNode = new FormParseNode('not-an-array');
        $child = $this->parseNode->getChildNode('key');
        $this->assertNull($child);
    }

    public function testGetChildNodeReturnsNullForMissingKey(): void {
        $this->parseNode = new FormParseNode(['name' => 'Alice']);
        $child = $this->parseNode->getChildNode('missing');
        $this->assertNull($child);
    }

    public function testGetChildNodeReturnsValueForExistingKey(): void {
        $this->parseNode = new FormParseNode(['name' => 'Alice', 'age' => '30']);
        $child = $this->parseNode->getChildNode('name');
        $this->assertNotNull($child);
        $this->assertEquals('Alice', $child->getStringValue());
    }

    public function testGetChildNodeWithUrlEncodedKey(): void {
        $this->parseNode = new FormParseNode(['first%20name' => 'Alice']);
        $child = $this->parseNode->getChildNode('first%20name');
        $this->assertNotNull($child);
        $this->assertEquals('Alice', $child->getStringValue());
    }

    public function testGetObjectValueThrowsForInvalidType(): void {
        $this->parseNode = new FormParseNode(['name' => 'Alice']);
        $this->expectException(\InvalidArgumentException::class);
        $this->parseNode->getObjectValue([\stdClass::class, 'create']);
    }

    public function testGetObjectValueThrowsForUndefinedMethod(): void {
        $this->parseNode = new FormParseNode(['name' => 'Alice']);
        $this->expectException(\InvalidArgumentException::class);
        $this->parseNode->getObjectValue([Person::class, 'nonExistentMethod']);
    }

    public function testGetObjectValueReturnsNullForNullNode(): void {
        $this->parseNode = new FormParseNode(null);
        $result = $this->parseNode->getObjectValue([Person::class, 'createFromDiscriminatorValue']);
        $this->assertNull($result);
    }

    public function testGetObjectValueReturnsNullForNullStringNode(): void {
        $this->parseNode = new FormParseNode('null');
        $result = $this->parseNode->getObjectValue([Person::class, 'createFromDiscriminatorValue']);
        $this->assertNull($result);
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
