<?php

namespace Microsoft\Kiota\Serialization\Form;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Enum;
use Microsoft\Kiota\Abstractions\Serialization\Parsable;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Types\Date;
use Microsoft\Kiota\Abstractions\Types\Time;
use Psr\Http\Message\StreamInterface;

class FormParseNode implements ParseNode
{

    /** @var callable(Parsable): void|null $onBeforeAssignFieldValues */
    private $onBeforeAssignFieldValues = null;

    /** @var callable(Parsable): void|null */
    private $onAfterAssignFieldValues = null;
    private string $rawValue;
    private string $decodedValue;
    private array $fields = [];

    public function __construct(string $rawValue)
    {
        $this->rawValue = $rawValue;
        $this->decodedValue = urldecode($this->rawValue);
        $this->fields = array_filter(array_map(fn ($val) => explode("=", $val), explode("&", $this->rawValue)), fn ($item) => count($item) == 2);
        $finalResult = [];
        foreach ($this->fields as $field) {
            if (array_key_exists($field[0], $finalResult)) {
                $finalResult[$field[0]] []= $field[1];
            } else {
                $finalResult[$field[0]] = [$field[1]];
            }
        }
        var_dump($finalResult);
        $this->fields = array_map(fn ($item) => implode(',', $item), $finalResult);
        var_dump($this->fields);
    }

    /**
     * @inheritDoc
     */
    public function getChildNode(string $identifier): ?ParseNode
    {
        // TODO: Implement getChildNode() method.
    }
    /**
     * @inheritDoc
     */
    public function getStringValue(): ?string
    {
        // TODO: Implement getStringValue() method.
    }

    private function sanitizeKey(?string $key): ?string {
        if (empty($key)) return $key;
        return urldecode(trim($key));
    }
    /**
     * @inheritDoc
     */
    public function getBooleanValue(): ?bool
    {
        // TODO: Implement getBooleanValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getIntegerValue(): ?int
    {
        // TODO: Implement getIntegerValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getFloatValue(): ?float
    {
        // TODO: Implement getFloatValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getObjectValue(array $type): ?Parsable
    {
       if (!method_exists(...$type)) {
           throw new InvalidArgumentException("Function does not exist.");
       }

       $item = call_user_func($type);

       call_user_func($this->getOnBeforeAssignFieldValues(), $item);
       $this->assignFieldValues($item);
       call_user_func($this->getOnAfterAssignFieldValues(), $item);
    }

    private function assignFieldValues(Parsable $item): void
    {

    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfObjectValues(array $type): ?array
    {
        // TODO: Implement getCollectionOfObjectValues() method.
    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfPrimitiveValues(?string $typeName = null): ?array
    {
        // TODO: Implement getCollectionOfPrimitiveValues() method.
    }

    /**
     * @inheritDoc
     */
    public function getDateTimeValue(): ?DateTime
    {
        // TODO: Implement getDateTimeValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getDateIntervalValue(): ?DateInterval
    {
        // TODO: Implement getDateIntervalValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getDateValue(): ?Date
    {
        // TODO: Implement getDateValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getTimeValue(): ?Time
    {
        // TODO: Implement getTimeValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getEnumValue(string $targetEnum): ?Enum
    {
        // TODO: Implement getEnumValue() method.
    }

    /**
     * @inheritDoc
     */
    public function getCollectionOfEnumValues(string $targetClass): ?array
    {
        // TODO: Implement getCollectionOfEnumValues() method.
    }

    /**
     * @inheritDoc
     */
    public function getBinaryContent(): ?StreamInterface
    {
        // TODO: Implement getBinaryContent() method.
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