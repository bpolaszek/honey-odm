<?php

declare(strict_types=1);

namespace Honey\ODM\Core\UnitOfWork;

use Honey\ODM\Core\Manager\ObjectManagerInterface;
use SplObjectStorage;
use WeakMap;

use function BenTools\IterableFunctions\iterable;
use function Honey\ODM\Core\weakmap_objects;
use function in_array;

final class UnitOfWork
{
    private const int DELETE = 0;
    private const int CREATE = 1;
    private const int UPDATE = 2;

    private readonly SplObjectStorage $scheduled;
    private WeakMap $changesets;
    public private(set) string $hash;

    public function __construct(
        public readonly ObjectManagerInterface $objectManager,
    ) {
        $this->scheduled = new SplObjectStorage();
    }

    public function scheduleUpsert(object $object, object ...$objects): void
    {
        foreach ([$object, ...$objects] as $object) {
            $operation = $this->objectManager->identities->contains($object) ? self::UPDATE : self::CREATE;
            $this->scheduled->attach($object, $operation);
        }
    }

    public function scheduleDeletion(object $object, object ...$objects): void
    {
        foreach ([$object, ...$objects] as $object) {
            $this->scheduled->attach($object, self::DELETE);
        }
    }

    public function computeChangesets(): void
    {
        $this->changesets = new WeakMap();
        $this->hash = '';
        foreach ($this->objectManager->identities as $object) {
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

        foreach ($this->getPendingDeletions() as $object) {
            $this->hash = hash('xxh32', $this->hash . spl_object_hash($object));
        }
    }

    public function getChangedObjects(): iterable
    {
        return weakmap_objects($this->changesets);
    }

    public function getPendingUpserts(): iterable
    {
        return iterable($this->scheduled)->filter(
            fn (object $object) => in_array($this->scheduled->getInfo(), [self::CREATE, self::UPDATE], true),
        );
    }

    public function getPendingDeletions(): iterable
    {
        return iterable($this->scheduled)->filter(
            fn (object $object) => self::DELETE === $this->scheduled->getInfo(),
        );
    }
}
