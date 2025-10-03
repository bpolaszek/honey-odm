<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use BenTools\ReflectionPlus\Reflection;
use Doctrine\Common\Collections\ArrayCollection;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use Honey\ODM\Core\Tests\Implementation\Examples\TestBook;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;
use League\Container\Container;

use function expect;
use function it;

describe('Relation Transformer', function () {
    $transport = new TestTransport();
    $container = new Container();
    $container->add(RelationTransformer::class);
    $container->add(DateTimeImmutableTransformer::class);

    it('normalizes relations', function () use ($container, $transport) {
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(transformers: $container),
            new TestEventDispatcher(),
            $transport,
        );
        $authors = [
            new TestAuthor(1, 'Stephen King'),
            new TestAuthor(2, 'George Orwell'),
        ];
        $books = [
            new TestBook('A', 'The Tommyknockers', $authors[0]),
            new TestBook('B', '1984', $authors[1]),
            new TestBook('C', 'The Holy Bible'),
        ];

        $objectManager->persist(...$authors, ...$books);
        $objectManager->flush();

        expect($objectManager->transport->storage)->toEqual([
            'authors' => new ArrayCollection([
                1 => [
                    'created_at' => null,
                    'author_id' => 1,
                    'author_name' => 'Stephen King',
                ],
                2 => [
                    'created_at' => null,
                    'author_id' => 2,
                    'author_name' => 'George Orwell',
                ],
            ]),
            'books' => new ArrayCollection([
                'A' => [
                    'id' => 'A',
                    'title' => 'The Tommyknockers',
                    'author_id' => 1,
                ],
                'B' => [
                    'id' => 'B',
                    'title' => '1984',
                    'author_id' => 2,
                ],
                'C' => [
                    'id' => 'C',
                    'title' => 'The Holy Bible',
                    'author_id' => null,
                ],
            ]),
        ]);
    });

    it('retrieves relations', function () use ($container, $transport) {
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(transformers: $container),
            new TestEventDispatcher(),
            $transport,
        );
        $book = $objectManager->find(TestBook::class, 'B');
        expect($book)->toBeInstanceOf(TestBook::class)
            ->and(Reflection::class(TestBook::class)->isUninitializedLazyObject($book))->toBeTrue()
            ->and($book->id)->toBe('B')
            ->and($book->name)->toBe('1984')
            ->and($book->author)->toBeInstanceOf(TestAuthor::class)
            ->and(Reflection::class(TestAuthor::class)->isUninitializedLazyObject($book->author))->toBeTrue()
            ->and($book->author->id)->toBe(2)
            ->and($book->author->name)->toBe('George Orwell')
        ;

        $book = $objectManager->find(TestBook::class, 'C');
        expect($book->author)->toBeNull();
    });
});
