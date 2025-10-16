<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionClass;
use RuntimeException;

/**
 * @template TObject of object
 * @template TPropertyMetadata of PropertyMetadata
 */
abstract class ClassMetadata
{
    /**
     * @var class-string<TObject>
     */
    final public protected(set) string $className;

    /**
     * @var ReflectionClass<TObject>
     */
    final public protected(set) ReflectionClass $reflection;

    /**
     * @var array<string, TPropertyMetadata>
     */
    final public protected(set) array $propertiesMetadata = [];

    final public function getIdPropertyMetadata(): PropertyMetadata
    {
        return array_find(
            $this->propertiesMetadata,
            fn (PropertyMetadata $metadata) => $metadata->primary,
        ) ?? throw new RuntimeException('No primary property found in class metadata');
    }
}
