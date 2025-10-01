<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\UnitOfWork\Changeset;
use IteratorAggregate;
use SplObjectStorage;
use Traversable;
use WeakMap;

/**
 * @internal
 */
final class Identities implements IteratorAggregate
{
    private SplObjectStorage $storage;

    /**
     * @var WeakMap<object, array<string, mixed>>
     */
    private WeakMap $rememberedStates;

    public function __construct(
        private readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        private readonly DocumentMapperInterface $mapper,
    ) {
        $this->storage = new SplObjectStorage();
        $this->rememberedStates = new WeakMap();
    }

    public function attach(object $object): void
    {
        $id = $this->classMetadataRegistry->getIdFromObject($object);
        $this->storage->attach($object, $id);
    }

    public function rememberState(object $object, array $document): void
    {
        $this->rememberedStates[$object] = $document;
    }

    public function forgetState(object $object): void
    {
        unset($this->rememberedStates[$object]);
    }

    public function detach(object $object): void
    {
        if ($this->storage->contains($object)) {
            $this->storage->detach($object);
        }
    }

    public function contains(object $object): bool
    {
        return $this->storage->contains($object);
    }

    public function containsId(string $className, mixed $id): bool
    {
        return null !== $this->getObject($className, $id);
    }

    public function getObject(string $className, mixed $id): ?object
    {
        foreach ($this->storage as $object) {
            $objectId = $this->storage->getInfo();
            if (($object::class === $className) && 0 === ($objectId <=> $id)) {
                return $object;
            }
        }

        return null;
    }

    public function computeChangeset(object $object, ?array $document = null): Changeset
    {
        $classMetadata = $this->classMetadataRegistry->getClassMetadata($object::class);
        $document ??= $this->mapper->objectToDocument($classMetadata, $object);
        $rememberedState = $this->rememberedStates[$object] ?? [];

        return new Changeset($document, $rememberedState);
    }

    public function getIterator(): Traversable
    {
        return $this->storage;
    }
}
