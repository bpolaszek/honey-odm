<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use Honey\ODM\Core\Criteria\Geo\BoundingBox;
use Honey\ODM\Core\Criteria\Geo\Coordinates;
use Honey\ODM\Core\Criteria\Geo\Radius;

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

    public function endsWith(string $value): Comparison
    {
        return new Comparison($this->property, Operator::ENDS_WITH, $value);
    }

    /**
     * Matches when the field holds *every* given value, as opposed to `in()`, which matches any of them.
     *
     * @param list<mixed> $values
     */
    public function hasAll(array $values): Comparison
    {
        return new Comparison($this->property, Operator::HAS_ALL, $values);
    }

    public function isNull(): Comparison
    {
        return new Comparison($this->property, Operator::IS_NULL);
    }

    public function isNotNull(): Comparison
    {
        return new Comparison($this->property, Operator::IS_NOT_NULL);
    }

    /**
     * Matches when the field is present in the document, whatever its value - including null.
     */
    public function exists(): Comparison
    {
        return new Comparison($this->property, Operator::EXISTS);
    }

    /**
     * Matches when the field holds an empty value: null, an empty string, or an empty array / object.
     */
    public function isEmpty(): Comparison
    {
        return new Comparison($this->property, Operator::IS_EMPTY);
    }

    /**
     * Matches values within the given interval. Either bound may be null, but not both.
     */
    public function between(
        mixed $left,
        mixed $right,
        bool $includeLeft = true,
        bool $includeRight = true,
    ): Comparison {
        return new Comparison(
            $this->property,
            Operator::BETWEEN,
            new Range($left, $right, $includeLeft, $includeRight),
        );
    }

    /**
     * Matches points located within $meters of the given center.
     */
    public function withinGeoRadius(float $latitude, float $longitude, float $meters): Comparison
    {
        return new Comparison(
            $this->property,
            Operator::WITHIN_GEO_RADIUS,
            new Radius(new Coordinates($latitude, $longitude), $meters),
        );
    }

    /**
     * Matches points located within the box drawn from its south-west corner to its north-east one.
     */
    public function withinGeoBoundingBox(
        float $southWestLatitude,
        float $southWestLongitude,
        float $northEastLatitude,
        float $northEastLongitude,
    ): Comparison {
        return new Comparison(
            $this->property,
            Operator::WITHIN_GEO_BOUNDING_BOX,
            new BoundingBox(
                new Coordinates($southWestLatitude, $southWestLongitude),
                new Coordinates($northEastLatitude, $northEastLongitude),
            ),
        );
    }
}
