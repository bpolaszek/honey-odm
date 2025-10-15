<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use BenTools\ReflectionPlus\Reflection;
use Doctrine\Common\Collections\ArrayCollection;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use Honey\ODM\Core\Tests\Implementation\Examples\TestBook;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function expect;
use function it;

describe('Relation Transformer', function () {
    $transport = new TestTransport();

    it('normalizes relations', function () use ($transport) {
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            $transport,
        );
        $books = [
            new TestBook('A', 'The Tommyknockers'),
            new TestBook('B', '1984'),
            new TestBook('C', 'Carrie'),
        ];
        $authors = [
            new TestAuthor(1, 'Stephen King', [$books[0], $books[2]]),
            new TestAuthor(2, 'George Orwell', [$books[1]]),
            new TestAuthor(3, 'Lazy writer'),
        ];

        $objectManager->persist(...$authors, ...$books);
        $objectManager->flush();

        expect($objectManager->transport->storage)->toEqual([
            'authors' => new ArrayCollection([
                1 => [
                    'created_at' => null,
                    'author_id' => 1,
                    'author_name' => 'Stephen King',
                    'books' => ['A', 'C'],
                ],
                2 => [
                    'created_at' => null,
                    'author_id' => 2,
                    'author_name' => 'George Orwell',
                    'books' => ['B'],
                ],
                3 => [
                    'created_at' => null,
                    'author_id' => 3,
                    'author_name' => 'Lazy writer',
                    'books' => null,
                ],
            ]),
            'books' => new ArrayCollection([
                'A' => [
                    'id' => 'A',
                    'title' => 'The Tommyknockers',
                    'author_id' => null,
                ],
                'B' => [
                    'id' => 'B',
                    'title' => '1984',
                    'author_id' => null,
                ],
                'C' => [
                    'id' => 'C',
                    'title' => 'Carrie',
                    'author_id' => null,
                ],
            ]),
        ]);
    });

    it('retrieves relations', function () use ($transport) {
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            $transport,
        );
        $author = $objectManager->find(TestAuthor::class, 1);
        expect($author)->toBeInstanceOf(TestAuthor::class)
            ->and(Reflection::class(TestAuthor::class)->isUninitializedLazyObject($author))->toBeTrue()
            ->and($author->id)->toBe(1)
            ->and($author->name)->toBe('Stephen King')
            ->and($author->books)->toHaveCount(2)
            ->and(Reflection::class(TestBook::class)->isUninitializedLazyObject($author->books[0]))->toBeTrue()
            ->and($author->books[0]->id)->toBe('A')
            ->and($author->books[0]->name)->toBe('The Tommyknockers')
        ;

        $author = $objectManager->find(TestAuthor::class, 3);
        expect($author->books)->toBeNull();
    });
});
