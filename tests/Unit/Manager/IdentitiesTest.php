<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Manager\Identities;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;
use Honey\ODM\Core\UnitOfWork\Changeset;

use function array_keys;

describe('Identities', function () {
    it('attaches an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');

        // When
        $identities->attach($document);

        // Then
        expect($identities->contains($document))->toBeTrue();
    });

    it('detaches an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');
        $identities->attach($document);

        // When
        $identities->detach($document);

        // Then
        expect($identities->contains($document))->toBeFalse();
    });

    it('checks if it contains an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document1 = new TestDocument(1, 'Test Name');
        $document2 = new TestDocument(2, 'Another Name');

        // When
        $identities->attach($document1);

        // Then
        expect($identities->contains($document1))->toBeTrue()
            ->and($identities->contains($document2))->toBeFalse();
    });

    it('remembers the state of an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');
        $state = ['id' => 1, 'name' => 'Test Name', 'foo' => 'bar'];

        // When
        $identities->rememberState($document, $state);

        // Then - verify by computing changeset which uses remembered state
        $changeset = $identities->computeChangeset($document);
        expect($changeset)->toBeInstanceOf(Changeset::class);
    });

    it('forgets the state of an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');
        $state = ['id' => 1, 'name' => 'Test Name', 'foo' => 'bar'];
        $identities->rememberState($document, $state);

        // When
        $identities->forgetState($document);

        // Then - verify by computing changeset which should use empty remembered state
        $changeset = $identities->computeChangeset($document);
        expect($changeset->previousDocument)->toBeEmpty();
    });

    it('iterates over attached objects', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document1 = new TestDocument(1, 'Test Name 1');
        $document2 = new TestDocument(2, 'Test Name 2');

        // When
        $identities->attach($document1);
        $identities->attach($document2);

        // Then
        $attachedObjects = [];
        foreach ($identities as $object) {
            $attachedObjects[] = $object;
        }

        expect($attachedObjects)->toHaveCount(2)
            ->and($attachedObjects)->toContain($document1)
            ->and($attachedObjects)->toContain($document2);
    });

    it('computes the changeset of an object', function () {
        // Given
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Original Name');
        $originalState = ['id' => 1, 'name' => 'Original Name'];

        // When
        $identities->rememberState($document, $originalState);
        $document->name = 'Updated Name'; // Change the object
        $changeset = $identities->computeChangeset($document);

        // Then
        expect($changeset)->toBeInstanceOf(Changeset::class)
            ->and($changeset->previousDocument)->toBe($originalState)
            ->and($changeset->newDocument)->toHaveKey('name', 'Updated Name')
            ->and(array_keys($changeset->changedProperties))->toBe(['name'])
            ->and($changeset->changedProperties['name'][0])->toBe('Updated Name')
            ->and($changeset->changedProperties['name'][1])->toBe('Original Name')
        ;
    });
});
