<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;

use function expect;
use function Honey\ODM\Core\Criteria\field;

describe('UnsupportedExpressionException', function () {
    it('describes an unsupported expression', function () {
        $exception = UnsupportedExpressionException::expression(field('foo')->equals('bar'));

        expect($exception->getMessage())->toContain('Comparison');
    });

    it('describes an unsupported operator', function () {
        $exception = UnsupportedExpressionException::operator(Operator::CONTAINS);

        expect($exception->getMessage())->toContain('CONTAINS');
    });

    it('describes an unsupported feature', function () {
        $exception = UnsupportedExpressionException::feature('search');

        expect($exception->getMessage())->toContain('search');
    });
});
