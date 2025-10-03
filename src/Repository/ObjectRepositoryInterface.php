<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

/**
 * @template F of mixed
 * @template O of object
 */
interface ObjectRepositoryInterface
{
    /**
     * @param F $criteria
     *
     * @return ResultInterface<O>
     */
    public function findBy(mixed $criteria): ResultInterface;

    /**
     * @return ResultInterface<O>
     */
    public function findAll(): ResultInterface;

    /**
     * @param F $criteria
     *
     * @return O|null
     */
    public function findOneBy(mixed $criteria): ?object;

    /**
     * @return O|null
     */
    public function find(mixed $id): ?object;
}
