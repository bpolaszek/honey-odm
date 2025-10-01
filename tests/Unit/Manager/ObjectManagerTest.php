<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

use function array_slice;
use function expect;

describe('ObjectManager - Basic operations', function () {
    $transport = new TestTransport();
    $objectManager = new ObjectManager(
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
        $pendingDeletions = [...$objectManager->unitOfWork->getPendingDeletions()];
        expect($pendingDeletions)->toContain(...array_slice($objects, 2, 3));
    });

    it('has not flushed changes yet', function () use ($transport) {
        expect($transport->storage)->toBeEmpty();
    });

    it('flushes pending operations', function () use ($objectManager, $transport) {
        // When
        $unitOfWork = $objectManager->unitOfWork;
        $objectManager->flush();

        // Then
        expect($transport->storage)->toBe([
            'documents' => [
                1 => ['id' => 1, 'name' => 'Test Name 1'],
                2 => ['id' => 2, 'name' => 'Test Name 2'],
            ],
        ])
            ->and($objectManager->unitOfWork)->not()->toBe($unitOfWork)
            ->and([...$objectManager->unitOfWork->getPendingDeletions()])->toBeEmpty()
            ->and([...$objectManager->unitOfWork->getPendingUpserts()])->toBeEmpty()
        ;
    });

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
    });
});
