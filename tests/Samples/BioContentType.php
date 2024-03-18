<?php

namespace Microsoft\Kiota\Serialization\Tests\Samples;

use Microsoft\Kiota\Abstractions\Enum;

class BioContentType extends Enum
{
    const HTML = 'html';
    const PLAIN = 'plain';
    const JSON = 'json';
}