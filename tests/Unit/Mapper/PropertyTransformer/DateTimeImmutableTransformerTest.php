<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use DateTimeImmutable;
use DateTimeInterface;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\EventDispatcher\TestEventDispatcher;
use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Honey\ODM\Core\Tests\Implementation\Transport\TestTransport;

it('returns null when input is null', function () {
    $transformer = new DateTimeImmutableTransformer();
    $metadata = new TestAsField();
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);
    $result = $transformer->fromDocument(null, $metadata, $context);
    expect($result)->toBeNull();
    $result = $transformer->toDocument(null, $metadata, $context);
    expect($result)->toBeNull();
});

it('complains if value is not a DateTimeInterface instance', function () {
    $transformer = new DateTimeImmutableTransformer();
    $metadata = new TestAsField();
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);
    $transformer->toDocument('not a date', $metadata, $context);
})->throws(\InvalidArgumentException::class, "Expected instance of DateTimeInterface, got 'string'.");

it('uses options', function () {
    $transformer = new DateTimeImmutableTransformer();
    $metadata = new TestAsField(transformer: new TransformerMetadata(
        service: DateTimeImmutableTransformer::class,
        options: [
            'from_format' => 'Y-m-d H:i:s',
            'from_tz' => 'America/New_York',
            'to_tz' => 'Europe/Paris',
            'to_format' => 'U.u',
            'to_type' => 'float',
        ],
    ));
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $dateString = '2024-06-01 12:00:00'; // 12 PM in New York
    $dateTimeImmutable = $transformer->fromDocument($dateString, $metadata, $context);
    expect($dateTimeImmutable)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($dateTimeImmutable?->format(DateTimeInterface::ATOM))->toBe('2024-06-01T12:00:00-04:00');

    $utcDateString = '2024-06-01T16:00:00+00:00'; // 4 PM in UTC
    $utcDateTimeImmutable = new DateTimeImmutable($utcDateString);
    $convertedString = $transformer->toDocument($utcDateTimeImmutable, $metadata, $context);
    expect($convertedString)->toBe((float) new DateTimeImmutable('2024-06-01T16:00:00+00:00')->format('U.u'));
});

it('uses default options', function () {
    $transformer = new DateTimeImmutableTransformer();
    $metadata = new TestAsField(transformer: new TransformerMetadata(
        service: DateTimeImmutableTransformer::class,
    ));
    $objectManager = new class (
        new TestClassMetadataRegistry(),
        new TestDocumentMapper(),
        new TestEventDispatcher(),
        new TestTransport(),
    ) extends ObjectManager {
    };
    $classMetadata = $objectManager->classMetadataRegistry->getClassMetadata(TestDocument::class);
    $context = new MappingContext($classMetadata, $objectManager, new \stdClass(), []);

    $dateString = '2024-06-01T12:00:00-04:00';
    $dateTimeImmutable = $transformer->fromDocument($dateString, $metadata, $context);
    expect($dateTimeImmutable)->toBeInstanceOf(DateTimeImmutable::class)
        ->and($dateTimeImmutable?->format(DateTimeInterface::ATOM))->toBe('2024-06-01T12:00:00-04:00');

    $utcDateString = '2024-06-01T12:00:00-04:00';
    $utcDateTimeImmutable = new DateTimeImmutable($utcDateString);
    $convertedString = $transformer->toDocument($utcDateTimeImmutable, $metadata, $context);
    expect($convertedString)->toBe($utcDateString);
});
