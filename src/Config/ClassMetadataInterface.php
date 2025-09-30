<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionClass;

/**
 * @template O of object
 * @template P of PropertyMetadataInterface
 */
interface ClassMetadataInterface
{
    /**
     * @var class-string<O>
     */
    public string $className {get; }

    /**
     * @var array<string, P>
     */
    public array $propertiesMetadata {get; }

    /**
     * @var ReflectionClass<O>
     */
    public ReflectionClass $reflection {get; }

    /**
     * @return P
     */
    public function getIdPropertyMetadata(): PropertyMetadataInterface;
}
