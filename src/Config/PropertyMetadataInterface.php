<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionProperty;

interface PropertyMetadataInterface
{
    public ClassMetadataInterface $classMetadata {get; set; }
    public ReflectionProperty $reflection {get; set; }
    public bool $primary {get; }
    public ?TransformerMetadataInterface $transformer {get; }
}
