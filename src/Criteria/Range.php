<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use InvalidArgumentException;

/**
 * An interval, with per-side inclusivity. Either bound may be null (open-ended range), but not both.
 */
final readonly class Range
{
    public function __construct(
        public mixed $left,
        public mixed $right,
        public bool $includeLeft = true,
        public bool $includeRight = true,
    ) {
        if (null === $left && null === $right) {
            throw new InvalidArgumentException('A range requires at least one bound.');
        }
    }
}
