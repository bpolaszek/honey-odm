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
    public string $className {get; set; }

    /**
     * @var array<string, P>
     */
    public array $propertiesMetadata {get; set; }

    /**
     * @var ReflectionClass<O>
     */
    public ReflectionClass $reflection {get; set; }

    /**
     * @return P
     */
    public function getIdPropertyMetadata(): PropertyMetadataInterface;
}
