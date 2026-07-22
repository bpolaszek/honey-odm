<?php

declare(strict_types=1);

namespace Honey\ODM\Core;

use Closure;
use InvalidArgumentException;
use Stringable;
use Throwable;
use WeakMap;

use function get_debug_type;
use function is_int;
use function is_string;
use function sprintf;

function throws(Closure $fn): bool
{
    try {
        $fn();

        return false;
    } catch (Throwable) {
        return true;
    }
}

/**
 * Normalizes a document id into an array key, so identity maps and keyed stores agree on it.
 *
 * @throws InvalidArgumentException when the id is neither an int, a string, nor stringable
 */
function id_to_array_key(mixed $id): int|string
{
    return match (true) {
        is_int($id), is_string($id) => $id,
        $id instanceof Stringable => (string) $id,
        default => throw new InvalidArgumentException(
            sprintf('An id must be an int, a string or a Stringable, got `%s`.', get_debug_type($id)),
        ),
    };
}

/**
 * @internal
 *
 * @template TKey of object
 * @template TValue
 *
 * @param WeakMap<TKey, TValue> $weakmap
 *
 * @return iterable<TKey>
 */
function weakmap_objects(WeakMap $weakmap): iterable
{
    foreach ($weakmap as $key => $value) {
        yield $key;
    }
}
