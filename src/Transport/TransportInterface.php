<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Transport;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

/**
 * @template F of mixed
 */
interface TransportInterface
{
    public function flushPendingOperations(UnitOfWork $unitOfWork): void;

    /**
     * @param F $criteria
     *
     * @return iterable<array<string, mixed>>
     */
    public function retrieveDocuments(mixed $criteria): iterable;

    /**
     * @return array<string, mixed>|null
     */
    public function retrieveDocumentById(ClassMetadataInterface $classMetadata, mixed $id): ?array;
}
