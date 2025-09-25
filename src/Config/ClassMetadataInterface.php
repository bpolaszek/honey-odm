<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionClass;

/**
 * @template P of PropertyMetadataInterface
 */
interface ClassMetadataInterface
{
    public string $className {get;}

    /**
     * @var array<string, P>
     */
    public array $propertiesMetadata {get;}
    public ReflectionClass $reflection {get;} // @phpstan-ignore missingType.generics
}
