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

    public function notContains(string $value): Negation
    {
        return not($this->contains($value));
    }

    public function startsWith(string $value): Comparison
    {
        return new Comparison($this->property, Operator::STARTS_WITH, $value);
    }

    public function notStartsWith(string $value): Negation
    {
        return not($this->startsWith($value));
    }

    public function endsWith(string $value): Comparison
    {
        return new Comparison($this->property, Operator::ENDS_WITH, $value);
    }

    public function notEndsWith(string $value): Negation
    {
        return not($this->endsWith($value));
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

    /**
     * Matches when the field misses at least one of the given values. To match fields holding none of
     * them, use `notIn()`.
     *
     * @param list<mixed> $values
     */
    public function notHasAll(array $values): Negation
    {
        return not($this->hasAll($values));
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
     * Matches when the field is absent from the document - as opposed to `isNull()`, which requires it.
     */
    public function notExists(): Negation
    {
        return not($this->exists());
    }

    /**
     * Matches when the field holds an empty value: null, an empty string, or an empty array / object.
     */
    public function isEmpty(): Comparison
    {
        return new Comparison($this->property, Operator::IS_EMPTY);
    }

    public function isNotEmpty(): Negation
    {
        return not($this->isEmpty());
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
     * Matches values outside the given interval - including fields holding no value at all.
     */
    public function notBetween(
        mixed $left,
        mixed $right,
        bool $includeLeft = true,
        bool $includeRight = true,
    ): Negation {
        return not($this->between($left, $right, $includeLeft, $includeRight));
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
     * Matches points located further than $meters from the given center - including unlocated ones.
     */
    public function outsideGeoRadius(float $latitude, float $longitude, float $meters): Negation
    {
        return not($this->withinGeoRadius($latitude, $longitude, $meters));
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

    /**
     * Matches points located outside that box - including unlocated ones.
     */
    public function outsideGeoBoundingBox(
        float $southWestLatitude,
        float $southWestLongitude,
        float $northEastLatitude,
        float $northEastLongitude,
    ): Negation {
        return not($this->withinGeoBoundingBox(
            $southWestLatitude,
            $southWestLongitude,
            $northEastLatitude,
            $northEastLongitude,
        ));
    }
}
