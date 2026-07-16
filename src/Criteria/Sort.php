<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use SortDirection;

/**
 * Sorts by an object property (by its PHP property name).
 */
final readonly class Sort
{
    public function __construct(
        public string $property,
        public SortDirection $direction = SortDirection::Ascending,
    ) {
    }
}
