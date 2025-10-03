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

it('works', function () {
    $transport = new TestTransport([
        'authors' => new ArrayCollection([
            42 => [
                'created_at' => '2025-10-03T12:47:19+00:00',
                'author_id' => 42,
                'author_name' => 'John Doe',
                'book_id' => '1337'
            ]
        ]),
        'books' => new ArrayCollection([
            '1337' => [
                'id' => '1337',
                'author_id' => 42
            ]
        ]),
    ]);
    $container = new Container();
    $container->add(RelationTransformer::class);
    $container->add(DateTimeImmutableTransformer::class);
    $objectManager = new TestObjectManager(
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(transformers: $container),
        new TestEventDispatcher(),
        $transport,
    );

    $book = $objectManager->find(TestBook::class, '1337');
    expect($book)->toBeInstanceOf(TestBook::class)
        ->and(Reflection::class(TestBook::class)->isUninitializedLazyObject($book))->toBeTrue()
        ->and($book->id)->toBe('1337')
        ->and($book->author)->toBeInstanceOf(TestAuthor::class)
        ->and(Reflection::class(TestAuthor::class)->isUninitializedLazyObject($book->author))->toBeTrue()
        ->and($book->author->id)->toBe(42)
        ->and($book->author->name)->toBe('John Doe')
    ;
});
