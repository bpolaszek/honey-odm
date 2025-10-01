<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\UnitOfWork;

use Honey\ODM\Core\Manager\Identities;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Manager\ObjectManagerInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use Psr\EventDispatcher\EventDispatcherInterface;

use function iterator_to_array;

describe('UnitOfWork', function () {
    it('schedules an upsert', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $document1 = new TestDocument(1, 'Test Name 1');
        $document2 = new TestDocument(2, 'Test Name 2');
        $document3 = new TestDocument(3, 'Test Name 3');

        // When
        $unitOfWork->scheduleUpsert($document1, $document2);
        $unitOfWork->scheduleDeletion($document3);

        // Then
        $pendingUpserts = iterator_to_array($unitOfWork->getPendingUpserts());
        expect($pendingUpserts)->toHaveCount(2)
            ->and($pendingUpserts)->toContain($document1)
            ->and($pendingUpserts)->toContain($document2);
    });

    it('schedules a deletion', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $document1 = new TestDocument(1, 'Test Name 1');
        $document2 = new TestDocument(2, 'Test Name 2');
        $document3 = new TestDocument(3, 'Test Name 3');

        // When
        $unitOfWork->scheduleDeletion($document1, $document2);
        $unitOfWork->scheduleUpsert($document3);

        // Then
        $pendingDeletions = iterator_to_array($unitOfWork->getPendingDeletes());
        expect($pendingDeletions)->toHaveCount(2)
            ->and($pendingDeletions)->toContain($document1)
            ->and($pendingDeletions)->toContain($document2);
    });

    it('lists pending upserts', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $upsertDocument = new TestDocument(1, 'Upsert Doc');
        $deleteDocument = new TestDocument(2, 'Delete Doc');

        // When
        $unitOfWork->scheduleUpsert($upsertDocument);
        $unitOfWork->scheduleDeletion($deleteDocument);

        // Then
        $pendingUpserts = iterator_to_array($unitOfWork->getPendingUpserts());
        expect($pendingUpserts)->toHaveCount(1)
            ->and($pendingUpserts)->toContain($upsertDocument)
            ->and($pendingUpserts)->not->toContain($deleteDocument);
    });

    it('lists pending deletions', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $upsertDocument = new TestDocument(1, 'Upsert Doc');
        $deleteDocument = new TestDocument(2, 'Delete Doc');

        // When
        $unitOfWork->scheduleUpsert($upsertDocument);
        $unitOfWork->scheduleDeletion($deleteDocument);

        // Then
        $pendingDeletions = iterator_to_array($unitOfWork->getPendingDeletes());
        expect($pendingDeletions)->toHaveCount(1)
            ->and($pendingDeletions)->toContain($deleteDocument)
            ->and($pendingDeletions)->not->toContain($upsertDocument);
    });

    it('computes changesets', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $document = new TestDocument(1, 'Original Name');

        // Attach object and remember its state
        $objectManager->identities->attach($document);
        $objectManager->identities->rememberState($document, ['id' => 1, 'name' => 'Original Name', 'foo' => 'bar']);

        // Change the object
        $document->name = 'Updated Name';

        // When
        $unitOfWork->computeChangesets();

        // Then
        $changedObjects = iterator_to_array($unitOfWork->getChangedObjects());
        expect($changedObjects)->toContain($document);
    });

    it('lists objects which contains changes', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $document1 = new TestDocument(1, 'Original Name 1');
        $document2 = new TestDocument(2, 'Original Name 2');

        // Attach objects and remember their states (using current state)
        $objectManager->identities->attach($document1);
        $objectManager->identities->attach($document2);
        $objectManager->identities->rememberState($document1, $mapper->objectToDocument($registry->getClassMetadata($document1::class), $document1));
        $objectManager->identities->rememberState($document2, $mapper->objectToDocument($registry->getClassMetadata($document2::class), $document2));

        // Change only one object
        $document1->name = 'Updated Name 1';

        // When
        $unitOfWork->computeChangesets();

        // Then
        $changedObjects = iterator_to_array($unitOfWork->getChangedObjects());
        expect($changedObjects)->toContain($document1)
            ->and($changedObjects)->not->toContain($document2);
    });

    test('its hash changes when changesets are computed', function () {
        // Given
        $registry = new TestClassMetadataRegistry();
        $mapper = new TestDocumentMapper();
        $transport = new TestTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            classMetadataRegistry: $registry,
            documentMapper: $mapper,
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );
        $unitOfWork = new UnitOfWork($objectManager);
        $document = new TestDocument(1, 'Original Name');

        // Initial computation
        $unitOfWork->computeChangesets();
        $initialHash = $unitOfWork->hash;

        // Attach object, remember state, and change it
        $objectManager->identities->attach($document);
        $objectManager->identities->rememberState($document, ['id' => 1, 'name' => 'Original Name', 'foo' => 'bar']);
        $document->name = 'Updated Name';

        // When
        $unitOfWork->computeChangesets();

        // Then
        $secondHash = $unitOfWork->hash;
        expect($secondHash)->not->toBe($initialHash);

        // When
        $unitOfWork->computeChangesets();

        // Then
        expect($unitOfWork->hash)->toBe($secondHash); // Hash remains the same if no changes

        // When
        $unitOfWork->scheduleDeletion(new TestDocument(2, 'Another Doc'));
        $unitOfWork->computeChangesets();

        // Then
        expect($unitOfWork->hash)->not()->toBe($secondHash)
            ->and($unitOfWork->hash)->not()->toBe($initialHash)
        ;
    });
});
