<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\UnitOfWork\Changeset;
use IteratorAggregate;
use SplObjectStorage;
use Traversable;
use WeakMap;
use WeakReference;

use function Honey\ODM\Core\id_to_array_key;

/**
 * @internal
 *
 * @implements IteratorAggregate<int, object>
 */
final class Identities implements IteratorAggregate
{
    /**
     * @var SplObjectStorage<object, int|string>
     */
    private SplObjectStorage $storage;

    /**
     * @var WeakMap<object, array<string, mixed>>
     */
    private WeakMap $rememberedStates;

    /**
     * @var array<string, array<int|string, WeakReference<object>>>
     */
    private array $idsToObjects = [];

    /**
     * @var WeakMap<object, int|string>
     */
    private WeakMap $objectsToIds;

    public function __construct(
        private readonly ObjectManager $objectManager,
    ) {
        $this->storage = new SplObjectStorage();
        $this->rememberedStates = new WeakMap();
        $this->objectsToIds = new WeakMap();
    }

    public function attach(object $object, mixed $id): void
    {
        $id = $this->resolveId($id);
        $this->storage[$object] = $id;
        $this->idsToObjects[$object::class][$id] = WeakReference::create($object);
        $this->objectsToIds[$object] = $id;
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
            $id = $this->objectsToIds[$object] ?? null;
            if (null === $id) {
                continue;
            }
            unset($this->storage[$object]);
            unset($this->objectsToIds[$object]);
            unset($this->idsToObjects[$object::class][$id]);
            unset($this->rememberedStates[$object]);
        }
    }

    public function contains(object $object): bool
    {
        return isset($this->storage[$object]);
    }

    public function containsId(string $className, mixed $id): bool
    {
        $id = $this->resolveId($id);

        return isset($this->idsToObjects[$className][$id]);
    }

    public function getObject(string $className, mixed $id): ?object
    {
        $id = $this->resolveId($id);

        return $this->idsToObjects[$className][$id]?->get();
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

    private function resolveId(mixed $id): int|string
    {
        return id_to_array_key($id);
    }
}
