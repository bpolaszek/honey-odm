<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

use Honey\ODM\Core\Criteria\Criteria;

/**
 * @template TObject of object
 */
interface ObjectRepositoryInterface
{
    /**
     * @param Criteria|array<string, mixed>|null $criteria An array is interpreted as equality filters, AND-combined
     *
     * @return iterable<TObject>
     */
    public function findBy(Criteria|array|null $criteria): iterable;

    /**
     * @return iterable<TObject>
     */
    public function findAll(): iterable;

    /**
     * @param Criteria|array<string, mixed> $criteria
     *
     * @return TObject|null
     */
    public function findOneBy(Criteria|array $criteria): ?object;

    /**
     * @return TObject|null
     */
    public function find(mixed $id): ?object;
}
