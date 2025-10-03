<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

interface DocumentMapperInterface
{
    /**
     * @template O of object
     *
     * @param array<string, mixed> $source The document to map from
     * @param O $target The object to map to
     *
     * @return O
     */
    public function documentToObject(array $source, object $target, MappingContextInterface $context): object;

    /**
     * @template O of object
     *
     * @param O $source The object to map from
     * @param array<string, mixed> $target The document to map to
     *
     * @return array<string, mixed>
     */
    public function objectToDocument(object $source, array $target, MappingContextInterface $context): array;
}
