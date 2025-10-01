<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Manager\Identities;
use Honey\ODM\Core\Manager\ObjectFactory;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;

use function expect;

describe('ObjectFactory', function () {
    $classMetadataRegistry = new TestClassMetadataRegistry();
    $documentMapper = new TestDocumentMapper();
    $factory = new ObjectFactory(
        $classMetadataRegistry,
        $documentMapper,
        new Identities($classMetadataRegistry, $documentMapper),
    );
    $object = null;

    it('instantiates an object from a document', function () use ($factory, $classMetadataRegistry, &$object) {
        // Given
        $document = ['id' => 1, 'name' => 'Test Name'];
        $classMetadata = $classMetadataRegistry->getClassMetadata(TestDocument::class);

        // When
        /** @var TestDocument $object */
        $object = $factory->factory($document, $classMetadata);

        // Then
        expect($object)->toBeInstanceOf(TestDocument::class)
            ->and($object->id)->toBe(1)
            ->and($object->name)->toBe('Test Name');
    });

    it(
        'returns an existing object when the document is already in the identity map',
        function () use ($factory, $classMetadataRegistry, &$object) {
            $document = ['id' => 1, 'name' => 'Test Name'];
            $classMetadata = $classMetadataRegistry->getClassMetadata(TestDocument::class);
            expect($factory->factory($document, $classMetadata))->toBe($object);
        },
    )
        ->depends('it instantiates an object from a document');
});
