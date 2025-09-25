<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\UnitOfWork;

use Honey\ODM\Core\UnitOfWork\Changeset;

it('tracks no changes when documents are identical', function () {
    $document = ['name' => 'John', 'age' => 30];
    $changeset = new Changeset($document, $document);

    expect($changeset->changedProperties)->toBe([])
        ->and($changeset->newDocument)->toBe($document)
        ->and($changeset->previousDocument)->toBe($document);
});

it('tracks changed properties when values differ', function () {
    $oldDocument = ['name' => 'John', 'age' => 30];
    $newDocument = ['name' => 'Jane', 'age' => 25];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'name' => ['Jane', 'John'],
        'age' => [25, 30],
    ]);
});

it('tracks added properties in new document', function () {
    $oldDocument = ['name' => 'John'];
    $newDocument = ['name' => 'John', 'age' => 30];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'age' => [30, null],
    ]);
});

it('tracks removed properties from old document', function () {
    $oldDocument = ['name' => 'John', 'age' => 30];
    $newDocument = ['name' => 'John'];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'age' => [null, 30],
    ]);
});

it('handles empty documents', function () {
    $changeset = new Changeset([], []);

    expect($changeset->changedProperties)->toBe([])
        ->and($changeset->newDocument)->toBe([])
        ->and($changeset->previousDocument)->toBe([]);
});

it('handles empty old document', function () {
    $newDocument = ['name' => 'John', 'age' => 30];
    $changeset = new Changeset($newDocument, []);

    expect($changeset->changedProperties)->toBe([
        'name' => ['John', null],
        'age' => [30, null],
    ]);
});

it('handles empty new document', function () {
    $oldDocument = ['name' => 'John', 'age' => 30];
    $changeset = new Changeset([], $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'name' => [null, 'John'],
        'age' => [null, 30],
    ]);
});

it('handles null values correctly', function () {
    $oldDocument = ['name' => null, 'age' => 30];
    $newDocument = ['name' => 'John', 'age' => null];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'name' => ['John', null],
        'age' => [null, 30],
    ]);
});

it('handles different data types', function () {
    $oldDocument = ['value' => '30', 'flag' => 1];
    $newDocument = ['value' => 30, 'flag' => true];
    $changeset = new Changeset($newDocument, $oldDocument);

    // '30' <=> 30 returns 0 (equal), 1 <=> true returns 0 (equal)
    expect($changeset->changedProperties)->toBe([]);
});

it('handles arrays as values', function () {
    $oldDocument = ['tags' => ['php', 'web']];
    $newDocument = ['tags' => ['php', 'programming']];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'tags' => [['php', 'programming'], ['php', 'web']],
    ]);
});

it('handles nested arrays', function () {
    $oldDocument = ['meta' => ['created' => '2023-01-01', 'updated' => null]];
    $newDocument = ['meta' => ['created' => '2023-01-01', 'updated' => '2023-01-02']];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toBe([
        'meta' => [
            ['created' => '2023-01-01', 'updated' => '2023-01-02'],
            ['created' => '2023-01-01', 'updated' => null],
        ],
    ]);
});

it('handles zero values correctly', function () {
    $oldDocument = ['count' => 0, 'balance' => 0.0];
    $newDocument = ['count' => false, 'balance' => ''];
    $changeset = new Changeset($newDocument, $oldDocument);

    // 0 <=> false returns 0 (equal), but 0.0 <=> '' returns 1 (different)
    expect($changeset->changedProperties)->toBe([
        'balance' => ['', 0.0],
    ]);
});

it('handles identical values that compare equal with spaceship operator', function () {
    $oldDocument = ['value' => 0];
    $newDocument = ['value' => false];
    $changeset = new Changeset($newDocument, $oldDocument);

    // Since 0 <=> false returns 0, this should be considered unchanged
    expect($changeset->changedProperties)->toBe([]);
});

it('handles float precision differences', function () {
    $oldDocument = ['price' => 10.1];
    $newDocument = ['price' => 10.10];
    $changeset = new Changeset($newDocument, $oldDocument);

    // These should be considered equal by the spaceship operator
    expect($changeset->changedProperties)->toBe([]);
});

it('handles object comparison', function () {
    $obj1 = (object) ['id' => 1];
    $obj2 = (object) ['id' => 1];
    $oldDocument = ['object' => $obj1];
    $newDocument = ['object' => $obj2];
    $changeset = new Changeset($newDocument, $oldDocument);

    // Objects with same properties are considered equal by spaceship operator
    expect($changeset->changedProperties)->toBe([]);
});

it('preserves original document arrays', function () {
    $oldDocument = ['name' => 'John'];
    $newDocument = ['name' => 'Jane'];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->newDocument)->toBe($newDocument)
        ->and($changeset->previousDocument)->toBe($oldDocument)
        ->and($changeset->newDocument)->not->toBe($changeset->changedProperties);
});

it('stores changed properties in correct format', function () {
    $oldDocument = ['name' => 'John'];
    $newDocument = ['name' => 'Jane'];
    $changeset = new Changeset($newDocument, $oldDocument);

    expect($changeset->changedProperties)->toHaveKey('name')
        ->and($changeset->changedProperties['name'])->toHaveCount(2)
        ->and($changeset->changedProperties['name'][0])->toBe('Jane')
        ->and($changeset->changedProperties['name'][1])->toBe('John');
});
