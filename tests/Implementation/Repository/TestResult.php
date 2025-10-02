<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Repository;

use Honey\ODM\Core\Repository\ResultInterface;
use IteratorAggregate;
use Traversable;

final readonly class TestResult implements ResultInterface, IteratorAggregate
{
    public function __construct(public array $objects)
    {
    }

    public function getIterator(): Traversable
    {
        yield from $this->objects;
    }

    public function count(): int
    {
        return count($this->objects);
    }
}
