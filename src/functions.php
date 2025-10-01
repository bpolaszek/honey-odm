<?php

declare(strict_types=1);

namespace Honey\ODM\Core;

use Closure;
use Throwable;
use WeakMap;

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
 * @internal
 */
function weakmap_objects(WeakMap $weakmap): iterable
{
    foreach ($weakmap as $key => $value) {
        yield $key;
    }
}
