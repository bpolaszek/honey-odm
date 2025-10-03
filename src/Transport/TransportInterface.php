<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Transport;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

/**
 * @template TCriteria of mixed
 */
interface TransportInterface
{
    /**
     * @template TClassMetadata of ClassMetadataInterface
     * @template TPropertyMetadata of PropertyMetadataInterface
     * @param UnitOfWork<TClassMetadata, TPropertyMetadata, TCriteria> $unitOfWork
     */
    public function flushPendingOperations(UnitOfWork $unitOfWork): void;

    /**
     * @param TCriteria $criteria
     *
     * @return iterable<array<string, mixed>>
     */
    public function retrieveDocuments(mixed $criteria): iterable;

    /**
     * @template TObject of object
     * @param ClassMetadataInterface<TObject, PropertyMetadataInterface> $classMetadata
     * @return array<string, mixed>|null
     */
    public function retrieveDocumentById(ClassMetadataInterface $classMetadata, mixed $id): ?array;
}
