<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Event\PostLoadEvent;
use Honey\ODM\Core\Event\PrePersistEvent;
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
    public private(set) UnitOfWork $unitOfWork;
    private bool $isFlushing = false;

    public function __construct(
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        public readonly DocumentMapperInterface $documentMapper,
        public readonly EventDispatcherInterface $eventDispatcher,
        public readonly TransportInterface $transport,
    ) {
        $this->identities = new Identities($classMetadataRegistry, $documentMapper);
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

        return $this->factory($document, $classMetadata);
    }

    /**
     * @param ClassMetadataInterface<O, P> $classMetadata
     */
    public function factory(mixed $document, ClassMetadataInterface $classMetadata): object
    {
        $identityMap = $this->identities;
        $id = $this->classMetadataRegistry->getIdFromDocument($document, $classMetadata->className);
        $className = $classMetadata->className;
        if ($identityMap->containsId($className, $id)) {
            return $identityMap->getObject($className, $id);
        }
        $object = Reflection::class($className)->newLazyGhost(function (object $ghost) use ($document, $classMetadata) {
            $object = $this->documentMapper->documentToObject(
                $classMetadata,
                $document,
                $ghost,
            );
            $this->eventDispatcher->dispatch(new PostLoadEvent($object, $this));
        });
        $identityMap->attach($object);
        $identityMap->rememberState($object, $document);

        return $object;
    }

    public function firePrePersistEvent(object $object): void
    {
        if (UnitOfWork::CREATE !== $this->unitOfWork->getPendingOperation($object)) {
            return;
        }
        if ($this->unitOfWork->hasFiredEvent($object, PrePersistEvent::class)) {
            return;
        }
        $event = new PrePersistEvent($object, $this);
        $this->eventDispatcher->dispatch($event);
        $this->unitOfWork->registerFiredEvent($object, PrePersistEvent::class);
    }

    private function resetUnitOfWork(): void
    {
        $this->unitOfWork = new UnitOfWork($this);
    }
}
