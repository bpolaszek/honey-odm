<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\Range;
use InvalidArgumentException;

use function expect;

describe('Range', function () {
    it('is inclusive on both bounds by default', function () {
        $range = new Range(10, 100);

        expect($range->left)->toBe(10)
            ->and($range->right)->toBe(100)
            ->and($range->includeLeft)->toBeTrue()
            ->and($range->includeRight)->toBeTrue();
    });

    it('supports exclusive bounds', function () {
        $range = new Range(10, 100, includeLeft: false, includeRight: false);

        expect($range->includeLeft)->toBeFalse()
            ->and($range->includeRight)->toBeFalse();
    });

    it('accepts an open-ended bound', function (mixed $left, mixed $right) {
        $range = new Range($left, $right);

        expect($range->left)->toBe($left)
            ->and($range->right)->toBe($right);
    })->with([
        'no lower bound' => [null, 100],
        'no upper bound' => [10, null],
    ]);

    it('rejects a range without any bound', function () {
        new Range(null, null);
    })->throws(InvalidArgumentException::class, 'at least one bound');
});
