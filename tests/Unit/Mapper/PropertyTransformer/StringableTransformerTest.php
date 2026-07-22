<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\StringableTransformer;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Transport\InMemoryTransport;
use stdClass;
use Symfony\Component\Uid\Ulid;

use function describe;
use function mock;

describe('StringableTransformer', function () {
    it('converts an object to its string representation', function () {
        $transformer = new StringableTransformer();
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
        $metadata = new AsField(transformer: StringableTransformer::class);
        $context = new MappingContext($classMetadata, $objectManager, new stdClass(), []);
        $ulid = new Ulid('01K9722GJZ2XE4ZDKZSSX0MY5B');
        $value = $transformer->toDocument($ulid, $metadata, $context);

        expect($value)->toBe('01K9722GJZ2XE4ZDKZSSX0MY5B')
            ->and($transformer->toDocument(null, $metadata, $context))->toBeNull();
    });

    it('converts a string to its object representation', function () {
        $transformer = new StringableTransformer();

        $foo = new class {
            public function __construct(
                #[AsField(primary: true, transformer: StringableTransformer::class)]
                public ?Ulid $id = null,
            ) {
            }
        };

        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(configurations: [
                $foo::class => new AsDocument('foos'),
            ]),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata($foo::class);
        $context = new MappingContext($classMetadata, $objectManager, $foo, []);
        $ulid = '01K9722GJZ2XE4ZDKZSSX0MY5B';
        $propertyMetadata = $classMetadata->getIdPropertyMetadata();
        $value = $transformer->fromDocument($ulid, $propertyMetadata, $context);

        expect($value)->toEqual(new Ulid('01K9722GJZ2XE4ZDKZSSX0MY5B'))
            ->and($transformer->fromDocument(null, $propertyMetadata, $context))->toBeNull();
    });

    it('complains when settable type is invalid', function () {
        $transformer = new StringableTransformer();
        $foo = new class {
            #[AsField(primary: true, transformer: StringableTransformer::class)]
            public Ulid|stdClass|null $id = null;
        };
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(configurations: [
                $foo::class => new AsDocument('foos'),
            ]),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );

        $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata($foo::class);
        $propertyMetadata = $classMetadata->getIdPropertyMetadata();
        $context = new MappingContext($classMetadata, $objectManager, $foo, []);
        $transformer->fromDocument('foo', $propertyMetadata, $context);
    })->throws(\RuntimeException::class, 'Invalid type for property id.');

    it('complains when settable type has no string factory', function () {
        $transformer = new StringableTransformer();
        $foo = new class {
            #[AsField(primary: true, transformer: StringableTransformer::class)]
            public ?stdClass $id = null;
        };
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(configurations: [
                $foo::class => new AsDocument('foos'),
            ]),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );

        $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata($foo::class);
        $propertyMetadata = $classMetadata->getIdPropertyMetadata();
        $context = new MappingContext($classMetadata, $objectManager, $foo, []);
        $transformer->fromDocument('foo', $propertyMetadata, $context);
    })->throws(\RuntimeException::class, 'Failed to retrieve `fromString` method for type stdClass.');

    it('complains when settable type is not stringable', function () {
        $transformer = new StringableTransformer();
        $foo = new class {
            #[AsField(primary: true, transformer: StringableTransformer::class)]
            public ?stdClass $id = null;
        };
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(configurations: [
                $foo::class => new AsDocument('foos'),
            ]),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );

        $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata($foo::class);
        $propertyMetadata = $classMetadata->getIdPropertyMetadata();
        $context = new MappingContext($classMetadata, $objectManager, $foo, []);
        $transformer->toDocument('foo', $propertyMetadata, $context);
    })->throws(\RuntimeException::class, 'Value must be an instance of Stringable.');
});
