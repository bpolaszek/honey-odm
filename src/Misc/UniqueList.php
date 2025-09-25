<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Misc;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

use function array_key_exists;
use function count;
use function in_array;

/**
 * @internal
 *
 * @template T
 *
 * @implements ArrayAccess<int, T>
 * @implements IteratorAggregate<T>
 */
final class UniqueList implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var T[]
     */
    private array $storage = [];

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->storage);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->storage[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (in_array($value, $this->storage, true)) {
            return;
        }

        if (null !== $offset) {
            throw new InvalidArgumentException('Cannot assign value to specific offset in ' . self::class . ', only append is allowed.');
        }

        $this->storage[] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->storage[$offset]);
    }

    public function getIterator(): Traversable
    {
        yield from $this->storage;
    }

    public function count(): int
    {
        return count($this->storage);
    }

    /**
     * @return T[]
     */
    public function toArray(): array
    {
        return [...$this];
    }
}
