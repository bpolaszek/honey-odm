<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper;

use BenTools\ReflectionPlus\Reflection;
use DateTimeImmutable;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationsTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;
use Psr\Container\ContainerInterface;

it('maps a document to an object', function () {
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $author = Reflection::class(TestAuthor::class)->newInstanceWithoutConstructor();
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestAuthor::class);

    // Given
    $authorDoc = [
        'author_id' => 1,
        'author_name' => 'John Doe',
        'bar' => 'foo',
        'created_at' => '2025-09-30T14:05:19+00:00',
    ];

    // When
    $context = new MappingContext($classMetadata, $objectManager, $author, $authorDoc);
    $author = $objectManager->documentMapper->documentToObject($authorDoc, $author, $context); // @phpstan-ignore-line

     // Then
    expect($author)->toBeInstanceOf(TestAuthor::class) // @phpstan-ignore-line
        ->and($author->id)->toBe(1) // @phpstan-ignore-line
        ->and($author->name)->toBe('John Doe') // @phpstan-ignore-line
        ->and($author->createdAt)->toBeInstanceOf(\DateTimeImmutable::class) // @phpstan-ignore-line
        ->and($author->createdAt->format('Y-m-d H:i:s'))->toBe('2025-09-30 14:05:19'); // @phpstan-ignore-line
});

it('maps an object to a document', function () {
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestAuthor::class);

    // Given
    $author = new TestAuthor(1, 'John Doe');

    // When
    $context = new MappingContext($classMetadata, $objectManager, $author, []);
    $authorDoc = $objectManager->documentMapper->objectToDocument($author, [], $context); // @phpstan-ignore-line

    // Then
    expect($authorDoc)->toBeArray()
        ->toHaveKey('author_id', 1)
        ->toHaveKey('author_name', 'John Doe')
        ->toHaveKey('created_at', $author->createdAt?->format(DateTimeImmutable::ATOM));
});
