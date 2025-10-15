<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

describe('PrePersistEvent', function () {
    $transport = new TestTransport();
    $eventDispatcher = new TestEventDispatcher();
    $objectManager = new TestObjectManager(
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        $eventDispatcher,
        $transport,
    );
    $object = new TestDocument(1, 'Test Name 1');

    it('fires a PrePersistEvent during flush process', function () use ($objectManager, $eventDispatcher, $object) {
        // When
        $objectManager->persist($object);
        $objectManager->flush();

        // Then
        expect($eventDispatcher->getFiredEvents(PrePersistEvent::class))->toHaveCount(1)
            ->and($eventDispatcher->getFiredEvents(PrePersistEvent::class)[0])->toBeInstanceOf(PrePersistEvent::class)
            ->and($eventDispatcher->getFiredEvents(PrePersistEvent::class)[0]->object)->toBe($object)
            ->and($eventDispatcher->getFiredEvents(PrePersistEvent::class)[0]->objectManager)->toBe($objectManager);
    });

    it(
        'doesn\'t fire a second PrePersistEvent when it has alredy be fired for the object',
        function () use ($objectManager, $eventDispatcher) {
            // When
            $eventDispatcher->resetEvents();
            $objectManager->flush();

            // Then
            expect($eventDispatcher->getFiredEvents(PrePersistEvent::class))->toHaveCount(0);
        },
    )
        ->depends('it fires a PrePersistEvent during flush process');

    it(
        'recomputes changesets during a PrePersistEvent and prevents firing a second PrePersistEvent during the same flush session',
        function () {
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $eventCalls = new \ArrayObject();
            $eventDispatcher->listen(PrePersistEvent::class, function (PrePersistEvent $event) use ($eventCalls) {
                $eventCalls->append($event);
                $event->object->name = 'Modified Name';
                $event->objectManager->persist($event->object);
            });

            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->persist($object);
            $objectManager->flush();

            expect($eventCalls)->toHaveCount(1)
                ->and($object->name)->toBe('Modified Name')
                ->and($transport->storage['documents'][1]['name'])->toBe('Modified Name');
        },
    );
});
