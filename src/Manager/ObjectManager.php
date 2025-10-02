<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Event\PostLoadEvent;
use Honey\ODM\Core\Event\PostPersistEvent;
use Honey\ODM\Core\Event\PostRemoveEvent;
use Honey\ODM\Core\Event\PostUpdateEvent;
use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Event\PreRemoveEvent;
use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class ObjectManager implements ObjectManagerInterface
{
    public readonly Identities $identities;
    public private(set) UnitOfWork $unitOfWork;
    private bool $isFlushing = false;

    /**
     * @var array<class-string, ObjectRepositoryInterface>
     */
    protected array $repositories = [];

    public function __construct(
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        public readonly DocumentMapperInterface $documentMapper,
        public readonly EventDispatcherInterface $eventDispatcher,
        public readonly TransportInterface $transport,
    ) {
        $this->identities = new Identities($classMetadataRegistry, $documentMapper);
        $this->resetUnitOfWork();
    }

    protected function registerRepository(string $className, ObjectRepositoryInterface $repository): void
    {
        // Ensures the class is properly configured, this will throw an exception otherwise
        $this->classMetadataRegistry->getClassMetadata($className);
        $this->repositories[$className] = $repository;
    }

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return ObjectRepositoryInterface<O>
     */
    protected function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className]
            ?? throw new InvalidArgumentException("No repository registered for class $className");
    }

    final public function persist(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleUpsert($object, ...$objects);
    }

    final public function remove(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleDeletion($object, ...$objects);
    }

    final public function flush(): void
    {
        if ($this->isFlushing) {
            return; // Avoid recursive flush calls during event propagation
        }

        try {
            $this->isFlushing = true;
            $this->unitOfWork->computeChangesets();

            CheckChangesetsAndFireEvents:
            $hash = $this->unitOfWork->hash;

            foreach ($this->unitOfWork->getPendingInserts() as $object) {
                $this->firePrePersistEvent($object);
            }

            foreach ($this->unitOfWork->getPendingUpdates() as $object) {
                $this->firePreUpdateEvent($object);
            }

            foreach ($this->unitOfWork->getPendingDeletes() as $object) {
                $this->firePreRemoveEvent($object);
            }

            // Check if changesets have changed during events
            $this->unitOfWork->computeChangesets();
            if ($this->unitOfWork->hash !== $hash) {
                goto CheckChangesetsAndFireEvents;
            }

            $this->transport->flushPendingOperations($this->unitOfWork);
            $this->identities->attach(...$this->unitOfWork->getPendingUpserts());
            $this->identities->detach(...$this->unitOfWork->getPendingDeletes());
            $this->firePostFlushEvents();
            $this->resetUnitOfWork();
        } finally {
            $this->isFlushing = false;
        }
    }

    final public function find(string $className, mixed $id): ?object
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
    final public function factory(mixed $document, ClassMetadataInterface $classMetadata): object
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

    private function firePrePersistEvent(object $object): void
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

    private function firePreUpdateEvent(object $object): void
    {
        if (UnitOfWork::UPDATE !== $this->unitOfWork->getPendingOperation($object)) {
            return;
        }
        if ($this->unitOfWork->hasFiredEvent($object, PreUpdateEvent::class)) {
            return;
        }
        $event = new PreUpdateEvent($object, $this);
        $this->eventDispatcher->dispatch($event);
        $this->unitOfWork->registerFiredEvent($object, PreUpdateEvent::class);
    }

    private function firePreRemoveEvent(object $object): void
    {
        if (UnitOfWork::DELETE !== $this->unitOfWork->getPendingOperation($object)) {
            return;
        }
        if ($this->unitOfWork->hasFiredEvent($object, PreRemoveEvent::class)) {
            return;
        }
        $event = new PreRemoveEvent($object, $this);
        $this->eventDispatcher->dispatch($event);
        $this->unitOfWork->registerFiredEvent($object, PreRemoveEvent::class);
    }

    private function firePostFlushEvents(): void
    {
        $map = [
            PrePersistEvent::class => PostPersistEvent::class,
            PreUpdateEvent::class => PostUpdateEvent::class,
            PreRemoveEvent::class => PostRemoveEvent::class,
        ];
        foreach ($this->unitOfWork->firedEvents as $object => $events) {
            foreach ($events as $eventClass) {
                $targetEventClass = $map[$eventClass];
                $event = new $targetEventClass($object, $this);
                $this->eventDispatcher->dispatch($event);
            }
        }
    }

    private function resetUnitOfWork(): void
    {
        $this->unitOfWork = new UnitOfWork($this);
    }
}
