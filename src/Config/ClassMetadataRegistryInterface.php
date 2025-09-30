<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

/**
 * @template O of object
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 */
interface ClassMetadataRegistryInterface
{
    /**
     * @param class-string<O> $className
     */
    public function hasClassMetadata(string $className): bool;

    /**
     * @param class-string<O> $className
     * @return C<O, P>
     */
    public function getClassMetadata(string $className): ClassMetadataInterface;

    /**
     * @param array<string, mixed> $document
     * @param class-string<O> $className
     */
    public function getIdFromDocument(array $document, string $className): mixed;

    public function getIdFromObject(object $object): mixed;
}
