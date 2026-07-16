<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Negation;
use Honey\ODM\Core\Criteria\Operator;

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
        'isNull' => ['isNull', [], Operator::IS_NULL, null],
        'isNotNull' => ['isNotNull', [], Operator::IS_NOT_NULL, null],
    ]);
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
