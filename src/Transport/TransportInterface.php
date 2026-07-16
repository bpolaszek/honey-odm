<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Transport;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

interface TransportInterface
{
    /**
     * @param array<string, mixed> $flushOptions
     */
    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void;

    /**
     * Compiles the generic criteria into the platform's native query language and retrieves
     * the matching documents, as associative arrays.
     *
     * @param AsDocument<object> $classMetadata
     *
     * @return iterable<array<string, mixed>>
     *
     * @throws \Honey\ODM\Core\Criteria\UnsupportedExpressionException
     */
    public function retrieveDocuments(AsDocument $classMetadata, Criteria $criteria): iterable;

    /**
     * @param AsDocument<object> $classMetadata
     *
     * @return array<string, mixed>|null
     */
    public function retrieveDocumentById(AsDocument $classMetadata, mixed $id): ?array;
}
