<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionClass;

/**
 * @template TObject of object
 * @template TPropertyMetadata of PropertyMetadataInterface
 */
interface ClassMetadataInterface
{
    /**
     * @var class-string<TObject>
     */
    public string $className {get;
    set; }

    /**
     * @var array<string, TPropertyMetadata>
     */
    public array $propertiesMetadata {get;
    set; }

    /**
     * @var ReflectionClass<TObject>
     */
    public ReflectionClass $reflection {get;
    set; }

    /**
     * @return TPropertyMetadata
     */
    public function getIdPropertyMetadata(): PropertyMetadataInterface;
}
