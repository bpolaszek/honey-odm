<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Geo\BoundingBox;
use Honey\ODM\Core\Criteria\Geo\Coordinates;
use Honey\ODM\Core\Criteria\Geo\Radius;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Negation;
use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\Range;

use function expect;
use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

describe('Field', function () {
    it('builds comparisons', function (string $method, array $arguments, Operator $expectedOperator, mixed $expectedValue) {
        $comparison = field('foo')->{$method}(...$arguments);

        expect($comparison)->toBeInstanceOf(Comparison::class)
            ->and($comparison->property)->toBe('foo')
            ->and($comparison->operator)->toBe($expectedOperator)
            ->and($comparison->value)->toBe($expectedValue);
    })->with([
        'equals' => ['equals', ['bar'], Operator::EQUALS, 'bar'],
        'notEquals' => ['notEquals', ['bar'], Operator::NOT_EQUALS, 'bar'],
        'greaterThan' => ['greaterThan', [42], Operator::GREATER_THAN, 42],
        'greaterThanOrEquals' => ['greaterThanOrEquals', [42], Operator::GREATER_THAN_OR_EQUALS, 42],
        'lessThan' => ['lessThan', [42], Operator::LESS_THAN, 42],
        'lessThanOrEquals' => ['lessThanOrEquals', [42], Operator::LESS_THAN_OR_EQUALS, 42],
        'in' => ['in', [['a', 'b']], Operator::IN, ['a', 'b']],
        'notIn' => ['notIn', [['a', 'b']], Operator::NOT_IN, ['a', 'b']],
        'contains' => ['contains', ['bar'], Operator::CONTAINS, 'bar'],
        'startsWith' => ['startsWith', ['bar'], Operator::STARTS_WITH, 'bar'],
        'endsWith' => ['endsWith', ['bar'], Operator::ENDS_WITH, 'bar'],
        'hasAll' => ['hasAll', [['a', 'b']], Operator::HAS_ALL, ['a', 'b']],
        'isNull' => ['isNull', [], Operator::IS_NULL, null],
        'isNotNull' => ['isNotNull', [], Operator::IS_NOT_NULL, null],
        'exists' => ['exists', [], Operator::EXISTS, null],
        'isEmpty' => ['isEmpty', [], Operator::IS_EMPTY, null],
    ]);

    it('builds a range comparison', function () {
        $comparison = field('price')->between(10, 100, includeRight: false);

        expect($comparison->operator)->toBe(Operator::BETWEEN)
            ->and($comparison->value)->toEqual(new Range(10, 100, true, false));
    });

    it('builds a geo radius comparison', function () {
        $comparison = field('coordinates')->withinGeoRadius(48.8566, 2.3522, 5000);

        expect($comparison->operator)->toBe(Operator::WITHIN_GEO_RADIUS)
            ->and($comparison->value)->toEqual(new Radius(new Coordinates(48.8566, 2.3522), 5000.0));
    });

    it('builds a geo bounding box comparison, from the south-west corner to the north-east one', function () {
        $comparison = field('coordinates')->withinGeoBoundingBox(48.80, 2.22, 48.90, 2.47);

        expect($comparison->operator)->toBe(Operator::WITHIN_GEO_BOUNDING_BOX)
            ->and($comparison->value)->toEqual(
                new BoundingBox(new Coordinates(48.80, 2.22), new Coordinates(48.90, 2.47)),
            );
    });
});

describe('CompositeExpression', function () {
    it('combines expressions with AND', function () {
        $a = field('foo')->equals('bar');
        $b = field('baz')->isNull();
        $composite = CompositeExpression::and($a, $b);

        expect($composite->operator)->toBe(LogicalOperator::AND)
            ->and($composite->expressions)->toBe([$a, $b]);
    });

    it('combines expressions with OR', function () {
        $a = field('foo')->equals('bar');
        $b = field('baz')->isNull();
        $composite = CompositeExpression::or($a, $b);

        expect($composite->operator)->toBe(LogicalOperator::OR)
            ->and($composite->expressions)->toBe([$a, $b]);
    });
});

describe('Negation', function () {
    it('negates an expression', function () {
        $comparison = field('foo')->equals('bar');

        expect(not($comparison))->toBeInstanceOf(Negation::class)
            ->and(not($comparison)->expression)->toBe($comparison);
    });
});
