<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Mapper\PropertyTransformer\BackedEnumTransformer;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Examples\TestIntStatus;
use Honey\ODM\Core\Tests\Implementation\Examples\TestStringStatus;
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Transport\InMemoryTransport;
use LogicException;
use ReflectionProperty;

it('returns null when input is null', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField();
    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $result = $transformer->fromDocument(null, $metadata, $context);
    expect($result)->toBeNull();

    $result = $transformer->toDocument(null, $metadata, $context);
    expect($result)->toBeNull();
});

it('uses options[target_class] to hydrate enum from document value (string-backed)', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField(transformer: new TransformerMetadata(
        service: BackedEnumTransformer::class,
        options: [
            'target_class' => TestStringStatus::class,
        ],
    ));
    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $enum = $transformer->fromDocument('draft', $metadata, $context);
    expect($enum)->toBe(TestStringStatus::Draft);

    $enum = $transformer->fromDocument('published', $metadata, $context);
    expect($enum)->toBe(TestStringStatus::Published);
});

it('uses options[target_class] to hydrate enum from document value (int-backed) and toDocument returns scalar', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField(transformer: new TransformerMetadata(
        service: BackedEnumTransformer::class,
        options: [
            'target_class' => TestIntStatus::class,
        ],
    ));
    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $enum = $transformer->fromDocument(0, $metadata, $context);
    expect($enum)->toBe(TestIntStatus::Pending);

    $enum = $transformer->fromDocument(1, $metadata, $context);
    expect($enum)->toBe(TestIntStatus::Done);

    // toDocument
    $scalar = $transformer->toDocument(TestIntStatus::Done, $metadata, $context);
    expect($scalar)->toBe(1);
});

it('toDocument returns underlying scalar value for string-backed enums', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField(transformer: new TransformerMetadata(
        service: BackedEnumTransformer::class,
        options: [
            'target_class' => TestStringStatus::class,
        ],
    ));
    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $scalar = $transformer->toDocument(TestStringStatus::Published, $metadata, $context);
    expect($scalar)->toBe('published');
});

it('infers target class from property reflection when no options provided', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField();
    // Provide a ReflectionProperty with a backed enum type
    Reflection::property($metadata, 'reflection')->setValue(
        $metadata,
        new ReflectionProperty(TestDocument::class, 'publicationState'),
    );

    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $enum = $transformer->fromDocument('draft', $metadata, $context);
    expect($enum)->toBe(TestStringStatus::Draft);
});

it('throws if it cannot infer a valid target class', function () {
    $transformer = new BackedEnumTransformer();
    $metadata = new AsField();
    // Either leave reflection unset or set it to a builtin type property
    // Here we simulate a builtin by reflecting an integer-typed property on an anonymous class
    $anonymous = new class {
        public int $value = 42;
    };
    Reflection::property($metadata, 'reflection')->setValue($metadata, new ReflectionProperty($anonymous, 'value'));

    $objectManager = new ObjectManager(
        new InMemoryTransport(),
        new ClassMetadataRegistry(),
        new DocumentMapper(),
        new TestEventDispatcher(),
    );
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $transformer->fromDocument(123, $metadata, $context);
})->throws(LogicException::class, 'Invalid target class.');
