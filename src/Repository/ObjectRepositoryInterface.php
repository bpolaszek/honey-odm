<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

/**
 * @template C
 * @template O of object
 */
interface ObjectRepositoryInterface
{
    /**
     * @param C $criteria
     *
     * @return ResultInterface<O>
     */
    public function findBy(mixed $criteria): ResultInterface;

    /**
     * @return ResultInterface<O>
     */
    public function findAll(): ResultInterface;

    /**
     * @param C $criteria
     *
     * @return O|null
     */
    public function findOneBy(mixed $criteria): ?object;

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object;
}
