<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function expect;

describe('PreUpdateEvent', function () {
    $transport = new TestTransport();
    $eventDispatcher = new TestEventDispatcher();
    $objectManager = new TestObjectManager(
        eventDispatcher: $eventDispatcher,
        transport: $transport,
    );
    $object = new TestDocument(1, 'Test Name 1');
    $objectManager->persist($object);
    $objectManager->flush();

    it('triggers a PreUpdateEvent when the object is updated', function () use ($object, $objectManager, $eventDispatcher) {
        $eventDispatcher->resetEvents();
        $object->name = 'Updated Name';

        // When
        $objectManager->flush();


        // Then
        expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toHaveCount(1)
            ->and($eventDispatcher->getFiredEvents(PreUpdateEvent::class)[0])->toBeInstanceOf(PreUpdateEvent::class)
            ->and($eventDispatcher->getFiredEvents(PreUpdateEvent::class)[0]->object)->toBe($object)
            ->and($eventDispatcher->getFiredEvents(PreUpdateEvent::class)[0]->objectManager)->toBe($objectManager);
    });
});
