<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestNode;
use Honey\ODM\Core\Transport\InMemoryTransport;

use function describe;
use function expect;
use function it;

describe('Stringable ids', function () {
    $seed = function (): InMemoryTransport {
        $transport = new InMemoryTransport();
        $transport->storage['nodes']['01K9722GJZ2XE4ZDKZSSX0MY5B'] = [
            'id' => '01K9722GJZ2XE4ZDKZSSX0MY5B',
            'parent_id' => null,
            'label' => 'root',
        ];
        $transport->storage['nodes']['01K9722GJZ2XE4ZDKZSSX0MY5C'] = [
            'id' => '01K9722GJZ2XE4ZDKZSSX0MY5C',
            'parent_id' => '01K9722GJZ2XE4ZDKZSSX0MY5B',
            'label' => 'child',
        ];

        return $transport;
    };

    it('resolves the same identifier whether the object is initialized or not', function () use ($seed) {
        // Given
        $objectManager = new ObjectManager(transport: $seed());
        $node = $objectManager->find(TestNode::class, '01K9722GJZ2XE4ZDKZSSX0MY5B');

        // When
        $whileLazy = $objectManager->getIdentifier($node);
        Reflection::class($node)->initializeLazyObject($node);
        $whenInitialized = $objectManager->getIdentifier($node);

        // Then
        expect($whileLazy)->toBe('01K9722GJZ2XE4ZDKZSSX0MY5B')
            ->and($whenInitialized)->toBe($whileLazy);
    });

    it('does not schedule an upsert when a related object gets initialized', function () use ($seed) {
        // Given
        $transport = $seed();
        $eventDispatcher = new TestEventDispatcher();
        $objectManager = new ObjectManager(transport: $transport, eventDispatcher: $eventDispatcher);

        // When - reading through the relation initializes the parent
        $child = $objectManager->find(TestNode::class, '01K9722GJZ2XE4ZDKZSSX0MY5C');
        expect($child->parent->label)->toBe('root');
        $objectManager->flush();

        // Then
        expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toBeEmpty()
            ->and($transport->storage['nodes']['01K9722GJZ2XE4ZDKZSSX0MY5C'])->toBe([
                'id' => '01K9722GJZ2XE4ZDKZSSX0MY5C',
                'parent_id' => '01K9722GJZ2XE4ZDKZSSX0MY5B',
                'label' => 'child',
            ]);
    });
});
