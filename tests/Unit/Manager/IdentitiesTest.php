<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Transport\InMemoryTransport;
use Honey\ODM\Core\UnitOfWork\Changeset;
use Symfony\Component\Uid\Ulid;

use function array_keys;
use function expect;

describe('Identities', function () {
    it('attaches an object', function () {
        // Given
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');

        // When
        $identities->attach($document, $document->id);

        // Then
        expect($identities->contains($document))->toBeTrue();
    });

    it('detaches an object', function () {
        // Given
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $identities = $objectManager->identities;
        $document = new TestDocument(1, 'Test Name');
        $identities->attach($document, $document->id);

        // When
        $identities->detach($document);

        // Then
        expect($identities->contains($document))->toBeFalse();
    });

    it('checks if it contains an object', function () {
        // Given
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $identities = $objectManager->identities;
        $document1 = new TestDocument(1, 'Test Name');
        $document2 = new TestDocument(2, 'Another Name');

        // When
        $identities->attach($document1, $document1->id);

        // Then
        expect($identities->contains($document1))->toBeTrue()
            ->and($identities->contains($document2))->toBeFalse();
    });

    it('remembers the state of an object', function () {
        // Given
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
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
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
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
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $identities = $objectManager->identities;
        $document1 = new TestDocument(1, 'Test Name 1');
        $document2 = new TestDocument(2, 'Test Name 2');

        // When
        $identities->attach($document1, $document1->id);
        $identities->attach($document2, $document2->id);

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
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
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

    it('accepts stringable objects as primary keys', function () {
        $document = new class {
            public function __construct(
                #[AsField(primary: true)]
                public Ulid $id = new Ulid(),
                #[AsField]
                public string $name = 'Original Name',
            ) {
            }
        };
        $objectManager = new ObjectManager(
            new InMemoryTransport(),
            new ClassMetadataRegistry(configurations: [
                $document::class => new AsDocument('foo')
            ]),
            new DocumentMapper(),
            new TestEventDispatcher(),
        );
        $identities = $objectManager->identities;
        $originalState = ['id' => (string) $document->id, 'name' => $document->name];

        // When
        $identities->attach($document, $document->id);
        $identities->rememberState($document, $originalState);

        // Then
        $this->assertTrue($identities->containsId($document::class, $document->id));

        // When
        $identities->detach($document, $document->id);

        // Then
        $this->assertFalse($identities->containsId($document::class, $document->id));
    });
});
