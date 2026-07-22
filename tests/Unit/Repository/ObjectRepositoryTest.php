<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Repository;

use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Examples\TestBook;
use Honey\ODM\Core\Tests\Implementation\Examples\TestPlace;
use Honey\ODM\Core\Transport\InMemoryTransport;

use function array_map;
use function expect;
use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

describe('ObjectRepository', function () {
    $transport = new InMemoryTransport();
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

describe('ObjectRepository operators', function () {
    $transport = new InMemoryTransport();
    $transport->storage['places'] = [
        'eiffel' => [
            'id' => 'eiffel',
            'label' => 'Eiffel Tower',
            'tags' => ['monument', 'paris'],
            '_geo' => ['lat' => 48.8584, 'lng' => 2.2945],
            'rating' => 5,
            'summary' => 'The iron lady',
        ],
        'louvre' => [
            'id' => 'louvre',
            'label' => 'Louvre Museum',
            'tags' => ['museum', 'paris'],
            '_geo' => ['lat' => 48.8606, 'lng' => 2.3376],
            'rating' => 5,
            'summary' => '',
        ],
        'colosseum' => [
            'id' => 'colosseum',
            'label' => 'Colosseum',
            'tags' => ['monument', 'rome'],
            '_geo' => ['lat' => 41.8902, 'lng' => 12.4922],
            'rating' => 4,
            'summary' => null,
        ],
        'atoll' => [ // No `summary` key at all, and no rating
            'id' => 'atoll',
            'label' => 'Funafuti Atoll',
            'tags' => ['nature'],
            '_geo' => ['lat' => -8.52, 'lng' => 179.19],
            'rating' => null,
        ],
    ];
    $repository = new ObjectManager($transport)->getRepository(TestPlace::class);

    /** @param iterable<TestPlace> $places */
    $ids = static fn (iterable $places): array => array_map(
        static fn (TestPlace $place) => $place->id,
        [...$places],
    );

    it('filters on a suffix', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('name')->endsWith('Museum')))))
            ->toBe(['louvre']);
    });

    it('filters on the presence of a field, regardless of its value', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('summary')->exists()))))
            ->toBe(['eiffel', 'louvre', 'colosseum']);
    });

    it('distinguishes a present-but-null field from a missing one', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('summary')->isNotNull()))))
            ->toBe(['eiffel', 'louvre']);
    });

    it('filters on emptiness', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('summary')->isEmpty()))))
            ->toBe(['louvre', 'colosseum', 'atoll']);
    });

    it('filters on a field holding every given value', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('tags')->hasAll(['monument', 'paris'])))))
            ->toBe(['eiffel']);
    });

    it('filters on a field holding any of the given values', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('tags')->in(['monument', 'paris'])))))
            ->toBe(['eiffel', 'louvre', 'colosseum']);
    });

    it('filters on an inclusive range', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('rating')->between(4, 5)))))
            ->toBe(['eiffel', 'louvre', 'colosseum']);
    });

    it('filters on an exclusive bound', function () use ($repository, $ids) {
        $criteria = Criteria::create()->where(field('rating')->between(4, 5, includeRight: false));

        expect($ids($repository->findBy($criteria)))->toBe(['colosseum']);
    });

    it('filters on an open-ended range, never matching a null value', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('rating')->between(null, 4)))))
            ->toBe(['colosseum']);
    });

    it('filters on a field missing at least one of the given values', function () use ($repository, $ids) {
        $criteria = Criteria::create()->where(field('tags')->notHasAll(['monument', 'paris']));

        expect($ids($repository->findBy($criteria)))->toBe(['louvre', 'colosseum', 'atoll']);
    });

    it('filters on non-emptiness', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('summary')->isNotEmpty()))))
            ->toBe(['eiffel']);
    });

    it('filters on a left-bounded range', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->where(field('rating')->between(5, null)))))
            ->toBe(['eiffel', 'louvre']);
    });

    it('keeps the storage order when documents compare equal on every sort', function () use ($repository, $ids) {
        expect($ids($repository->findBy(Criteria::create()->orderBy('rating', 'desc'))))
            ->toBe(['eiffel', 'louvre', 'colosseum', 'atoll']);
    });

    it('filters on a geo radius', function () use ($repository, $ids) {
        $within = static fn (float $meters) => $ids($repository->findBy(
            Criteria::create()->where(field('coordinates')->withinGeoRadius(48.8566, 2.3522, $meters)),
        ));

        expect($within(5000))->toBe(['eiffel', 'louvre'])
            ->and($within(2000))->toBe(['louvre']);
    });

    it('filters on a geo bounding box', function () use ($repository, $ids) {
        $criteria = Criteria::create()
            ->where(field('coordinates')->withinGeoBoundingBox(48.80, 2.22, 48.90, 2.47));

        expect($ids($repository->findBy($criteria)))->toBe(['eiffel', 'louvre']);
    });

    it('filters on a geo bounding box crossing the antimeridian', function () use ($repository, $ids) {
        $crossing = Criteria::create()
            ->where(field('coordinates')->withinGeoBoundingBox(-20.0, 170.0, 0.0, -170.0));
        $notCrossing = Criteria::create()
            ->where(field('coordinates')->withinGeoBoundingBox(-20.0, 170.0, 0.0, 175.0));

        expect($ids($repository->findBy($crossing)))->toBe(['atoll'])
            ->and($ids($repository->findBy($notCrossing)))->toBe([]);
    });

    it('filters outside a geo radius', function () use ($repository, $ids) {
        $criteria = Criteria::create()
            ->where(field('coordinates')->outsideGeoRadius(48.8566, 2.3522, 5000));

        expect($ids($repository->findBy($criteria)))->toBe(['colosseum', 'atoll']);
    });

    it('never locates a document without coordinates, and always considers it outside', function () use ($ids) {
        $transport = new InMemoryTransport();
        $transport->storage['places'] = ['ghost' => ['id' => 'ghost', 'label' => 'Nowhere']];
        $repository = new ObjectManager($transport)->getRepository(TestPlace::class);
        $matching = static fn (ExpressionInterface $expression) => $ids(
            $repository->findBy(Criteria::create()->where($expression)),
        );

        expect($matching(field('coordinates')->withinGeoRadius(48.8566, 2.3522, 5000)))->toBe([])
            ->and($matching(field('coordinates')->withinGeoBoundingBox(-90.0, -180.0, 90.0, 180.0)))->toBe([])
            ->and($matching(field('coordinates')->outsideGeoRadius(48.8566, 2.3522, 5000)))->toBe(['ghost']);
    });
});
