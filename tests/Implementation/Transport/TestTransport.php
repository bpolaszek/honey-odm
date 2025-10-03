<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Transport;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

use function array_merge;

/**
 * @implements TransportInterface<Criteria>
 */
final class TestTransport implements TransportInterface
{
    /**
     * @var array<string, ArrayCollection<int, array<string, mixed>>
     */
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
            $this->storage[$bucket] ??= new ArrayCollection();
            $this->storage[$bucket][$id] = array_merge($this->storage[$bucket][$id] ?? [], $document);
        }
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            /** @var TestAsDocument $classMetadata */
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $bucket = $classMetadata->bucket;
            $id = $classMetadataRegistry->getIdFromObject($object);
            unset($this->storage[$bucket][$id]);
        }
    }

    public function retrieveDocuments(mixed $criteria): array
    {
        throw new \LogicException("Not implemented");
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
