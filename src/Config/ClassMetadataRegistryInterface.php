<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template TClassMetadata of ClassMetadataInterface
 * @template TPropertyMetadata of PropertyMetadataInterface
 */
interface ClassMetadataRegistryInterface
{
    public PropertyAccessorInterface $propertyAccessor {get; }

    /**
     * @param class-string $className
     */
    public function hasClassMetadata(string $className): bool;

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return TClassMetadata<O, TPropertyMetadata>
     */
    public function getClassMetadata(string $className): ClassMetadataInterface;

    /**
     * @template O
     *
     * @param array<string, mixed> $document
     * @param class-string<O> $className
     */
    public function getIdFromDocument(array $document, string $className): mixed;

    public function getIdFromObject(object $object): mixed;
}
