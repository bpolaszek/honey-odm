<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

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

    /*$objectManager = new TestObjectManager(
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(transformers: $container),
        new TestEventDispatcher(),
        $transport,
    );
    $author = new TestAuthor(42, 'John Doe');
    $objectManager->persist($author);
    $objectManager->flush();

    $book = new TestBook('1337', $author);
    $objectManager->persist($book);
    $objectManager->flush();*/
    $objectManager = new TestObjectManager(
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(transformers: $container),
        new TestEventDispatcher(),
        $transport,
    );

    dump($transport);
    dump($objectManager->find(TestBook::class, '1337'));
});
