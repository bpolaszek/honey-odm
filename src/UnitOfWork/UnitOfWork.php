<?php

declare(strict_types=1);

namespace Honey\ODM\Core\UnitOfWork;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Misc\UniqueList;
use SplObjectStorage;
use WeakMap;

use function BenTools\IterableFunctions\iterable;
use function Honey\ODM\Core\weakmap_objects;
use function in_array;

final class UnitOfWork
{
    public const int NONE = 0;
    public const int DELETE = 1;
    public const int CREATE = 2;
    public const int UPDATE = 3;

    private readonly SplObjectStorage $scheduled;

    /**
     * @var WeakMap<object, Changeset>
     */
    private WeakMap $changesets;

    /**
     * @var WeakMap<object, int>
     */
    private WeakMap $pendingOperations;

    /**
     * @var WeakMap<object, UniqueList<string>>
     */
    public private(set) WeakMap $firedEvents;
    public private(set) string $hash;

    public function __construct(
        public readonly ObjectManager $objectManager,
    ) {
        $this->scheduled = new SplObjectStorage();
        $this->changesets = new WeakMap();
        $this->pendingOperations = new WeakMap();
        $this->firedEvents = new WeakMap();
    }

    public function scheduleUpsert(object $object, object ...$objects): void
    {
        foreach ([$object, ...$objects] as $object) {
            $operation = $this->objectManager->identities->contains($object) ? self::UPDATE : self::CREATE;
            $this->scheduled->attach($object);
            $this->pendingOperations[$object] = $operation;
        }
    }

    public function scheduleDeletion(object $object, object ...$objects): void
    {
        foreach ([$object, ...$objects] as $object) {
            $this->scheduled->attach($object);
            $this->pendingOperations[$object] = self::DELETE;
        }
    }

    public function computeChangesets(): void
    {
        $this->changesets = new WeakMap();
        $this->hash = '';
        foreach ($this->objectManager->identities as $object) {
            if (self::DELETE === $this->getPendingOperation($object)) {
                continue;
            }
            $changeset = $this->objectManager->identities->computeChangeset($object);
            if ([] !== $changeset->changedProperties) {
                $this->changesets[$object] = $changeset;
                $this->scheduleUpsert($object);
            }
        }

        foreach ($this->getPendingUpserts() as $object) {
            $changeset = $this->objectManager->identities->computeChangeset($object);
            if ([] !== $changeset->changedProperties) {
                $this->changesets[$object] = $changeset;
                $this->hash = hash('xxh32', $this->hash . serialize($changeset));
            }
        }

        foreach ($this->getPendingDeletes() as $object) {
            $this->hash = hash('xxh32', $this->hash . spl_object_hash($object));
        }
    }

    /**
     * @return iterable<object>
     */
    public function getChangedObjects(): iterable
    {
        return weakmap_objects($this->changesets);
    }

    /**
     * @return iterable<object>
     */
    public function getPendingUpserts(): iterable
    {
        return iterable(weakmap_objects($this->pendingOperations))->filter(
            fn (object $object) => in_array($this->pendingOperations[$object], [self::CREATE, self::UPDATE], true),
        );
    }

    /**
     * @return iterable<object>
     */
    public function getPendingInserts(): iterable
    {
        return iterable(weakmap_objects($this->pendingOperations))->filter(
            fn (object $object) => self::CREATE === $this->pendingOperations[$object],
        );
    }

    /**
     * @return iterable<object>
     */
    public function getPendingUpdates(): iterable
    {
        return iterable(weakmap_objects($this->pendingOperations))->filter(
            fn (object $object) => self::UPDATE === $this->pendingOperations[$object],
        );
    }

    /**
     * @return iterable<object>
     */
    public function getPendingDeletes(): iterable
    {
        return iterable(weakmap_objects($this->pendingOperations))->filter(
            fn (object $object) => self::DELETE === $this->pendingOperations[$object],
        );
    }

    public function getPendingOperation(object $object): int
    {
        return $this->pendingOperations[$object] ?? self::NONE;
    }

    public function registerFiredEvent(object $object, string $eventClass): void
    {
        $this->firedEvents[$object] ??= new UniqueList();
        $this->firedEvents[$object][] = $eventClass;
    }

    public function hasFiredEvent(object $object, string $eventClass): bool
    {
        if (!isset($this->firedEvents[$object])) {
            return false;
        }

        return in_array($eventClass, $this->firedEvents[$object]->toArray(), true);
    }
}
