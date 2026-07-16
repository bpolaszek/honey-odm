<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Repository;

use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Examples\TestBook;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function expect;
use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

describe('ObjectRepository', function () {
    $transport = new TestTransport();
    $transport->storage['books'] = [
        'A' => ['id' => 'A', 'title' => 'The Tommyknockers', 'author_id' => 1],
        'B' => ['id' => 'B', 'title' => '1984', 'author_id' => 2],
        'C' => ['id' => 'C', 'title' => 'The Holy Bible', 'author_id' => null],
    ];
    $objectManager = new ObjectManager($transport);
    $repository = $objectManager->getRepository(TestBook::class);

    it('finds all objects', function () use ($repository) {
        $books = [...$repository->findAll()];

        expect($books)->toHaveCount(3)
            ->and($books)->each->toBeInstanceOf(TestBook::class);
    });

    it('finds objects by array criteria, using property names', function () use ($repository) {
        $books = [...$repository->findBy(['name' => '1984'])];

        expect($books)->toHaveCount(1)
            ->and($books[0]->id)->toBe('B');
    });

    it('finds objects by criteria', function () use ($repository) {
        $books = [...$repository->findBy(Criteria::create()->where(field('name')->startsWith('The')))];

        expect($books)->toHaveCount(2);
    });

    it('supports OR and negations', function () use ($repository) {
        $books = [...$repository->findBy(
            Criteria::create()
                ->where(not(field('name')->contains('Tommy')))
                ->orWhere(field('name')->equals('1984'), field('author')->isNotNull()),
        )];

        expect($books)->toHaveCount(2);
    });

    it('supports sorting, offset and limit', function () use ($repository) {
        $criteria = Criteria::create()->orderBy('name', 'desc')->offset(1)->limit(1);
        $books = [...$repository->findBy($criteria)];

        expect($books)->toHaveCount(1)
            ->and($books[0]->name)->toBe('The Holy Bible');
    });

    it('supports full-text search', function () use ($repository) {
        $books = [...$repository->findBy(Criteria::create()->search('holy'))];

        expect($books)->toHaveCount(1)
            ->and($books[0]->id)->toBe('C');
    });

    it('finds one object', function () use ($repository) {
        $book = $repository->findOneBy(['name' => '1984']);

        expect($book)->toBeInstanceOf(TestBook::class)
            ->and($book->id)->toBe('B');
    });

    it('returns null when no object matches', function () use ($repository) {
        expect($repository->findOneBy(['name' => 'Nope']))->toBeNull();
    });

    it('leaves the original criteria untouched in findOneBy()', function () use ($repository) {
        $criteria = Criteria::create()->where(field('name')->startsWith('The'));
        $repository->findOneBy($criteria);

        expect($criteria->limit)->toBeNull();
    });

    it('finds an object by its id', function () use ($repository) {
        $book = $repository->find('A');

        expect($book)->toBeInstanceOf(TestBook::class)
            ->and($book->name)->toBe('The Tommyknockers')
            ->and($repository->find('ZZ'))->toBeNull();
    });

    it('complains on unsupported expressions', function () use ($repository) {
        $unsupported = new class implements ExpressionInterface {
        };

        [...$repository->findBy(Criteria::create()->where($unsupported))];
    })->throws(UnsupportedExpressionException::class);
});
