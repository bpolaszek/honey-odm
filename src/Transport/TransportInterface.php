<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Transport;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

/**
 * @template C
 */
interface TransportInterface
{
    public function flushPendingOperations(UnitOfWork $unitOfWork): void;

    /**
     * @param C $criteria
     */
    public function retrieveDocuments(mixed $criteria): iterable;

    public function retrieveDocumentById(ClassMetadataInterface $classMetadata, mixed $id): array|object|null;
}
