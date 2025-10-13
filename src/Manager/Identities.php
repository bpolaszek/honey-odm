<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\UnitOfWork\Changeset;
use IteratorAggregate;
use SplObjectStorage;
use Traversable;
use WeakMap;
use WeakReference;

/**
 * @internal
 *
 * @template TClassMetadata of ClassMetadataInterface
 * @template TPropertyMetadata of PropertyMetadataInterface
 * @template TCriteria of mixed
 *
 * @implements IteratorAggregate<int, object>
 */
final class Identities implements IteratorAggregate
{
    /**
     * @var SplObjectStorage<object, mixed>
     */
    private SplObjectStorage $storage;

    /**
     * @var WeakMap<object, array<string, mixed>>
     */
    private WeakMap $rememberedStates;

    /**
     * @var array<string, array<mixed, WeakReference<object>>>
     */
    private array $ids = [];

    /**
     * @param ObjectManager<TClassMetadata, TPropertyMetadata, TCriteria> $objectManager
     */
    public function __construct(
        private readonly ObjectManager $objectManager,
    ) {
        $this->storage = new SplObjectStorage();
        $this->rememberedStates = new WeakMap();
    }

    public function attach(object $object, mixed $id): void
    {
        $this->storage->attach($object);
        $this->ids[$object::class][$id] = WeakReference::create($object);
    }

    /**
     * @param array<string, mixed> $document
     */
    public function rememberState(object $object, array $document): void
    {
        $this->rememberedStates[$object] = $document;
    }

    public function forgetState(object $object): void
    {
        unset($this->rememberedStates[$object]);
    }

    public function detach(object ...$objects): void
    {
        foreach ($objects as $object) {
            $this->storage->detach($object);
            unset($this->rememberedStates[$object]);
        }
    }

    public function contains(object $object): bool
    {
        return $this->storage->contains($object);
    }

    public function containsId(string $className, mixed $id): bool
    {
        return isset($this->ids[$className][$id]);
    }

    public function getObject(string $className, mixed $id): ?object
    {
        return $this->ids[$className][$id]?->get(); // @phpstan-ignore nullsafe.neverNull
    }

    /**
     * @param array<string, mixed>|null $document
     */
    public function computeChangeset(object $object, ?array $document = null): Changeset
    {
        $classMetadata = $this->objectManager->classMetadataRegistry->getClassMetadata($object::class);
        $context = new MappingContext($classMetadata, $this->objectManager, $object, $document ?? []);
        $document ??= $this->objectManager->documentMapper->objectToDocument($object, [], $context);
        $rememberedState = $this->rememberedStates[$object] ?? [];

        return new Changeset($document, $rememberedState);
    }

    public function getIterator(): Traversable
    {
        return $this->storage;
    }
}
