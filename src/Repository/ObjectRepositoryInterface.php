<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

/**
 * @template TCriteria of mixed
 * @template TObject of object
 */
interface ObjectRepositoryInterface
{
    /**
     * @param TCriteria $criteria
     *
     * @return ResultInterface<TObject>
     */
    public function findBy(mixed $criteria): ResultInterface;

    /**
     * @return ResultInterface<TObject>
     */
    public function findAll(): ResultInterface;

    /**
     * @param TCriteria $criteria
     *
     * @return TObject|null
     */
    public function findOneBy(mixed $criteria): ?object;

    /**
     * @return TObject|null
     */
    public function find(mixed $id): ?object;
}
