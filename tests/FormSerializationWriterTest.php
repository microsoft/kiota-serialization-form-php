<?php

namespace Microsoft\Kiota\Serialization\Tests;

use DateInterval;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Microsoft\Kiota\Serialization\Form\FormSerializationWriter;
use Microsoft\Kiota\Serialization\Tests\Samples\Address;
use Microsoft\Kiota\Serialization\Tests\Samples\MaritalStatus;
use Microsoft\Kiota\Serialization\Tests\Samples\Person;
use PHPUnit\Framework\TestCase;

class FormSerializationWriterTest extends TestCase
{
    private SerializationWriter $serializationWriter;

    /**
     */
    public function testWriteAdditionalData(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAdditionalData(['@odata.type' => 'Type']);
        $expected = '%40odata.type=Type';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteLongValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeIntegerValue("timestamp", 28192199291929192);
        $expected = 'timestamp=28192199291929192';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteDateOnlyValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $date = Date::createFrom(2012, 12, 3);
        $this->serializationWriter->writeAnyValue("date", $date);
        $expected = 'date=2012-12-03';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteUUIDValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeStringValue("id", '9de7828f-4975-49c7-8734-805487dfb8a2');
        $expected = 'id=9de7828f-4975-49c7-8734-805487dfb8a2';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteCollectionOfNonParsableObjectValues(): void{

        $this->expectException(\RuntimeException::class);
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeCollectionOfPrimitiveValues("stops", [1,2,3,4,5]);
        $expected = 'stops=1&stops=2&stops=3&stops=4&stops=5';
        $this->assertEquals($expected, $this->serializationWriter->getSerializedContent());
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("stops", ["first" => 'First', 'second' => 'Second']);
        $this->serializationWriter->getSerializedContent()->getContents();
    }

    public function testWriteFloatValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("height", 12.394);
        $expected = 'height=12.394';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteEnumSetValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("status", new MaritalStatus('married,complicated'));
        $expected = 'status=married,complicated';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteNullValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("nextPage", null);
        $expected = 'nextPage=null';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteCollectionOfObjectValues(): void{
        $this->expectException(\RuntimeException::class);
        $this->serializationWriter = new FormSerializationWriter();
        $person1 = new Person();
        $person1->setName("John");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $person2 = new Person();
        $person2->setName('Jane');
        $person2->setMaritalStatus(new MaritalStatus('married'));
        $this->serializationWriter->writeAnyValue("to", [$person1, $person2]);
        $this->serializationWriter->getSerializedContent()->getContents();
    }

    public function testWriteObjectValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $person1 = new Person();
        $person1->setName("John Kennedy");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $person1->setAdditionalData(['ages' => [12, 13, 14], 'mari' => ['sells', 'nothing'], 'safes' => []]);
        $this->serializationWriter->writeObjectValue('to', $person1);
        $expected = 'name=John+Kennedy&maritalStatus=single&ages=12&ages=13&ages=14&mari=sells&mari=nothing';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteIntersectionWrapperObjectValue(): void
    {
        $person1 = new Person();
        $person1->setName("John");
        $person1->setMaritalStatus(new MaritalStatus('single'));
        $address = new Address();
        $address->setCity('Nairobi');
        $jsonSerializationWriter = new FormSerializationWriter();
        $beforeSerialization = fn (Parsable $n) => true;
        $afterSerialization = fn (Parsable $n) => true;
        $startSerialization = fn (Parsable $p, SerializationWriter $n) => true;
        $jsonSerializationWriter->setOnBeforeObjectSerialization($beforeSerialization);
        $jsonSerializationWriter->setOnAfterObjectSerialization($afterSerialization);
        $jsonSerializationWriter->setOnStartObjectSerialization($startSerialization);
        $jsonSerializationWriter->writeObjectValue("intersection", $person1, $address);
        $expected = 'name=John&maritalStatus=single&city=Nairobi';
        $this->assertEquals($expected, $jsonSerializationWriter->getSerializedContent()->getContents());
    }

    public function testWriteEnumValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("status", [new MaritalStatus('married'), new MaritalStatus('single')]);
        $expected = 'status=married&status=single';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteAnyValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $time = new Time('11:00:00');
        $this->serializationWriter->writeAnyValue("created", $time);
        $expected = 'created=11:00:00';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteNonParsableObjectValue(): void{
        $this->expectException(\RuntimeException::class);
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("times", (object)[
            "start" => Time::createFrom(12,0, 23),
            "end" => Time::createFrom(13, 45, 12)]);
        $this->serializationWriter->getSerializedContent()->getContents();
    }

    public function testWriteBooleanValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("available", true);
        $expected = 'available=true';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteTimeOnlyValue(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("time", Time::createFromDateTime(new \DateTime('2018-12-12T12:34:42+00:00Z')));
        $expected = 'time=12:34:42';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteIntegerValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("age", 23);
        $expected = 'age=23';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testWriteDateTimeValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("dateTime", new \DateTime('2018-12-12T12:34:42+00:00'));
        $expected = 'dateTime=2018-12-12T12:34:42+00:00';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    public function testGetSerializedContent(): void{
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("statement", "This is a string\tholta\b\n\n\tonce again");
        $expected = 'statement=This+is+a+string%5Ctholta%5C%5Cb%5Cn%5Cn%5Ctonce+again';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     */
    public function testWriteStringValue(): void {
        $this->serializationWriter = new FormSerializationWriter();
        $this->serializationWriter->writeAnyValue("statement", "This is a string\n\r\t");
        $expected = 'statement=This+is+a+string%5Cn%5Cr%5Ct';
        $actual = $this->serializationWriter->getSerializedContent()->getContents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testWriteDateIntervalValue(): void
    {
        $this->serializationWriter = new FormSerializationWriter();
        $interval = new DateInterval('P300DT100S');
        $this->serializationWriter->writeAnyValue('timeTaken', $interval);

        $content = $this->serializationWriter->getSerializedContent();
        $this->assertEquals('timeTaken=P300DT100S', $content->getContents());
    }

    public function testWriteBinaryContentValue(): void
    {
        $this->serializationWriter = new FormSerializationWriter();
        $stream = Utils::streamFor("Hello world!!!\r\t\t\t\n");
        $this->serializationWriter->writeBinaryContent('body', $stream);
        $stream->rewind();
        $this->serializationWriter->writeAnyValue('body3', $stream);
        $content = $this->serializationWriter->getSerializedContent();
        $this->assertEquals("body=Hello+world%21%21%21%5Cr%5Ct%5Ct%5Ct%5Cn&body3=Hello+world%21%21%21%5Cr%5Ct%5Ct%5Ct%5Cn", $content->getContents());
    }
}
