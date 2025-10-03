<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 */
interface ClassMetadataRegistryInterface
{
    /**
     * @param class-string $className
     */
    public function hasClassMetadata(string $className): bool;

    /**
     * @template O
     * @param class-string<O> $className
     *
     * @return C<O, P>
     */
    public function getClassMetadata(string $className): ClassMetadataInterface;

    /**
     * @template O
     * @param array<string, mixed> $document
     * @param class-string<O> $className
     */
    public function getIdFromDocument(array $document, string $className): mixed;

    public function getIdFromObject(object $object): mixed;
}
