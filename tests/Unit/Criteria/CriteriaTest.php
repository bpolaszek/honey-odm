<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Sort;
use InvalidArgumentException;
use SortDirection;

use function expect;
use function Honey\ODM\Core\Criteria\field;

describe('Criteria', function () {
    it('starts empty', function () {
        $criteria = Criteria::create();

        expect($criteria->where)->toBeNull()
            ->and($criteria->orderBy)->toBe([])
            ->and($criteria->search)->toBeNull()
            ->and($criteria->limit)->toBeNull()
            ->and($criteria->offset)->toBe(0);
    });

    it('stores a search term', function () {
        $criteria = Criteria::create()->search('gatsby');

        expect($criteria->search)->toBe('gatsby');
    });

    it('sets a single expression with where()', function () {
        $expression = field('name')->equals('foo');
        $criteria = Criteria::create()->where($expression);

        expect($criteria->where)->toBe($expression);
    });

    it('AND-combines multiple expressions passed to where()', function () {
        $a = field('name')->equals('foo');
        $b = field('year')->greaterThan(2020);
        $criteria = Criteria::create()->where($a, $b);

        expect($criteria->where)->toBeInstanceOf(CompositeExpression::class)
            ->and($criteria->where->operator)->toBe(LogicalOperator::AND)
            ->and($criteria->where->expressions)->toBe([$a, $b]);
    });

    it('replaces the current filter with where()', function () {
        $a = field('name')->equals('foo');
        $b = field('name')->equals('bar');
        $criteria = Criteria::create()->where($a)->where($b);

        expect($criteria->where)->toBe($b);
    });

    it('AND-combines with andWhere()', function () {
        $a = field('name')->equals('foo');
        $b = field('year')->greaterThan(2020);

        $criteria = Criteria::create()->andWhere($a);
        expect($criteria->where)->toBe($a);

        $criteria->andWhere($b);
        expect($criteria->where)->toBeInstanceOf(CompositeExpression::class)
            ->and($criteria->where->operator)->toBe(LogicalOperator::AND)
            ->and($criteria->where->expressions)->toBe([$a, $b]);
    });

    it('OR-combines with orWhere()', function () {
        $a = field('name')->equals('foo');
        $b = field('name')->equals('bar');

        $criteria = Criteria::create()->orWhere($a);
        expect($criteria->where)->toBe($a);

        $criteria->orWhere($b);
        expect($criteria->where)->toBeInstanceOf(CompositeExpression::class)
            ->and($criteria->where->operator)->toBe(LogicalOperator::OR)
            ->and($criteria->where->expressions)->toBe([$a, $b]);
    });

    it('accumulates sorts', function () {
        $criteria = Criteria::create()
            ->orderBy('name')
            ->orderBy('id', 'asc')
            ->orderBy('year', 'desc')
            ->orderBy('rating', SortDirection::Descending);

        expect($criteria->orderBy)->toEqual([
            new Sort('name', SortDirection::Ascending),
            new Sort('id', SortDirection::Ascending),
            new Sort('year', SortDirection::Descending),
            new Sort('rating', SortDirection::Descending),
        ]);
    });

    it('rejects invalid sort directions', function () {
        Criteria::create()->orderBy('name', 'sideways');
    })->throws(InvalidArgumentException::class, 'Invalid sort direction `sideways`.');

    it('stores limit and offset', function () {
        $criteria = Criteria::create()->limit(10)->offset(20);

        expect($criteria->limit)->toBe(10)
            ->and($criteria->offset)->toBe(20);
    });
});
