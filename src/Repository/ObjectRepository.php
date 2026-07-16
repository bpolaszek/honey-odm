<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Manager\ObjectManager;

/**
 * Generic, platform-agnostic repository.
 *
 * Implementations may provide their own repositories to expose native query capabilities.
 *
 * @template TObject of object
 *
 * @implements ObjectRepositoryInterface<TObject>
 */
final readonly class ObjectRepository implements ObjectRepositoryInterface
{
    /**
     * @param class-string<TObject> $className
     */
    public function __construct(
        public ObjectManager $objectManager,
        public string $className,
    ) {
    }

    public function findBy(Criteria|array|null $criteria): iterable
    {
        $criteria = self::resolveCriteria($criteria);
        $classMetadata = $this->objectManager->getClassMetadata($this->className);

        foreach ($this->objectManager->transport->retrieveDocuments($classMetadata, $criteria) as $document) {
            yield $this->objectManager->factory($document, $this->className);
        }
    }

    public function findAll(): iterable
    {
        return $this->findBy(null);
    }

    public function findOneBy(Criteria|array $criteria): ?object
    {
        $criteria = clone self::resolveCriteria($criteria);
        $criteria->limit(1);

        foreach ($this->findBy($criteria) as $object) {
            return $object;
        }

        return null;
    }

    public function find(mixed $id): ?object
    {
        return $this->objectManager->find($this->className, $id);
    }

    /**
     * @param Criteria|array<string, mixed>|null $criteria
     */
    private static function resolveCriteria(Criteria|array|null $criteria): Criteria
    {
        return match (true) {
            $criteria instanceof Criteria => $criteria,
            default => Criteria::fromArray($criteria ?? []),
        };
    }
}
