<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

/**
 * Fluent factory for building comparisons on an object property.
 */
final readonly class Field
{
    public function __construct(
        public string $property,
    ) {
    }

    public function equals(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::EQUALS, $value);
    }

    public function notEquals(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::NOT_EQUALS, $value);
    }

    public function greaterThan(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::GREATER_THAN, $value);
    }

    public function greaterThanOrEquals(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::GREATER_THAN_OR_EQUALS, $value);
    }

    public function lessThan(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::LESS_THAN, $value);
    }

    public function lessThanOrEquals(mixed $value): Comparison
    {
        return new Comparison($this->property, Operator::LESS_THAN_OR_EQUALS, $value);
    }

    /**
     * @param list<mixed> $values
     */
    public function in(array $values): Comparison
    {
        return new Comparison($this->property, Operator::IN, $values);
    }

    /**
     * @param list<mixed> $values
     */
    public function notIn(array $values): Comparison
    {
        return new Comparison($this->property, Operator::NOT_IN, $values);
    }

    public function contains(string $value): Comparison
    {
        return new Comparison($this->property, Operator::CONTAINS, $value);
    }

    public function startsWith(string $value): Comparison
    {
        return new Comparison($this->property, Operator::STARTS_WITH, $value);
    }

    public function isNull(): Comparison
    {
        return new Comparison($this->property, Operator::IS_NULL);
    }

    public function isNotNull(): Comparison
    {
        return new Comparison($this->property, Operator::IS_NOT_NULL);
    }
}
