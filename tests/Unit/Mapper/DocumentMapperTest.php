<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper;

use BenTools\ReflectionPlus\Reflection;
use DateTimeImmutable;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use Honey\ODM\Core\Tests\Implementation\Mapper\TestDocumentMapper;
use Psr\Container\ContainerInterface;

$transformers = new class implements ContainerInterface {
    /**
     * @var array<class-string, object>
     */
    private array $services;

    public function __construct()
    {
        $this->services[DateTimeImmutableTransformer::class] = new DateTimeImmutableTransformer();
    }

    public function get(string $id)
    {
        return $this->services[$id] ?? throw new class ("Service $id not found") extends \Exception {
        };
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
};

it('maps a document to an object', function () use ($transformers) {
    $mapper = new TestDocumentMapper(transformers: $transformers);
    $registry = new TestClassMetadataRegistry();
    $author = Reflection::class(TestAuthor::class)->newInstanceWithoutConstructor();
    $metadata = $registry->getClassMetadata(TestAuthor::class);

    // Given
    $authorDoc = [
        'author_id' => 1,
        'author_name' => 'John Doe',
        'bar' => 'foo',
        'created_at' => '2025-09-30T14:05:19+00:00',
    ];

    // When
    $author = $mapper->documentToObject($metadata, $authorDoc, $author); // @phpstan-ignore-line

     // Then
    expect($author)->toBeInstanceOf(TestAuthor::class) // @phpstan-ignore-line
        ->and($author->id)->toBe(1) // @phpstan-ignore-line
        ->and($author->name)->toBe('John Doe') // @phpstan-ignore-line
        ->and($author->createdAt)->toBeInstanceOf(\DateTimeImmutable::class) // @phpstan-ignore-line
        ->and($author->createdAt->format('Y-m-d H:i:s'))->toBe('2025-09-30 14:05:19'); // @phpstan-ignore-line
});

it('maps an object to a document', function () use ($transformers) {
    $mapper = new TestDocumentMapper(transformers: $transformers);
    $registry = new TestClassMetadataRegistry();
    $metadata = $registry->getClassMetadata(TestAuthor::class);

    // Given
    $author = new TestAuthor(1, 'John Doe');

    // When
    $authorDoc = $mapper->objectToDocument($metadata, $author); // @phpstan-ignore-line

    // Then
    expect($authorDoc)->toBeArray()
        ->toHaveKey('author_id', 1)
        ->toHaveKey('author_name', 'John Doe')
        ->toHaveKey('created_at', $author->createdAt?->format(DateTimeImmutable::ATOM));
});
