<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Config\TestPlatformMetadata;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocumentWithoutPrimaryKey;

use function expect;

it('loads class metadata', function (array $configurations) {
    $registry = new ClassMetadataRegistry(configurations: $configurations);
    expect($registry->hasClassMetadata(TestDocument::class))->toBeTrue();

    $classMetadata = $registry->getClassMetadata(TestDocument::class);
    expect($classMetadata->className)->toBe(TestDocument::class)
        ->and($classMetadata->reflection)->toBe(Reflection::class(TestDocument::class))
        ->and($classMetadata->collection)->toBe('documents')
        ->and($classMetadata->propertiesMetadata)->toHaveCount(4)
        ->and($classMetadata->propertiesMetadata)->not->toHaveKey('foo')
        ->and($classMetadata->propertiesMetadata['id'])->toBeInstanceOf(AsField::class)
        ->and($classMetadata->propertiesMetadata['id']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['id']->primary)->toBeTrue()
        ->and($classMetadata->propertiesMetadata['id']->reflection)->toEqual(Reflection::property(TestDocument::class, 'id'))
        ->and($classMetadata->propertiesMetadata['name'])->toBeInstanceOf(AsField::class)
        ->and($classMetadata->propertiesMetadata['name']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['name']->primary)->toBeFalse()
        ->and($classMetadata->propertiesMetadata['name']->reflection)->toEqual(Reflection::property(TestDocument::class, 'name'))
    ;
})->with(function () {
    yield 'Eager load by constructor (external metadata)' => [
        [TestDocument::class => new AsDocument(collection: 'documents')],
    ];
    yield 'Lazy load by constructor' => [[TestDocument::class]];
    yield 'Lazy load on call' => [[]];
});

it('collects platform metadata', function () {
    $registry = new ClassMetadataRegistry();
    $classMetadata = $registry->getClassMetadata(TestDocument::class);

    expect($classMetadata->platformMetadata)->toHaveCount(1)
        ->and($classMetadata->getPlatformMetadata(TestPlatformMetadata::class))->toBeInstanceOf(TestPlatformMetadata::class)
        ->and($classMetadata->getPlatformMetadata(TestPlatformMetadata::class)?->options)->toBe(['foo' => 'bar'])
        ->and($classMetadata->propertiesMetadata['name']->getPlatformMetadata(TestPlatformMetadata::class)?->options)->toBe(['searchable' => true])
        ->and($classMetadata->propertiesMetadata['id']->getPlatformMetadata(TestPlatformMetadata::class))->toBeNull()
    ;
});

it('iterates over registered class metadata', function () {
    $registry = new ClassMetadataRegistry(configurations: [TestDocument::class]);

    expect([...$registry])->toHaveKey(TestDocument::class);
});

it('complains when class is not registered as a document', function () {
    $foo = new class {
        #[AsField]
        public int $id;
    };

    $registry = new ClassMetadataRegistry();
    expect($registry->hasClassMetadata($foo::class))->toBeFalse()
        ->and(fn () => $registry->getClassMetadata($foo::class))
        ->toThrow(\InvalidArgumentException::class);
});

it('complains when document has no primary key', function () {
    $registry = new ClassMetadataRegistry();
    expect(fn () => $registry->getClassMetadata(TestDocumentWithoutPrimaryKey::class))
        ->toThrow(\RuntimeException::class);
});

it('returns the id of an object', function () {
    $registry = new ClassMetadataRegistry();
    $object = new TestDocument(42, 'foo');
    expect($registry->getIdFromObject($object))->toBe(42);
});

it('returns the id of a document', function () {
    $registry = new ClassMetadataRegistry();
    $document = ['id' => 42, 'name' => 'foo'];
    expect($registry->getIdFromDocument($document, TestDocument::class))->toBe(42);
});
