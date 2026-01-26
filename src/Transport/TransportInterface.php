<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Transport;

use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

/**
 * @template TCriteria of mixed
 * @template TFlushOptions of array<string, mixed>
 */
interface TransportInterface
{
    /**
     * @template TClassMetadata of ClassMetadata
     * @template TPropertyMetadata of PropertyMetadata
     *
     * @param UnitOfWork<TClassMetadata, TPropertyMetadata, TCriteria, TFlushOptions> $unitOfWork
     * @param TFlushOptions $flushOptions
     */
    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void;

    /**
     * @param TCriteria $criteria
     *
     * @return iterable<array<string, mixed>>
     */
    public function retrieveDocuments(mixed $criteria): iterable;

    /**
     * @template TObject of object
     *
     * @param ClassMetadata<TObject, PropertyMetadata> $classMetadata
     *
     * @return array<string, mixed>|null
     */
    public function retrieveDocumentById(ClassMetadata $classMetadata, mixed $id): ?array;
}
