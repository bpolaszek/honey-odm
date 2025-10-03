<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;

interface DocumentMapperInterface
{
    /**
     * @template O of object
     *
     * @param ClassMetadataInterface<O, PropertyMetadataInterface> $classMetadata
     * @param array<string, mixed> $source The document to map from
     * @param O $target The object to map to
     *
     * @return O
     */
    public function documentToObject(ClassMetadataInterface $classMetadata, array $source, object $target): object; // @phpstan-ignore missingType.generics

    /**
     * @template O of object
     *
     * @param ClassMetadataInterface<O, PropertyMetadataInterface> $classMetadata
     * @param O $source The object to map from
     * @param array<string, mixed> $target The document to map to
     *
     * @return array<string, mixed>
     */
    public function objectToDocument(ClassMetadataInterface $classMetadata, object $source, array $target = []): array; // @phpstan-ignore missingType.generics
}
