<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionProperty;

/**
 * @template C of ClassMetadataInterface
 */
interface PropertyMetadataInterface
{
    /**
     * @var C
     */
    public ClassMetadataInterface $classMetadata {get; }
    public ReflectionProperty $reflection {get; }
    public bool $primary {get; }
}
