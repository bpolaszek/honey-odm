<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Behavior;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Event\PreUpdateEvent;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use Honey\ODM\Core\Tests\Implementation\Examples\TestBook;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Transport\InMemoryTransport;

use function describe;
use function expect;
use function it;

describe('Change tracking', function () {
    it('does not schedule an upsert when the stored document holds unmapped attributes', function () {
        // Given - "foo" is a public property of TestDocument, but it is not mapped as a field
        $transport = new InMemoryTransport();
        $eventDispatcher = new TestEventDispatcher();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Untouched', 'foo' => 'bar'];
        $objectManager = new ObjectManager(transport: $transport, eventDispatcher: $eventDispatcher);

        // When - the document is read (and hydrated), then flushed without being modified
        $object = $objectManager->find(TestDocument::class, 1);
        Reflection::class($object)->initializeLazyObject($object);
        $objectManager->flush();

        // Then
        expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toBeEmpty()
            ->and($transport->storage['documents'][1])->toBe(['id' => 1, 'name' => 'Untouched', 'foo' => 'bar']);
    });

    it('does not schedule an upsert when a property transformer cannot round-trip', function () {
        // Given - the book points to an author which is not stored: the relation hydrates to null
        $transport = new InMemoryTransport();
        $eventDispatcher = new TestEventDispatcher();
        $transport->storage['books']['book-1'] = ['id' => 'book-1', 'title' => 'Untouched', 'author_id' => 999];
        $objectManager = new ObjectManager(transport: $transport, eventDispatcher: $eventDispatcher);

        // When
        $book = $objectManager->find(TestBook::class, 'book-1');
        Reflection::class($book)->initializeLazyObject($book);
        $objectManager->flush();

        // Then
        expect($book->author)->toBeNull()
            ->and($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toBeEmpty()
            ->and($transport->storage['books']['book-1'])
            ->toBe(['id' => 'book-1', 'title' => 'Untouched', 'author_id' => 999]);
    });

    it('does not re-upsert an object which has already been flushed', function () {
        // Given
        $transport = new InMemoryTransport();
        $eventDispatcher = new TestEventDispatcher();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Original Name', 'publicationState' => null, 'done' => null];
        $objectManager = new ObjectManager(transport: $transport, eventDispatcher: $eventDispatcher);

        // When
        $object = $objectManager->find(TestDocument::class, 1);
        $object->name = 'Updated Name';
        $objectManager->flush();
        $objectManager->flush();

        // Then - the change is written once, and the object is not dirty anymore
        expect($eventDispatcher->getFiredEvents(PreUpdateEvent::class))->toHaveCount(1)
            ->and($transport->storage['documents'][1]['name'])->toBe('Updated Name')
            ->and($objectManager->identities->computeChangeset($object)->changedProperties)->toBeEmpty();
    });

    it('does not initialize related objects when serializing a document', function () {
        // Given
        $transport = new InMemoryTransport();
        $transport->storage['books']['book-1'] = ['id' => 'book-1', 'title' => 'Untouched', 'author_id' => 1];
        $transport->storage['authors'][1] = ['author_id' => 1, 'author_name' => 'George Orwell'];
        $objectManager = new ObjectManager(transport: $transport);

        // When - the book is hydrated and modified, but its author is never accessed
        $book = $objectManager->find(TestBook::class, 'book-1');
        $book->name = 'Nineteen Eighty-Four';
        $objectManager->flush();

        // Then - neither the changeset computation nor the flush hydrated the author
        expect(Reflection::class(TestAuthor::class)->isUninitializedLazyObject($book->author))->toBeTrue()
            ->and($transport->storage['books']['book-1'])
            ->toBe(['id' => 'book-1', 'title' => 'Nineteen Eighty-Four', 'author_id' => 1]);
    });

    it('does not initialize lazy objects which were never accessed', function () {
        // Given
        $transport = new InMemoryTransport();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Untouched'];
        $objectManager = new ObjectManager(transport: $transport);

        // When - the object is loaded but never accessed
        $object = $objectManager->find(TestDocument::class, 1);
        $objectManager->flush();

        // Then - computing changesets must not force its hydration
        expect(Reflection::class($object)->isUninitializedLazyObject($object))->toBeTrue();
    });

    it('does not initialize a lazy object which is being removed', function () {
        // Given
        $transport = new InMemoryTransport();
        $transport->storage['documents'][1] = ['id' => 1, 'name' => 'Untouched'];
        $objectManager = new ObjectManager(transport: $transport);

        // When
        $object = $objectManager->find(TestDocument::class, 1);
        $objectManager->remove($object);
        $objectManager->flush();

        // Then - deleting only requires the id, which the identity map already knows
        expect(Reflection::class($object)->isUninitializedLazyObject($object))->toBeTrue()
            ->and($transport->storage['documents'])->toBeEmpty();
    });
});
