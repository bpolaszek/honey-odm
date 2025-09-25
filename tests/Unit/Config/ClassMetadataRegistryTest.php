<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Tests\Implementation\Config\AsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\AsField;
use Honey\ODM\Core\Tests\Implementation\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Examples\Document;
use Honey\ODM\Core\Tests\Implementation\Examples\DocumentWithoutPrimaryKey;

use function expect;

it('loads class metadata', function (array $configurations) {
    $registry = new ClassMetadataRegistry($configurations);
    expect($registry->hasClassMetadata(Document::class))->toBeTrue();

    $classMetadata = $registry->getClassMetadata(Document::class);
    expect($classMetadata->className)->toBe(Document::class)
        ->and($classMetadata->reflection)->toBe(Reflection::class(Document::class))
        ->and($classMetadata->propertiesMetadata)->toHaveCount(2)
        ->and($classMetadata->propertiesMetadata['id'])->toBeInstanceOf(AsField::class)
        ->and($classMetadata->propertiesMetadata['id']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['id']->primary)->toBeTrue()
        ->and($classMetadata->propertiesMetadata['id']->reflection)->toEqual(Reflection::property(Document::class, 'id'))
        ->and($classMetadata->propertiesMetadata['name'])->toBeInstanceOf(AsField::class)
        ->and($classMetadata->propertiesMetadata['name']->classMetadata)->toBe($classMetadata)
        ->and($classMetadata->propertiesMetadata['name']->primary)->toBeFalse()
        ->and($classMetadata->propertiesMetadata['name']->reflection)->toEqual(Reflection::property(Document::class, 'name'))
    ;
})->with(function () {
    $refl = Reflection::method(ClassMetadataRegistry::class, 'populateClassMetadata');
    $classMetadata = $refl->invoke(new ClassMetadataRegistry(), Reflection::class(Document::class), new AsDocument());
    yield 'Eager load by constructor' => [[Document::class => $classMetadata]];
    yield 'Lazy load by constructor' => [[Document::class]];
    yield 'Lazy load on call' => [[]];
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
    expect(fn () => $registry->getClassMetadata(DocumentWithoutPrimaryKey::class))
        ->toThrow(\RuntimeException::class);
});
