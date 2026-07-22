<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

/**
 * Compares an object property (by its PHP property name) against a value.
 */
final readonly class Comparison implements ExpressionInterface
{
    public function __construct(
        public string $property,
        public Operator $operator,
        public mixed $value = null,
    ) {
    }
}
