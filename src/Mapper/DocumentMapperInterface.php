<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;

/**
 * @template D of object|array<string, mixed>
 * @template O of object
 */
interface DocumentMapperInterface
{
    /**
     * @param ClassMetadataInterface<O, PropertyMetadataInterface> $classMetadata
     * @param D $source The document to map from
     * @param O $target The object to map to
     *
     * @return O
     */
    public function documentToObject(ClassMetadataInterface $classMetadata, mixed $source, object $target): object; // @phpstan-ignore missingType.generics

    /**
     * @param ClassMetadataInterface<O, PropertyMetadataInterface> $classMetadata
     * @param O $source
     *
     * @return D
     */
    public function objectToDocument(ClassMetadataInterface $classMetadata, object $source, mixed $target = []): mixed; // @phpstan-ignore missingType.generics
}
