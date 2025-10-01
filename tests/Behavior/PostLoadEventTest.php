<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use Honey\ODM\Core\Event\PostLoadEvent;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

describe('PostLoadEvent', function () {
    $transport = new TestTransport();
    $eventDispatcher = new TestEventDispatcher();
    $objectManager = new ObjectManager(
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        $eventDispatcher,
        $transport,
    );
    $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Test Name 1'];

    it('fires a PostLoadEvent after creating an object', function () use ($objectManager, $eventDispatcher) {
        // When
        $object = $objectManager->find(TestDocument::class, 1);

        // Then
        expect($eventDispatcher->firedEvents)->toHaveCount(1)
            ->and($eventDispatcher->firedEvents[0])->toBeInstanceOf(PostLoadEvent::class)
            ->and($eventDispatcher->firedEvents[0]->object)->toBe($object)
            ->and($eventDispatcher->firedEvents[0]->objectManager)->toBe($objectManager);
    });

    it(
        'doesn\'t fire a second PostLoadEvent when the object is already managed',
        function () use ($objectManager, $eventDispatcher) {
            // When
            $eventDispatcher->resetEvents();
            $objectManager->find(TestDocument::class, 1);

            // Then
            expect($eventDispatcher->firedEvents)->toHaveCount(0);
        },
    )
        ->depends('it fires a PostLoadEvent after creating an object');
});
