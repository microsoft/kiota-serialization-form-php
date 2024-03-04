<?php

namespace Microsoft\Kiota\Serialization\Form;

use Exception;
use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Serialization\ParseNode;
use Microsoft\Kiota\Abstractions\Serialization\ParseNodeFactory;
use Psr\Http\Message\StreamInterface;

class FormParseNodeFactory implements ParseNodeFactory
{

    /**
     * @inheritDoc
     */
    public function getRootParseNode(string $contentType, StreamInterface $rawResponse): ParseNode
    {
        if (empty($contentType)) {
            throw new InvalidArgumentException('$contentType cannot be empty.');
        }

        $streamContents = $rawResponse->getContents();
        if (strcasecmp($this->getValidContentType(), $contentType) !== 0){
            throw new InvalidArgumentException("expected a {$this->getValidContentType()} content type.");
        }
        if (empty($streamContents)){
            throw new InvalidArgumentException('$rawResponse cannot be empty.');
        }
        try {
            $fields = array_filter(array_map(fn ($val) => explode("=", $val), explode("&", $streamContents)), fn ($item) => count($item) == 2);
            $finalResult = [];
            foreach ($fields as $field) {
                if (array_key_exists($field[0], $finalResult)) {
                    $finalResult[$field[0]] []= $field[1];
                } else {
                    $finalResult[$field[0]] = [$field[1]];
                }
            }
            $fields2 = array_map(fn ($item) => count($item) <= 1 ? implode(',', $item) : $item, $finalResult);
        } catch (Exception $ex){
            throw new \RuntimeException('The was a problem parsing the response.', 1, $ex);
        }
        return new FormParseNode($fields2);
    }

    /**
     * @inheritDoc
     */
    public function getValidContentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}