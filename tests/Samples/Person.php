<?php

namespace Microsoft\Kiota\Serialization\Tests\Samples;

use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;

class Person implements Parsable, AdditionalDataHolder
{
    /** @var array<string, mixed> */
    private array $additionalData = [];
    private ?string $name = null;

    private ?int $age = null;
    private ?float $height = null;

    private ?MaritalStatus $maritalStatus = null;


    /** @var BioContentType[]|null  */
    private ?array $bioContentType = null;

    private ?bool $seen = null;
    /**
     * @inheritDoc
     */
    public function getFieldDeserializers(): array
    {
        $currentObject = $this;
        return [
            "name" => static function (ParseNode $n) use ($currentObject) {$currentObject->setName($n->getStringValue());},
            "age" => function (ParseNode $n) use ($currentObject) {$currentObject->setAge($n->getIntegerValue());},
            "height" => function (ParseNode $n) use ($currentObject) {$currentObject->setHeight($n->getFloatValue());},
            "maritalStatus" => function (ParseNode $n) use ($currentObject) {$currentObject->setMaritalStatus($n->getEnumValue(MaritalStatus::class));},
            "address" => function (ParseNode $n) use ($currentObject) {$currentObject->setAddress($n->getObjectValue(array(Address::class, 'createFromDiscriminatorValue')));},
            "type" => function (ParseNode $n) use ($currentObject) {$currentObject->setBioContentType($n->getCollectionOfEnumValues(BioContentType::class));},
            "seen" => function (ParseNode $n) use ($currentObject) {$currentObject->setSeen($n->getBooleanValue());}
        ];
    }

    /**
     * @param bool|null $seen
     */
    public function setSeen(?bool $seen): void
    {
        $this->seen = $seen;
    }

    /**
     * @return bool|null
     */
    public function getSeen(): ?bool
    {
        return $this->seen;
    }

    /**
     * @inheritDoc
     */
    public function serialize(SerializationWriter $writer): void {
        $writer->writeStringValue('name', $this->name);
        $writer->writeIntegerValue('age', $this->age);
        $writer->writeEnumValue('maritalStatus', $this->maritalStatus);
        $writer->writeFloatValue('height', $this->height);
        $writer->writeBooleanValue('seen', $this->seen);
        $writer->writeCollectionOfEnumValues('type', $this->bioContentType);
        $writer->writeAdditionalData($this->additionalData);
    }

    /**
     * @return array<BioContentType>|null
     */
    public function getBioContentType(): ?array
    {
        return $this->bioContentType;
    }

    /**
     * @param array<BioContentType>|null $bioContentType
     */
    public function setBioContentType(?array $bioContentType): void
    {
        $this->bioContentType = $bioContentType;
    }
    /**
     * @inheritDoc
     */
    public function getAdditionalData(): array {
        return $this->additionalData;
    }

    public static function createFromDiscriminatorValue(ParseNode $parseNode): Person {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function setAdditionalData(array $value): void {
        $this->additionalData = $value;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void {
        $this->name = $name;
    }

    /**
     * @return int|null
     */
    public function getAge(): ?int {
        return $this->age;
    }

    /**
     * @param int|null $age
     */
    public function setAge(?int $age): void {
        $this->age = $age;
    }

    /**
     * @return float|null
     */
    public function getHeight(): ?float {
        return $this->height;
    }

    /**
     * @param float|null $height
     */
    public function setHeight(?float $height): void {
        $this->height = $height;
    }

    /**
     * @param MaritalStatus|null $maritalStatus
     */
    public function setMaritalStatus(?MaritalStatus $maritalStatus): void {
        $this->maritalStatus = $maritalStatus;
    }

    /**
     * @return MaritalStatus|null
     */
    public function getMaritalStatus(): ?MaritalStatus {
        return $this->maritalStatus;
    }

    /**
     * @return Address|null
     */
    public function getAddress(): ?Address {
        return $this->address;
    }

    /**
     * @param Address|null $address
     */
    public function setAddress(?Address $address): void {
        $this->address = $address;
    }

}