<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use BenTools\ReflectionPlus\Reflection;
use Closure;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Event\PostLoadEvent;
use Honey\ODM\Core\Event\PostPersistEvent;
use Honey\ODM\Core\Event\PostRemoveEvent;
use Honey\ODM\Core\Event\PostUpdateEvent;
use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Event\PreRemoveEvent;
use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Misc\NullEventDispatcher;
use Honey\ODM\Core\Repository\ObjectRepository;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionException;
use ReflectionProperty;
use Stringable;

use function array_column;
use function array_combine;
use function is_object;

final class ObjectManager
{
    public private(set) Identities $identities;
    public private(set) UnitOfWork $unitOfWork;
    private bool $isFlushing = false;

    /**
     * @var array<class-string, ObjectRepositoryInterface<object>>
     */
    private array $repositories = [];

    /**
     * @param array<string, mixed> $defaultFlushOptions
     * @param (Closure(self, class-string): ObjectRepositoryInterface<object>)|null $repositoryFactory Creates the default repository for a class - implementations may provide their own
     */
    public function __construct(
        public readonly TransportInterface $transport,
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry = new ClassMetadataRegistry(),
        public readonly DocumentMapperInterface $documentMapper = new DocumentMapper(),
        public readonly EventDispatcherInterface $eventDispatcher = new NullEventDispatcher(),
        public private(set) array $defaultFlushOptions = [],
        private readonly ?Closure $repositoryFactory = null,
    ) {
        $this->identities = new Identities($this);
        $this->resetUnitOfWork();
    }

    /**
     * @template O of object
     *
     * @param O|class-string<O> $classNameOrObject
     *
     * @return AsDocument<O>
     */
    final public function getClassMetadata(object|string $classNameOrObject): AsDocument
    {
        return $this->classMetadataRegistry->getClassMetadata(match (is_object($classNameOrObject)) {
            true => $classNameOrObject::class,
            default => $classNameOrObject,
        });
    }

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     * @param ObjectRepositoryInterface<O> $repository
     *
     * @return ObjectRepositoryInterface<O>
     */
    public function registerRepository(
        string $className,
        ObjectRepositoryInterface $repository,
    ): ObjectRepositoryInterface {
        // Ensures the class is properly configured, this will throw an exception otherwise
        $this->classMetadataRegistry->getClassMetadata($className);
        $this->repositories[$className] = $repository;

        return $repository;
    }

    /**
     * @template O of object
     *
     * @param class-string<O> $className
     *
     * @return ObjectRepositoryInterface<O>
     */
    public function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className] // @phpstan-ignore return.type
            ?? $this->registerRepository(
                $className,
                match ($this->repositoryFactory) {
                    null => new ObjectRepository($this, $className),
                    default => ($this->repositoryFactory)($this, $className),
                },
            );
    }

    /**
     * Returns the object's identifier - without forcing the initialization of an uninitialized lazy object,
     * for which the identity map already knows the id.
     *
     * Stringable ids are normalized the way the identity map and the stores expect them (see id_to_array_key()),
     * so that the returned value doesn't depend on whether the object was initialized.
     */
    final public function getIdentifier(object $object): mixed
    {
        if (Reflection::class($object)->isUninitializedLazyObject($object)) {
            $id = $this->identities->getId($object);
            if (null !== $id) {
                return $id;
            }
        }

        $id = $this->classMetadataRegistry->getIdFromObject($object);

        return $id instanceof Stringable ? (string) $id : $id;
    }

    final public function persist(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleUpsert($object, ...$objects);
    }

    final public function remove(object $object, object ...$objects): void
    {
        $this->unitOfWork->scheduleDeletion($object, ...$objects);
    }

    /**
     * @param array<string, mixed> $flushOptions
     */
    final public function flush(array $flushOptions = []): void
    {
        $flushOptions = [
            ...$this->defaultFlushOptions,
            ...$flushOptions,
        ];

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

            $this->transport->flushPendingOperations($this->unitOfWork, $flushOptions);
            foreach ($this->unitOfWork->getPendingUpserts() as $object) {
                $this->identities->attach($object, $this->classMetadataRegistry->getIdFromObject($object));
                $this->identities->rememberCurrentState($object);
            }
            $this->identities->detach(...$this->unitOfWork->getPendingDeletes());
            $this->firePostFlushEvents();
            $this->resetUnitOfWork();
        } finally {
            $this->isFlushing = false;
        }
    }

    final public function clear(): void
    {
        $this->identities = new Identities($this);
        $this->resetUnitOfWork();
    }

    /**
     * @template TObject of object
     *
     * @param class-string<TObject> $className
     *
     * @return TObject|null
     */
    final public function find(string $className, mixed $id): ?object
    {
        if ($this->identities->containsId($className, $id)) {
            return $this->identities->getObject($className, $id); // @phpstan-ignore return.type
        }

        $classMetadata = $this->classMetadataRegistry->getClassMetadata($className);

        $document = $this->transport->retrieveDocumentById($classMetadata, $id);
        if (!$document) {
            return null;
        }

        return $this->factory($document, $className);
    }

    /**
     * @template TObject of object
     *
     * @param array<string, mixed> $document
     * @param class-string<TObject> $className
     *
     * @return TObject
     */
    final public function factory(array $document, string $className, bool $refresh = false): object
    {
        $classMetadata = $this->classMetadataRegistry->getClassMetadata($className);
        $id = $this->classMetadataRegistry->getIdFromDocument($document, $classMetadata->className);
        $className = $classMetadata->className;
        if ($this->identities->containsId($className, $id)) {
            $object = $this->identities->getObject($className, $id);

            if ($refresh) {
                $context = new MappingContext($classMetadata, $this, $object, $document); // @phpstan-ignore argument.type
                $this->documentMapper->documentToObject($document, $object, $context); // @phpstan-ignore argument.type
                $this->identities->rememberCurrentState($object); // @phpstan-ignore argument.type
            }

            return $object; // @phpstan-ignore return.type
        }
        $object = Reflection::class($className)->newLazyGhost(function (object $ghost) use ($document, $classMetadata) {
            $context = new MappingContext($classMetadata, $this, $ghost, $document);
            $object = $this->documentMapper->documentToObject($document, $ghost, $context);
            foreach (Reflection::class($object::class)->getProperties() as $property) {
                if (!$property->isStatic() && !$property->isInitialized($object)) {
                    try {
                        $defaultValue = self::getDefaultValue($property);
                        $property->setValue($object, $defaultValue);
                    } catch (ReflectionException) { // @codeCoverageIgnore
                    }
                }
            }
            $this->identities->rememberCurrentState($object);
            $this->eventDispatcher->dispatch(new PostLoadEvent($object, $this, $document));
        });
        $this->identities->attach($object, $id);

        return $object; // @phpstan-ignore return.type
    }

    private static function getDefaultValue(ReflectionProperty $property): mixed
    {
        if (!$property->isPromoted()) {
            return $property->getDefaultValue(); // @codeCoverageIgnore
        }

        $constructorParameters = $property->getDeclaringClass()->getConstructor()?->getParameters() ?? [];
        $constructorParameters = array_combine(
            array_column($constructorParameters, 'name'),
            $constructorParameters,
        );

        return $constructorParameters[$property->name]->getDefaultValue();
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
