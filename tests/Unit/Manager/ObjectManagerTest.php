<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Event\PrePersistEvent;
use Honey\ODM\Core\Event\PreRemoveEvent;
use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Repository\TestObjectRepository;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function array_slice;
use function expect;
use function it;

describe('ObjectManager', function () {
    describe('Object Factory', function () {
        $transport = new TestTransport();
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            $transport,
        );
        $object = null;
        it('instantiates an object from a document', function () use ($objectManager, &$object) {
            // Given
            $document = ['id' => 1, 'name' => 'Test Name'];

            // When
            /** @var TestDocument $object */
            $object = $objectManager->factory($document, TestDocument::class);

            // Then
            expect($object)->toBeInstanceOf(TestDocument::class)
                ->and($object->id)->toBe(1)
                ->and($object->name)->toBe('Test Name');
        });

        it(
            'returns an existing object when the document is already in the identity map',
            function () use ($objectManager, &$object) {
                $document = ['id' => 1, 'name' => 'Test Name 2'];
                expect($objectManager->factory($document, TestDocument::class))->toBe($object)
                ->and($object->name)->toBe('Test Name');
            },
        )
            ->depends('it instantiates an object from a document');

        it(
            'returns an existing, refreshed object when the document is already in the identity map',
            function () use ($objectManager, &$object) {
                $document = ['id' => 1, 'name' => 'Test Name 2'];
                expect($objectManager->factory($document, TestDocument::class, true))->toBe($object)
                ->and($object->name)->toBe('Test Name 2');
            },
        )
            ->depends('it returns an existing object when the document is already in the identity map');
    });

    describe('Basic Operations', function () {
        $transport = new TestTransport();
        $objectManager = new TestObjectManager(
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            $transport,
        );

        $objects = [
            new TestDocument(1, 'Test Name 1'),
            new TestDocument(2, 'Test Name 2'),
            new TestDocument(3, 'Test Name 3'),
            new TestDocument(4, 'Test Name 4'),
            new TestDocument(5, 'Test Name 5'),
        ];

        it('persists objects', function () use ($objectManager, $objects) {
            // When
            $objectManager->persist($objects[0], $objects[1]);
            $objectManager->persist($objects[2]);

            // Then
            $pendingUpserts = [...$objectManager->unitOfWork->getPendingUpserts()];
            expect($pendingUpserts)->toContain(...array_slice($objects, 0, 3));
        });

        it('removes objects', function () use ($objectManager, $objects) {
            // When
            $objectManager->remove($objects[2], $objects[3]);
            $objectManager->remove($objects[4]);

            // Then
            $pendingDeletions = [...$objectManager->unitOfWork->getPendingDeletes()];
            expect($pendingDeletions)->toContain(...array_slice($objects, 2, 3));
        })
            ->depends('it persists objects');

        it('has not flushed changes yet', function () use ($transport) {
            expect($transport->storage)->toBeEmpty();
        })
            ->depends('it persists objects');

        it('flushes pending operations', function () use ($objectManager, $transport) {
            // When
            $unitOfWork = $objectManager->unitOfWork;
            $objectManager->flush();

            // Then
            expect($transport->storage['documents']?->toArray() ?? [])->toBe([
                1 => ['id' => 1, 'name' => 'Test Name 1'],
                2 => ['id' => 2, 'name' => 'Test Name 2'],
            ])
                ->and($objectManager->unitOfWork)->not()->toBe($unitOfWork)
                ->and([...$objectManager->unitOfWork->getPendingDeletes()])->toBeEmpty()
                ->and([...$objectManager->unitOfWork->getPendingUpserts()])->toBeEmpty()
            ;
        })
            ->depends('it persists objects');

        it('passes flush options to the transport', function () use ($objectManager, $transport) {
            $objectManager = new TestObjectManager(transport: $transport, defaultFlushOptions: ['foo' => 'bar', 'bar' => 'baz']);
            $objectManager->flush(['foo' => 'baz', 'baz' => 'bar']);
            expect($transport->passedFlushOptions)->toBe([
                'foo' => 'baz',
                'bar' => 'baz',
                'baz' => 'bar',
            ]);
        });
        // phpcs:enable

        it('retrieves a document by its id', function () use ($objectManager, $objects, $transport) {
            // When
            $object = $objectManager->find(TestDocument::class, 1);

            // Then
            expect($object)->toBe($objects[0]);

            // Non-existing document
            expect($objectManager->find(TestDocument::class, 999))->toBeNull();

            // Existing document, not in identity map
            $transport->storage['documents'][10] = ['id' => 10, 'name' => 'Test Name 10'];
            /** @var TestDocument $object */
            $object = $objectManager->find(TestDocument::class, 10);
            expect($object)->toBeInstanceOf(TestDocument::class)
                ->and($object->id)->toBe(10)
                ->and($object->name)->toBe('Test Name 10');
        })
            ->depends('it flushes pending operations');

        it('clears itself', function () use ($objectManager) {
            $object = $objectManager->find(TestDocument::class, 10);
            assert($object instanceof TestDocument);
            $uow = $objectManager->unitOfWork;
            $identities = $objectManager->identities;

            // When
            $objectManager->clear();

            // Then
            expect($objectManager->identities)->not->toBe($identities)
                ->and($objectManager->unitOfWork)->not->toBe($uow);
        })->depends('it flushes pending operations');
    });

    describe('Events', function () {
        it("won't fire a PrePersistEvent on an object not supposed to be persisted", function () {
            $method = Reflection::method(ObjectManager::class, 'firePrePersistEvent');
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->persist($object);
            $objectManager->flush();
            $eventDispatcher->resetEvents();
            $object->name = 'Modified Name';
            $objectManager->unitOfWork->computeChangesets();

            // When
            $method->invoke($objectManager, $object);

            // Then
            expect($eventDispatcher->getFiredEvents(PrePersistEvent::class))->toHaveCount(0);
        });

        it("won't fire a PreUpdateEvent on an object not supposed to be persisted", function () {
            $method = Reflection::method(ObjectManager::class, 'firePreUpdateEvent');
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->persist($object);

            // When
            $method->invoke($objectManager, $object);

            // Then
            expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toHaveCount(0);
        });

        it("won't fire a PreUpdateEvent twice on the same object during the same flush session", function () {
            $method = Reflection::method(ObjectManager::class, 'firePreUpdateEvent');
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->persist($object);
            $objectManager->flush();
            $eventDispatcher->resetEvents();
            $object->name = 'Modified Name';
            $objectManager->unitOfWork->computeChangesets();

            // When
            $method->invoke($objectManager, $object);
            $method->invoke($objectManager, $object);

            // Then
            expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toHaveCount(1);
        });

        it("won't fire a PreRemoveEvent on an object not supposed to be removed", function () {
            $method = Reflection::method(ObjectManager::class, 'firePreRemoveEvent');
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->persist($object);
            $objectManager->flush();
            $eventDispatcher->resetEvents();
            $object->name = 'Modified Name';
            $objectManager->unitOfWork->computeChangesets();

            // When
            $method->invoke($objectManager, $object);

            // Then
            expect($eventDispatcher->getFiredEvents(PreRemoveEvent::class))->toHaveCount(0);
        });

        it("won't fire a PreRemoveEvent twice on the same object during the same flush session", function () {
            $method = Reflection::method(ObjectManager::class, 'firePreRemoveEvent');
            $transport = new TestTransport();
            $eventDispatcher = new TestEventDispatcher();
            $objectManager = new TestObjectManager(
                new TestClassMetadataRegistry(),
                new TestDocumentMapper(),
                $eventDispatcher,
                $transport,
            );
            $object = new TestDocument(1, 'Test Name 1');
            $objectManager->remove($object);

            // When
            $method->invoke($objectManager, $object);
            $method->invoke($objectManager, $object);

            // Then
            expect($eventDispatcher->getFiredEvents(PreRemoveEvent::class))->toHaveCount(1);
        });
    });

    describe('Repositories', function () {
        $objectManager = new class (
            new TestClassMetadataRegistry(),
            new TestDocumentMapper(),
            new TestEventDispatcher(),
            new TestTransport(),
        ) extends ObjectManager {
        };

        it('complains if it cannot find a repository', function () use ($objectManager) {
            $objectManager->getRepository(TestDocument::class);
        })->throws(\InvalidArgumentException::class);

        it('registers and retrieves a repository', function () use ($objectManager) {
            $repository = new TestObjectRepository($objectManager, TestDocument::class);
            $objectManager->registerRepository(TestDocument::class, $repository);
            expect($objectManager->getRepository(TestDocument::class))->toBe($repository);
        })
            ->depends('it complains if it cannot find a repository');
    });
});
