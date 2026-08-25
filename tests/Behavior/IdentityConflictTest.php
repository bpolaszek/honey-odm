<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocumentWithGeneratedId;
use Honey\ODM\Core\Transport\InMemoryTransport;

use function describe;
use function expect;
use function it;

describe('Identity conflicts', function () {
    it('lets an explicitly persisted object win over the managed instance holding the same id', function () {
        // Given - the stale instance is loaded, modified, and would be upserted on its own
        $transport = new InMemoryTransport();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Stored Name', 'publicationState' => null, 'done' => null];
        $objectManager = new ObjectManager(transport: $transport);
        $stale = $objectManager->find(TestDocument::class, 1);
        $stale->name = 'Stale Name';

        // When - a fresh instance carrying the same id is persisted
        $fresh = new TestDocument(1, 'Fresh Name');
        $objectManager->persist($fresh);
        $objectManager->flush();

        // Then - only the fresh one is written, and it becomes the managed instance
        expect($transport->storage['documents'][1]['name'])->toBe('Fresh Name')
            ->and([...$objectManager->identities])->toBe([$fresh])
            ->and($objectManager->find(TestDocument::class, 1))->toBe($fresh);
    });

    it('does not detach the managed instance when it is the one being persisted', function () {
        // Given
        $transport = new InMemoryTransport();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Stored Name', 'publicationState' => null, 'done' => null];
        $objectManager = new ObjectManager(transport: $transport);

        // When
        $object = $objectManager->find(TestDocument::class, 1);
        $object->name = 'Updated Name';
        $objectManager->persist($object);
        $objectManager->flush();

        // Then
        expect($transport->storage['documents'][1]['name'])->toBe('Updated Name')
            ->and([...$objectManager->identities])->toBe([$object]);
    });

    it('supports ids which are assigned by a PrePersistEvent listener', function () {
        // Given
        $transport = new InMemoryTransport();
        $eventDispatcher = new TestEventDispatcher();
        $eventDispatcher->listen(PrePersistEvent::class, function (PrePersistEvent $event) {
            $event->object->id = 'generated-id';
        });
        $objectManager = new ObjectManager(transport: $transport, eventDispatcher: $eventDispatcher);

        // When - the id is still unassigned when changesets are first computed
        $object = new TestDocumentWithGeneratedId('Fresh Name');
        $objectManager->persist($object);
        $objectManager->flush();

        // Then
        expect($object->id)->toBe('generated-id')
            ->and($transport->storage['generated']['generated-id']['name'])->toBe('Fresh Name');
    });
});
