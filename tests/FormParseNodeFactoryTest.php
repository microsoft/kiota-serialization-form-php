<?php

namespace Microsoft\Kiota\Serialization\Tests;

use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Serialization\Form\FormParseNodeFactory;
use PHPUnit\Framework\TestCase;

class FormParseNodeFactoryTest extends TestCase
{

    public function testGetRootParseNode(): void
    {
        $parseNode = (new FormParseNodeFactory())->getRootParseNode((new FormParseNodeFactory())->getValidContentType(), Utils::streamFor('name=Hello&age=23&age=34&age=45'));
        /** @var TestEntity $obj */
        $obj = $parseNode->getObjectValue([TestEntity::class, 'createFromDiscriminatorValue']);
        $this->assertNotNull($obj);
        $this->assertEquals([23,34,45], $obj->getAges());
        $this->assertEquals('Hello', $obj->getName());
    }

    public function testGetValidContentType(): void
    {
        $parseN = new FormParseNodeFactory();

        $this->assertEquals('application/x-www-form-urlencoded', $parseN->getValidContentType());
    }


}
class TestEntity implements Parsable, AdditionalDataHolder {
    private ?string $name = null;

    /**
     * @var array<int>|null
     */
    private ?array $ages = null;

    /**
     * @inheritDoc
     */
    public function getAdditionalData(): ?array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData(array $value): void
    {}

    /**
     * @inheritDoc
     */
    public function getFieldDeserializers(): array
    {
        return [
            'name' => fn(ParseNode $parseNode) => $this->setName($parseNode->getStringValue()),
            'age' => fn(ParseNode $parseNode) => $this->setAges($parseNode->getCollectionOfPrimitiveValues()),
        ];
    }

    /**
     * @inheritDoc
     */
    public function serialize(SerializationWriter $writer): void
    {
        $writer->writeStringValue('name', $this->getName());
        $writer->writeCollectionOfPrimitiveValues('age', $this->getAges());
    }

    /**
     * @return array<int>|null
     */
    public function getAges(): ?array
    {
        return $this->ages;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $value): void
    {
        $this->name = $value;
    }

    /**
     * @param array<int>|null $value
     * @return void
     */
    public function setAges(?array $value): void
    {
        $this->ages = $value;
    }

    public static function createFromDiscriminatorValue(ParseNode $node): TestEntity
    {
        return new TestEntity();
    }
}
