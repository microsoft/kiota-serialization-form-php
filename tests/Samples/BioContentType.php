<?php

namespace Microsoft\Kiota\Serialization\Form\Tests\Samples;

use Microsoft\Kiota\Abstractions\Enum;

class BioContentType extends Enum
{
    const HTML = 'html';
    const PLAIN = 'plain';
    const JSON = 'json';
}
