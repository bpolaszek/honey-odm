<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PostPersistEvent;
use Honey\ODM\Core\Event\PostRemoveEvent;
use Honey\ODM\Core\Event\PostUpdateEvent;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function expect;

describe('Post* events', function () {
    $transport = new TestTransport();
    $eventDispatcher = new TestEventDispatcher();
    $objectManager = new TestObjectManager(
        eventDispatcher: $eventDispatcher,
        transport: $transport,
    );
    $object = new TestDocument(1, 'Test Name 1');

    it('dispatches a PostPersist event after flush', function () use ($objectManager, $eventDispatcher, $object) {
        $eventDispatcher->listen(PostPersistEvent::class, fn () => null);
        $objectManager->persist($object);
        $objectManager->flush();
        expect($eventDispatcher->getFiredEvents(PostPersistEvent::class))->toHaveCount(1)
            ->and($eventDispatcher->getFiredEvents(PostUpdateEvent::class))->toHaveCount(0)
            ->and($eventDispatcher->getFiredEvents(PostRemoveEvent::class))->toHaveCount(0);
    });

    it('dispatches a PostUpdate event after flush', function () use ($objectManager, $eventDispatcher, $object) {
        $eventDispatcher->resetEvents();
        $object->name = 'Updated Name';
        $objectManager->flush();
        expect($eventDispatcher->getFiredEvents(PostPersistEvent::class))->toHaveCount(0)
            ->and($eventDispatcher->getFiredEvents(PostUpdateEvent::class))->toHaveCount(1)
            ->and($eventDispatcher->getFiredEvents(PostRemoveEvent::class))->toHaveCount(0);
    })
        ->depends('it dispatches a PostPersist event after flush');

    it('dispatches a PostRemove event after flush', function () use ($objectManager, $eventDispatcher, $object) {
        $eventDispatcher->resetEvents();
        $objectManager->remove($object);
        $objectManager->flush();
        expect($eventDispatcher->getFiredEvents(PostPersistEvent::class))->toHaveCount(0)
            ->and($eventDispatcher->getFiredEvents(PostUpdateEvent::class))->toHaveCount(0)
            ->and($eventDispatcher->getFiredEvents(PostRemoveEvent::class))->toHaveCount(1);
    })
        ->depends('it dispatches a PostUpdate event after flush');
});
