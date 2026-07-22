<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Transport\InMemoryTransport;

use function expect;

describe('Prevent flushing during flush', function () {
    it('prevents infinite flushing loop during flush operation', function () {
        $transport = new InMemoryTransport();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(
            eventDispatcher: $eventDispatcher,
            transport: $transport,
        );

        $eventWasCalled = false;
        $eventDispatcher->listen(PrePersistEvent::class, function (PrePersistEvent $event) use (&$eventWasCalled) {
            $event->objectManager->flush(); // 😱
            $eventWasCalled = true;
        });

        $object = new TestDocument(1, 'Test Name 1');
        $objectManager->persist($object);
        $objectManager->flush();

        expect($eventWasCalled)->toBeTrue(); // We're still alive! 😌
    });
});
