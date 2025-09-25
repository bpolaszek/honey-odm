<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocumentWithoutPrimaryKey;

use function expect;

it('loads class metadata', function (array $configurations) {
    $registry = new TestClassMetadataRegistry($configurations);
    expect($registry->hasClassMetadata(TestDocument::class))->toBeTrue();

    $classMetadata = $registry->getClassMetadata(TestDocument::class);
    expect($classMetadata->className)->toBe(TestDocument::class)
        ->and($classMetadata->reflection)->toBe(Reflection::class(TestDocument::class))
        ->and($classMetadata->propertiesMetadata)->toHaveCount(2)
        ->and($classMetadata->propertiesMetadata['id'])->toBeInstanceOf(TestAsField::class)
        ->and($classMetadata->propertiesMetadata['id']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['id']->primary)->toBeTrue()
        ->and($classMetadata->propertiesMetadata['id']->reflection)->toEqual(Reflection::property(TestDocument::class, 'id'))
        ->and($classMetadata->propertiesMetadata['name'])->toBeInstanceOf(TestAsField::class)
        ->and($classMetadata->propertiesMetadata['name']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['name']->primary)->toBeFalse()
        ->and($classMetadata->propertiesMetadata['name']->reflection)->toEqual(Reflection::property(TestDocument::class, 'name'))
    ;
})->with(function () {
    $refl = Reflection::method(TestClassMetadataRegistry::class, 'populateClassMetadata');
    $classMetadata = $refl->invoke(new TestClassMetadataRegistry(), Reflection::class(TestDocument::class), new TestAsDocument());
    yield 'Eager load by constructor' => [[TestDocument::class => $classMetadata]];
    yield 'Lazy load by constructor' => [[TestDocument::class]];
    yield 'Lazy load on call' => [[]];
});

it('complains when class is not registered as a document', function () {
    $foo = new class {
        #[TestAsField]
        public int $id;
    };

    $registry = new TestClassMetadataRegistry();
    expect($registry->hasClassMetadata($foo::class))->toBeFalse()
        ->and(fn () => $registry->getClassMetadata($foo::class))
        ->toThrow(\InvalidArgumentException::class);
});

it('complains when document has no primary key', function () {
    $registry = new TestClassMetadataRegistry();
    expect(fn () => $registry->getClassMetadata(TestDocumentWithoutPrimaryKey::class))
        ->toThrow(\RuntimeException::class);
});
