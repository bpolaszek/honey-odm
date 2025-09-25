<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Misc;

use Honey\ODM\Core\Misc\UniqueList;

use function array_keys;
use function count;
use function expect;
use function it;

describe('UniqueList', function () {

    it('allows setting values at specific offsets', function () {
        $list = new UniqueList();
        $list[] = 'first';
        $list[] = 'second';

        expect($list[0])->toBe('first')
            ->and($list[1])->toBe('second');
    });

    it('prevents duplicate values', function () {
        $list = new UniqueList();
        $list[] = 'duplicate';
        $list[] = 'unique';
        $list[] = 'duplicate'; // Should be ignored

        expect($list[0])->toBe('duplicate')
            ->and($list[1])->toBe('unique')
            ->and($list[2])->toBeNull()
            ->and(count($list))->toBe(2);
    });

    it('handles strict comparison for duplicates', function () {
        $list = new UniqueList();
        $list[] = 1;
        $list[] = '1';
        $list[] = 1.0;

        expect($list[0])->toBe(1)
            ->and($list[1])->toBe('1')
            ->and($list[2])->toBe(1.0)
            ->and(count($list))->toBe(3);
    });

    it('handles object duplicates correctly', function () {
        $list = new UniqueList();
        $obj1 = new \stdClass();
        $obj1->value = 'test';
        $obj2 = new \stdClass();
        $obj2->value = 'test';

        $list[] = $obj1;
        $list[] = $obj2; // Different object instance
        $list[] = $obj1; // Same object instance - should be ignored

        expect($list[0])->toBe($obj1)
            ->and($list[1])->toBe($obj2)
            ->and($list[2])->toBeNull()
            ->and(count($list))->toBe(2);
    });

    it('checks if offset exists', function () {
        $list = new UniqueList();
        $list[] = 'value';

        expect(isset($list[0]))->toBeTrue()
            ->and(isset($list[1]))->toBeFalse();
    });

    it('returns null for non-existent offsets', function () {
        $list = new UniqueList();

        expect($list[0])->toBeNull()
            ->and($list[999])->toBeNull()
            ->and($list[-1])->toBeNull();
    });

    it('unsets offsets correctly', function () {
        $list = new UniqueList();
        $list[] = 'first';
        $list[] = 'second';

        unset($list[0]);

        expect(isset($list[0]))->toBeFalse()
            ->and($list[0])->toBeNull()
            ->and(isset($list[1]))->toBeTrue()
            ->and($list[1])->toBe('second')
            ->and(count($list))->toBe(1);
    });

    it('counts elements correctly', function () {
        $list = new UniqueList();

        expect(count($list))->toBe(0);

        $list[] = 'first';
        expect(count($list))->toBe(1);

        $list[] = 'second';
        expect(count($list))->toBe(2);

        $list[] = 'first'; // Duplicate - should not increase count
        expect(count($list))->toBe(2);

        unset($list[0]);
        expect(count($list))->toBe(1);
    });

    it('is iterable', function () {
        $list = new UniqueList();
        $list[] = 'first';
        $list[] = 'second';
        $list[] = 'third';

        $values = [];
        foreach ($list as $value) {
            $values[] = $value;
        }

        expect($values)->toBe(['first', 'second', 'third']);
    });

    it('converts to array correctly', function () {
        $list = new UniqueList();
        $list[] = 'first';
        $list[] = 'second';
        $list[] = 'middle';

        $array = $list->toArray();

        expect($array)->toBe(['first', 'second', 'middle'])
            ->and(array_keys($array))->toBe([0, 1, 2]);
    });

    it('handles null values', function () {
        $list = new UniqueList();
        $list[] = null;
        $list[] = 'value';
        $list[] = null; // Duplicate null - should be ignored

        expect($list[0])->toBeNull()
            ->and($list[1])->toBe('value')
            ->and(isset($list[2]))->toBeFalse()
            ->and(count($list))->toBe(2);
    });

    it('handles mixed data types', function () {
        $list = new UniqueList();
        $list[] = 42;
        $list[] = 'string';
        $list[] = true;
        $list[] = false;
        $list[] = 3.14;
        $list[] = [];
        $list[] = new \stdClass();

        expect(count($list))->toBe(7);

        $list[] = 42; // Duplicate integer
        $list[] = 'string'; // Duplicate string
        $list[] = true; // Duplicate boolean

        expect(count($list))->toBe(7); // Should remain 7
    });

    it('handles array duplicates', function () {
        $list = new UniqueList();
        $arr1 = ['a', 'b', 'c'];
        $arr2 = ['a', 'b', 'c'];
        $arr3 = ['a', 'b', 'd'];

        $list[] = $arr1;
        $list[] = $arr2; // Same content but different array instance
        $list[] = $arr3;

        expect(count($list))->toBe(2);
    });


    it('handles boolean false vs null correctly', function () {
        $list = new UniqueList();
        $list[] = false;
        $list[] = null;
        $list[] = 0;
        $list[] = '';

        expect($list[0])->toBe(false)
            ->and($list[1])->toBeNull()
            ->and($list[2])->toBe(0)
            ->and($list[3])->toBe('')
            ->and(count($list))->toBe(4);

        // Test duplicates
        $list[] = false; // Should be ignored
        $list[] = null; // Should be ignored
        expect(count($list))->toBe(4);
    });

    it('works with empty list operations', function () {
        $list = new UniqueList();

        expect(count($list))->toBe(0)
            ->and($list->toArray())->toBe([])
            ->and(isset($list[0]))->toBeFalse();

        $iterations = 0;
        foreach ($list as $item) {
            $iterations++;
        }
        expect($iterations)->toBe(0);
    });

    it('complains when an illegal offset is used', function (mixed $offset) {
        $list = new UniqueList();
        $list[$offset] = 'foo'; // @phpstan-ignore offsetAssign.dimType
    })
        ->with(fn () => [['foo'], [1], [1.5], [true], [false], [[]], [new \stdClass()], ['']])
    ->throws(\InvalidArgumentException::class);
    ;
});
