<?php

namespace Microsoft\Kiota\Serialization\Form;

use DateInterval;
use DateTime;
use DateTimeInterface;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriterToStringTrait;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;

class FormSerializationWriter implements SerializationWriter
{
    use SerializationWriterToStringTrait;
    /** @var callable|null  */
    private $onAfterObjectSerialization = null;
    /** @var callable|null  */
    private $onBeforeObjectSerialization = null;
    /** @var callable|null */
    private $onStartObjectSerialization = null;

    /** @var array<bool|float|int|string|null> $writer */
    private array $writer = [];


    private const PROPERTY_SEPARATOR = '&';

    private int $depth = 0;


    /**
     * @param string $propertyName
     * @return void
     */
    private function writePropertyName(string $propertyName): void
    {
        $key = urlencode($propertyName);
        $this->writer []= "$key=";
    }

    /**
     * @param string|null $key
     * @param string|float|int|bool|null $value
     * @return void
     */
    private function writePropertyValue(?string $key, $value): void {
        $this->writer []= $value;

        if ($key !== null) {
            $this->writer []= self::PROPERTY_SEPARATOR;
        }
    }


    /**
     * @inheritDoc
     */
    public function writeStringValue(?string $key, ?string $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $urlEncodedString = urlencode($this->getStringValueAsEscapedString($value));
            $this->writePropertyValue($key, $urlEncodedString);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeBooleanValue(?string $key, ?bool $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $options = ['false', 'true'];
            $this->writePropertyValue($key, $options[$value]);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeFloatValue(?string $key, ?float $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $this->writePropertyValue($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeIntegerValue(?string $key, ?int $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $this->writePropertyValue($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeDateTimeValue(?string $key, ?DateTime $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $this->writePropertyValue($key, $this->getDateTimeValueAsString($value));
        }
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfObjectValues(?string $key, ?array $values): void
    {
        throw new RuntimeException("Form serialization does not support collections.");
    }

    /**
     * Writes the specified model object value to the stream with an optional given key.
     * @param string|null $key the key to write the value with.
     * @param Parsable|null $value the value to write to the stream.
     * @param Parsable ...$additionalValuesToMerge additional Parsable values to merge.
     */
    public function writeObjectValue(?string $key, ?Parsable $value, ?Parsable ...$additionalValuesToMerge): void
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
        if (count($this->writer) > 0 && $this->writer[count($this->writer) - 1] === self::PROPERTY_SEPARATOR){
            array_pop($this->writer);
        }
        return Utils::streamFor(implode('', $this->writer));
    }

    /**
     * @inheritDoc
     */
    public function writeEnumValue(?string $key, ?Enum $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $this->writePropertyValue($key, "{$value->value()}");
        }
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfEnumValues(?string $key, ?array $values): void
    {
        if ($values !== null) {
            foreach ($values as $v) {
                $this->writeEnumValue($key, $v);
            }
            if (count($values) > 0) {
                array_pop($this->writer);
            }
            if ($key !== null) {
                $this->writer []= self::PROPERTY_SEPARATOR;
            }
        }
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
        if($value === null) {
            return;
        }
        foreach ($value as $key => $val) {
            $this->writeAnyValue($key, $val);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeDateValue(?string $key, ?Date $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $val = "$value";
            $this->writePropertyValue($key, $val);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeTimeValue(?string $key, ?Time $value): void
    {
        if ($value !== null) {
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $val = "$value";
            $this->writePropertyValue($key, $val);
        }
    }

    /**
     * @inheritDoc
     */
    public function writeDateIntervalValue(?string $key, ?DateInterval $value): void
    {
        if ($value !== null){
            if (!empty($key)) {
                $this->writePropertyName($key);
            }
            $this->writePropertyValue($key, $this->getDateIntervalValueAsString($value));
        }
    }

    /**
     * @inheritDoc
     */
    public function writeCollectionOfPrimitiveValues(?string $key, ?array $value): void
    {
        if ($value !== null) {
            foreach ($value as $val) {
                $this->writeAnyValue($key, $val);
            }
            if (count($value) > 0) {
                array_pop($this->writer);
            }
            if ($key !== null) {
                $this->writer [] = self::PROPERTY_SEPARATOR;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function writeAnyValue(?string $key, $value): void
    {
        if (is_null($value)) {
            $this->writeNullValue($key);
        } elseif (is_float($value)) {
            $this->writeFloatValue($key, $value);
        } elseif (is_string($value)) {
            $this->writeStringValue($key, $value);
        } elseif (is_int($value)) {
            $this->writeIntegerValue($key, $value);
        } elseif (is_bool($value)) {
            $this->writeBooleanValue($key, $value);
        } elseif ($value instanceof Date) {
            $this->writeDateValue($key, $value);
        } elseif ($value instanceof Time) {
            $this->writeTimeValue($key, $value);
        } elseif ($value instanceof DateInterval) {
            $this->writeDateIntervalValue($key, $value);
        } elseif ($value instanceof DateTime) {
            $this->writeDateTimeValue($key, $value);
        } elseif (is_array($value)) {
            $keys = array_filter(array_keys($value), 'is_string');
            // If there are string keys then that means this is a single
            // object we are dealing with
            // otherwise it is a collection of objects.
            if (!empty($keys)) {
                $this->writeNonParsableObjectValue($key, (object)$value);
            } elseif (!empty($value)) {
                if ($value[0] instanceof Parsable) {
                    throw new RuntimeException('Form serialization does not support object nesting.');
                } elseif ($value[0] instanceof Enum) {
                    $this->writeCollectionOfEnumValues($key, $value);
                } else {
                    $this->writeCollectionOfPrimitiveValues($key, $value);
                }
            }
        } elseif ($value instanceof stdClass) {
            $this->writeNonParsableObjectValue($key, $value);
        } elseif ($value instanceof Parsable) {
            throw new RuntimeException('Form serialization does not support object nesting.');
        } elseif ($value instanceof Enum) {
            $this->writeEnumValue($key, $value);
        } elseif ($value instanceof StreamInterface) {
            $this->writeStringValue($key, $value->getContents());
        } else {
            $type = gettype($value);
            throw new InvalidArgumentException("Could not serialize the object of type $type ");
        }
    }

    /**
     * @param string|null $key
     * @param mixed|null $value
     */
    public function writeNonParsableObjectValue(?string $key, $value): void{

        if ($this->depth > 0) {
            throw new RuntimeException('Form serialization does not support nesting.');
        }
        $this->depth++;
        if ($value !== null) {
            $value = (array)$value;

            if(!empty($key)) {
                if (count($value) > 0) {
                    throw new RuntimeException('Form Serialization does not support nesting.');
                }
                $this->writePropertyName($key);
            }
            foreach ($value as $kKey => $kVal) {
                $this->writeAnyValue($kKey, $kVal);
            }
            if (count($value) > 0) {
                array_pop($this->writer);
            }
            if ($key !== null) {
                $this->writer [] = self::PROPERTY_SEPARATOR;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function writeBinaryContent(?string $key, ?StreamInterface $value): void
    {
        if ($value !== null) {
            $val = $value->getContents();
            $value->rewind();
            $this->writeStringValue($key, $val);
        }
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