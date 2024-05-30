<?php

namespace Microsoft\Kiota\Serialization\Form;

use DateInterval;
use DateTime;
use Exception;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\AdditionalDataHolder;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFromStringTrait;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class FormParseNode implements ParseNode
{
    use ParseNodeFromStringTrait;

    /** @var callable(Parsable): void|null $onBeforeAssignFieldValues */
    private $onBeforeAssignFieldValues = null;

    /** @var callable(Parsable): void|null */
    private $onAfterAssignFieldValues = null;

    /** @var mixed|null $node */
    private $node;

    /**
     * @param mixed|null $rawValue
     */
    public function __construct($rawValue)
    {
        $this->node = $rawValue;
    }

    /**
     * Checks if the current node value is null or null string.
     * @return bool
     */
    private function isNull(): bool
    {
        return ($this->node === null) || (is_string($this->node) && strcasecmp($this->node, 'null') === 0);
    }

    /**
     * @param string $key
     * @return string
     */
    private function sanitizeKey(string $key): string {
        if (empty($key)) return $key;
        return urldecode(trim($key));
    }

    /**
     * @inheritDoc
     */
    public function getChildNode(string $identifier): ?FormParseNode
    {
        $sanitizedKey = $this->sanitizeKey($identifier);
        if ((!is_array($this->node)) || (($this->node[$sanitizedKey] ?? null) === null)) {
            return null;
        }
        return new self($this->node[$sanitizedKey]);
    }
    /**
     * @inheritDoc
     */
    public function getStringValue(): ?string
    {
        return is_string($this->node) && !$this->isNull()
            ? urldecode(addcslashes($this->node, "\\\t\r\n"))
            : null;
    }
    /**
     * @inheritDoc
     */
    public function getBooleanValue(): ?bool
    {
        return (!$this->isNull() && filter_var($this->node, FILTER_VALIDATE_BOOLEAN)) ? boolval($this->node) : null;
    }

    /**
     * @inheritDoc
     */
    public function getIntegerValue(): ?int
    {
       return !$this->isNull() && filter_var($this->node, FILTER_VALIDATE_INT, FILTER_FLAG_NONE) ? intval($this->node) : null;
    }

    /**
     * @inheritDoc
     */
    public function getFloatValue(): ?float
    {
        return !$this->isNull() ? floatval($this->node) : null;
    }

    /**
     * @inheritDoc
     */
    public function getObjectValue(array $type): ?Parsable
    {
        if ($this->isNull()) {
            return null;
        }
        if (!is_subclass_of($type[0], Parsable::class)){
            throw new InvalidArgumentException("Invalid type $type[0] provided.");
        }
        if (!is_callable($type, true, $callableString)) {
            throw new InvalidArgumentException('Undefined method '. $type[1]);
        }
        $result = $callableString($this);
        if($this->getOnBeforeAssignFieldValues() !== null) {
            $this->getOnBeforeAssignFieldValues()($result);
        }
        $this->assignFieldValues($result);
        if ($this->getOnAfterAssignFieldValues() !== null){
            $this->getOnAfterAssignFieldValues()($result);
        }
        return $result;
    }

    /**
     * @param AdditionalDataHolder|Parsable $item
     * @return void
     */
    private function assignFieldValues($item): void
    {
        $fieldDeserializers = [];
        if (is_a($item, Parsable::class)){
            $fieldDeserializers = $item->getFieldDeserializers();
        }
        $isAdditionalDataHolder = false;
        $additionalData = [];
        if (is_a($item, AdditionalDataHolder::class)) {
            $isAdditionalDataHolder = true;
            $additionalData = $item->getAdditionalData() ?? [];
        }
        if (is_array($this->node)) {
            foreach ($this->node as $key => $value) {
                $deserializer = $fieldDeserializers[$key] ?? null;
                if ($deserializer !== null) {
                    $deserializer(new FormParseNode($value));
                } else {
                    $key                  = (string)$key;
                    $additionalData[$key] = $value;
                }
            }
        }

        if ( $isAdditionalDataHolder ) {
            $item->setAdditionalData($additionalData);
        }
    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfObjectValues(array $type): ?array
    {
        throw new RuntimeException('Collection of objects are not supported.');
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getCollectionOfPrimitiveValues(?string $typeName = null): ?array
    {
        if (!is_array($this->node)) {
            return null;
        }
        return array_map(static function ($x) use ($typeName) {
            $type = empty($typeName) ? get_debug_type($x) : $typeName;
            return (new FormParseNode($x))->getAnyValue($type);
        }, $this->node);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAnyValue(string $type) {
        switch ($type){
            case 'bool':
                return $this->getBooleanValue();
            case 'string':
                return $this->getStringValue();
            case 'int':
                return $this->getIntegerValue();
            case 'float':
                return $this->getFloatValue();
            case 'null':
                return null;
            case 'array':
                return $this->getCollectionOfPrimitiveValues();
            case Date::class:
                return $this->getDateValue();
            case Time::class:
                return $this->getTimeValue();
            default:
                if (is_subclass_of($type, Enum::class)){
                    return $this->getEnumValue($type);
                }
                if (is_subclass_of($type, StreamInterface::class)) {
                    return $this->getBinaryContent();
                }
                throw new InvalidArgumentException("Unable to decode type $type");
        }

    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateTimeValue(): ?DateTime
    {
        return is_string($this->node) ? new DateTime($this->node) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateIntervalValue(): ?DateInterval
    {
        return ($this->node !== null) ? $this->parseDateIntervalFromString(strval($this->node)) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getDateValue(): ?Date
    {
        return $this->node !== null ?  new Date(strval($this->node)): null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getTimeValue(): ?Time
    {
        $dateTime = $this->getDateTimeValue();
        return $dateTime !== null ?  Time::createFromDateTime($dateTime) : null;
    }

    /**
     * @inheritDoc
     */
    public function getEnumValue(string $targetEnum): ?Enum
    {
        if ($this->isNull()){
            return null;
        }
        if (!is_subclass_of($targetEnum, Enum::class)) {
            throw new InvalidArgumentException('Invalid enum provided.');
        }
        return new $targetEnum($this->node);
    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfEnumValues(string $targetClass): ?array
    {
        if (!is_array($this->node)) {
            return null;
        }
        $result = array_map(static function ($val) use($targetClass) {
            return $val->getEnumValue($targetClass);
        }, array_map(static function ($value) {
            return new FormParseNode($value);
        }, $this->node));
        return array_filter($result, fn ($item) => !is_null($item));
    }

    /**
     * @inheritDoc
     */
    public function getBinaryContent(): ?StreamInterface
    {
        if ($this->isNull()) {
            return null;
        } elseif (is_array($this->node)) {
            return Utils::streamFor(json_encode($this->node));
        }
        return Utils::streamFor(strval($this->node));
    }

    /**
     * @inheritDoc
     */
    public function getOnBeforeAssignFieldValues(): ?callable
    {
        return $this->onBeforeAssignFieldValues;
    }

    /**
     * @inheritDoc
     */
    public function getOnAfterAssignFieldValues(): ?callable
    {
        return $this->onAfterAssignFieldValues;
    }

    /**
     * @inheritDoc
     */
    public function setOnAfterAssignFieldValues(callable $value): void
    {
        $this->onAfterAssignFieldValues = $value;
    }

    /**
     * @inheritDoc
     */
    public function setOnBeforeAssignFieldValues(callable $value): void
    {
        $this->onBeforeAssignFieldValues = $value;
    }
}