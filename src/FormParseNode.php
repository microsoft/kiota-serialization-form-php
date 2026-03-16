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
        return self::isNullRaw($this->node);
    }

    /**
     * Checks if the given raw value is null or a null string.
     * @param mixed $rawValue
     * @return bool
     */
    private static function isNullRaw(mixed $rawValue): bool
    {
        return ($rawValue === null) || (is_string($rawValue) && strcasecmp($rawValue, 'null') === 0);
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
        return self::getStringValueFromRaw($this->node);
    }

    private static function getStringValueFromRaw(mixed $rawValue): ?string
    {
        return is_string($rawValue) && !self::isNullRaw($rawValue)
            ? urldecode($rawValue)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getBooleanValue(): ?bool
    {
        return self::getBooleanValueFromRaw($this->node);
    }

    private static function getBooleanValueFromRaw(mixed $rawValue): ?bool
    {
        if (self::isNullRaw($rawValue)) {
            return null;
        }
        return filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @inheritDoc
     */
    public function getIntegerValue(): ?int
    {
        return self::getIntegerValueFromRaw($this->node);
    }

    private static function getIntegerValueFromRaw(mixed $rawValue): ?int
    {
        return !self::isNullRaw($rawValue) && filter_var($rawValue, FILTER_VALIDATE_INT, FILTER_FLAG_NONE) ? intval($rawValue) : null;
    }

    /**
     * @inheritDoc
     */
    public function getFloatValue(): ?float
    {
        return self::getFloatValueFromRaw($this->node);
    }

    private static function getFloatValueFromRaw(mixed $rawValue): ?float
    {
        return !self::isNullRaw($rawValue) ? floatval($rawValue) : null;
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
        if (!is_callable($type, false, $callableString)) {
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
            switch ($type) {
                case 'bool':
                    return FormParseNode::getBooleanValueFromRaw($x);
                case 'string':
                    return FormParseNode::getStringValueFromRaw($x);
                case 'int':
                    return FormParseNode::getIntegerValueFromRaw($x);
                case 'float':
                    return FormParseNode::getFloatValueFromRaw($x);
                case 'null':
                    return null;
                case 'array':
                    return (new FormParseNode($x))->getCollectionOfPrimitiveValues();
                case Date::class:
                    return FormParseNode::getDateValueFromRaw($x);
                case Time::class:
                    return FormParseNode::getTimeValueFromRaw($x);
                default:
                    if (is_subclass_of($type, Enum::class)) {
                        return !self::isNullRaw($x) ? new $type($x) : null;
                    }
                    if (is_subclass_of($type, StreamInterface::class)) {
                        return (new FormParseNode($x))->getBinaryContent();
                    }
                    throw new InvalidArgumentException("Unable to decode type $type");
            }
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
        return self::getDateValueFromRaw($this->node);
    }

    private static function getDateValueFromRaw(mixed $rawValue): ?Date
    {
        return $rawValue !== null ? new Date(strval($rawValue)) : null;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getTimeValue(): ?Time
    {
        return self::getTimeValueFromRaw($this->node);
    }

    private static function getTimeValueFromRaw(mixed $rawValue): ?Time
    {
        if ($rawValue === null) {
            return null;
        }
        $dateTime = is_string($rawValue) ? new DateTime($rawValue) : null;
        return $dateTime !== null ? Time::createFromDateTime($dateTime) : null;
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
        if (!is_subclass_of($targetClass, Enum::class)) {
            throw new InvalidArgumentException('Invalid enum provided.');
        }
        $result = array_map(static function ($x) use ($targetClass) {
            if (self::isNullRaw($x)) {
                return null;
            }
            return new $targetClass($x);
        }, $this->node);
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