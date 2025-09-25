<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit;

use function describe;
use function Honey\ODM\Core\throws;

describe('throws', function () {
    it('returns true when callback throws an exception', function () {
        expect(throws(fn () => throw new \RuntimeException()))->toBeTrue();
    });
    it('returns false otherwise', function () {
        expect(throws(fn () => null))->toBeFalse();
    });
});
