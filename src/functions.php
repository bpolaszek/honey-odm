<?php

declare(strict_types=1);

namespace Honey\ODM\Core;

use Closure;
use Throwable;

function throws(Closure $fn): bool
{
    try {
        $fn();
        return false;
    } catch (Throwable) {
        return true;
    }
}
