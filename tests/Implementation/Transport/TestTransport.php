<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Transport;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

use function array_merge;

final class TestTransport implements TransportInterface
{
    public array $storage = [];

    public function flushPendingOperations(UnitOfWork $unitOfWork): void
    {
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;
        foreach ($unitOfWork->getPendingUpserts() as $object) {
            /** @var TestAsDocument $classMetadata */
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $id = $classMetadataRegistry->getIdFromObject($object);
            $bucket = $classMetadata->bucket;
            $document = $mapper->objectToDocument($classMetadata, $object);
            $this->storage[$bucket][$id] = array_merge($this->storage[$bucket][$id] ?? [], $document);
        }
        foreach ($unitOfWork->getPendingDeletions() as $object) {
            /** @var TestAsDocument $classMetadata */
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $bucket = $classMetadata->bucket;
            $id = $classMetadataRegistry->getIdFromObject($object);
            unset($this->storage[$bucket][$id]);
        }
    }

    public function retrieveDocuments(mixed $criteria): iterable
    {
        // TODO: Implement retrieveDocuments() method.
    }

    /**
     * @param TestAsDocument $classMetadata
     * @param mixed $id
     * @return array|object|null
     */
    public function retrieveDocumentById(ClassMetadataInterface $classMetadata, mixed $id): array|object|null
    {
        $bucket = $classMetadata->bucket;

        return $this->storage[$bucket][$id] ?? null;
    }
}
