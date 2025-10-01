<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 *
 * @implements ObjectManagerInterface<C<P>, P>
 */
final class ObjectManager implements ObjectManagerInterface
{
    public readonly Identities $identities;
    private readonly ObjectFactory $factory;
    public private(set) UnitOfWork $unitOfWork;
    private bool $isFlushing = false;

    public function __construct(
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        public readonly DocumentMapperInterface $documentMapper,
        public readonly EventDispatcherInterface $eventDispatcher,
        public readonly TransportInterface $transport,
    ) {
        $this->identities = new Identities($classMetadataRegistry, $documentMapper);
        $this->factory = new ObjectFactory($classMetadataRegistry, $documentMapper, $this->identities);
        $this->resetUnitOfWork();
    }

    public function persist(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleUpsert($object, ...$objects);
    }

    public function remove(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleDeletion($object, ...$objects);
    }

    public function flush(): void
    {
        if ($this->isFlushing) {
            return; // Avoid recursive flush calls during event propagation
        }

        try {
            $this->isFlushing = true;
            $this->transport->flushPendingOperations($this->unitOfWork);
            $this->identities->attach(...$this->unitOfWork->getPendingUpserts());
            $this->identities->detach(...$this->unitOfWork->getPendingDeletions());
            $this->resetUnitOfWork();
        } finally {
            $this->isFlushing = false;
        }
    }

    public function find(string $className, mixed $id): ?object
    {
        if ($this->identities->containsId($className, $id)) {
            return $this->identities->getObject($className, $id);
        }

        $classMetadata = $this->classMetadataRegistry->getClassMetadata($className);

        $document = $this->transport->retrieveDocumentById($classMetadata, $id);
        if (!$document) {
            return null;
        }

        return $this->factory->factory($document, $classMetadata);
    }

    private function resetUnitOfWork(): void
    {
        $this->unitOfWork = new UnitOfWork($this);
    }
}
