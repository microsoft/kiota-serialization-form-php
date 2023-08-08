<?php

namespace Microsoft\Kiota\Serialization\Form;

use DateInterval;
use DateTime;
use GuzzleHttp\Psr7\Utils;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class FormSerializationWriter implements SerializationWriter
{
    /** @var callable|null  */
    private $onAfterObjectSerialization = null;
    /** @var callable|null  */
    private $onBeforeObjectSerialization = null;
    /** @var callable|null */
    private $onStartObjectSerialization = null;

    /** @var array<string> $jsonData */
    private array $jsonData = [];

    private int $depth = 0;
    /**
     * @inheritDoc
     */
    public function writeStringValue(?string $key, ?string $value): void
    {
        // TODO: Implement writeStringValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeBooleanValue(?string $key, ?bool $value): void
    {

    }

    /**
     * @inheritDoc
     */
    public function writeFloatValue(?string $key, ?float $value): void
    {
        // TODO: Implement writeFloatValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeIntegerValue(?string $key, ?int $value): void
    {
        // TODO: Implement writeIntegerValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeDateTimeValue(?string $key, ?DateTime $value): void
    {
        // TODO: Implement writeDateTimeValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfObjectValues(?string $key, ?array $values): void
    {
        throw new RuntimeException("Form serialization does not support collections.");
    }

    /**
     * @inheritDoc
     */
    public function writeObjectValue(?string $key, ?Parsable $value, Parsable ...$additionalValuesToMerge): void
    {
        if ($this->depth > 0) {
            throw new RuntimeException("Form serialization does not support nested objects.");
        }
        $this->depth++;
        $cleanedAdditionalValuesToMerge = array_filter($additionalValuesToMerge, fn ($val) => $val !== null);
        if ($value === null && count($cleanedAdditionalValuesToMerge) === 0) return;

        if ($value != null) {
            if ($this->getOnBeforeObjectSerialization() !== null) {
                call_user_func($this->getOnBeforeObjectSerialization(), $value);
            }
            if ($this->getOnStartObjectSerialization() !== null) {
                call_user_func($this->getOnStartObjectSerialization(), $value, $this);
            }
            $value->serialize($this);
        }
        foreach($cleanedAdditionalValuesToMerge as $additionalValueToMerge)
        {
            if ($this->getOnBeforeObjectSerialization() !== null) {
                call_user_func($this->getOnBeforeObjectSerialization(), $additionalValueToMerge);
            }
            if ($this->getOnStartObjectSerialization() !== null) {
                call_user_func($this->getOnStartObjectSerialization(), $additionalValueToMerge, $this);
            }
            $additionalValueToMerge->serialize($this);
            if ($this->getOnAfterObjectSerialization() !== null) {
                call_user_func($this->getOnAfterObjectSerialization(), $additionalValueToMerge);
            }
        }
        if ($value !== null && $this->getOnAfterObjectSerialization() !== null) {
            call_user_func($this->getOnAfterObjectSerialization(), $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function getSerializedContent(): StreamInterface
    {
        return Utils::streamFor(implode("", $this->jsonData));
    }

    /**
     * @inheritDoc
     */
    public function writeEnumValue(?string $key, ?Enum $value): void
    {
        // TODO: Implement writeEnumValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfEnumValues(?string $key, ?array $values): void
    {
        // TODO: Implement writeCollectionOfEnumValues() method.
    }

    /**
     * @inheritDoc
     */
    public function writeNullValue(?string $key): void
    {
        $this->writeStringValue($key, "null");
    }

    /**
     * @inheritDoc
     */
    public function writeAdditionalData(?array $value): void
    {
        // TODO: Implement writeAdditionalData() method.
    }

    /**
     * @inheritDoc
     */
    public function writeDateValue(?string $key, ?Date $value): void
    {
        // TODO: Implement writeDateValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeTimeValue(?string $key, ?Time $value): void
    {
        // TODO: Implement writeTimeValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeDateIntervalValue(?string $key, ?DateInterval $value): void
    {
        // TODO: Implement writeDateIntervalValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfPrimitiveValues(?string $key, ?array $value): void
    {
        // TODO: Implement writeCollectionOfPrimitiveValues() method.
    }

    /**
     * @inheritDoc
     */
    public function writeAnyValue(?string $key, $value): void
    {
        // TODO: Implement writeAnyValue() method.
    }

    /**
     * @inheritDoc
     */
    public function writeBinaryContent(?string $key, ?StreamInterface $value): void
    {
        // TODO: Implement writeBinaryContent() method.
    }

    /**
     * @inheritDoc
     */
    public function setOnBeforeObjectSerialization(?callable $value): void
    {
        $this->onBeforeObjectSerialization = $value;
    }

    /**
     * @inheritDoc
     */
    public function getOnBeforeObjectSerialization(): ?callable
    {
        return $this->onBeforeObjectSerialization;
    }

    /**
     * @inheritDoc
     */
    public function setOnAfterObjectSerialization(?callable $value): void
    {
        $this->onAfterObjectSerialization = $value;
    }

    /**
     * @inheritDoc
     */
    public function getOnAfterObjectSerialization(): ?callable
    {
        return $this->onAfterObjectSerialization;
    }

    /**
     * @inheritDoc
     */
    public function setOnStartObjectSerialization(?callable $value): void
    {
        $this->onStartObjectSerialization =  $value;
    }

    /**
     * @inheritDoc
     */
    public function getOnStartObjectSerialization(): ?callable
    {
        return $this->onStartObjectSerialization;
    }
}