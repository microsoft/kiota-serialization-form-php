<?php

namespace Microsoft\Kiota\Serialization\Form;

use InvalidArgumentException;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriter;
use Microsoft\Kiota\Abstractions\Serialization\SerializationWriterFactory;

class FormSerializationWriterFactory implements SerializationWriterFactory
{

    /**
     * @inheritDoc
     */
    public function getSerializationWriter(string $contentType): SerializationWriter
    {
        if (empty(trim($contentType))) {
            throw new InvalidArgumentException('Content type cannot be empty');
        }

        if(strcasecmp($this->getValidContentType(),$contentType) !== 0){
            throw new InvalidArgumentException("expected a {$this->getValidContentType()} content type.");
        }
        return new FormSerializationWriter();
    }

    /**
     * @inheritDoc
     */
    public function getValidContentType(): string
    {
        return 'application/x-www-form-urlencoded';
    }
}