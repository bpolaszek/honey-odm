# Honey ODM Core

A framework-agnostic, core foundation library for building modern Object Document Mappers (ODM) in PHP.

[![CI Workflow](https://github.com/bpolaszek/honey-odm/actions/workflows/ci-workflow.yml/badge.svg)](https://github.com/bpolaszek/honey-odm/actions/workflows/ci-workflow.yml)
[![codecov](https://codecov.io/gh/bpolaszek/honey-odm/branch/main/graph/badge.svg)](https://codecov.io/gh/bpolaszek/honey-odm)

## Overview

Honey ODM Core provides the essential interfaces, components, and patterns needed to build robust ODMs that can work with various data sources like REST APIs, NoSQL databases, or any custom storage backend. The library focuses on providing a solid foundation with built-in features like property transformers, event mechanisms, and identity management.

## Key Features

- **Generic Interface Design**: Core interfaces that can be implemented for any data source
- **Built-in Property Transformers**: Automatic data transformation between storage and PHP objects
- **Event System**: Comprehensive lifecycle events (pre/post persist, update, remove, load)
- **Identity Management**: Automatic object identity tracking and management
- **Unit of Work Pattern**: Efficient batch operations and change tracking
- **Trait-based Implementation**: Ready-to-use traits that simplify implementation

## Requirements

- PHP 8.4 or higher
- (Optional) PSR-14 Event Dispatcher implementation
- (Optional) PSR-11 Container implementation


## Building your own ODM

Init your ODM project with Composer, then require Honey ODM Core:

```bash
composer require honey-odm/core
```

### Glossary

- **Class Metadata**: Metadata about a document class (e.g. endpoint / bucket / table name, whatever)
- **Property Metadata**: Metadata about a document property (e.g. name, transformer, is primary key, etc.)
- **Transport**: Handles communication with your data source
- **Object Manager**: Central component that orchestrates all ODM operations and events
- **Unit of Work**: Tracks changes and scheduled actions (insert, update, delete). The Unit of Work is destructed and recreated after each flush operation.
- **Object Repository**: Provides repository pattern methods for retrieving documents as objects.

### Essential Components

To build an ODM using Honey ODM Core, you need to extend these abstract classes and implement these core interfaces:

#### ClassMetadata

Class (attribute) that holds metadata information about your document classes, example:

```php
namespace MyODM\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
final class DocumentMetadata extends ClassMetadata
{
    public function __construct(
        public ?string $endpoint = null, // <-- That's an example, depending on your own implementation
    ) {
    }
}
```

#### PropertyMetadata

Class (attribute) that defines metadata for individual properties, example:

```php
namespace MyODM\Config;

use Attribute;
use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Config\TransformerMetadataInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class TestAsField extends PropertyMetadata
{
    public function __construct(
        public readonly bool $primary = false, // <-- You must implement a `$primary` property, it will be used for identity management
        protected TransformerMetadataInterface|string|null $transformer = null, // <-- You can allow property transformers usage
    ) {
    }
}
```

#### ClassMetadataRegistry

Service responsible for retrieving metadata about your document classes.

```php
namespace MyODM\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryTrait;

final class ClassMetadataRegistry implements ClassMetadataRegistryInterface
{
    use ClassMetadataRegistryTrait; // <-- We've done most of the hard work for you

    public function getIdFromObject(object $object): mixed
    {
        // Write your own logic to retrieve the ID of a document from an instantiated object 
    }

    public function getIdFromDocument(array $document, string $className): mixed
    {
        // Write your own logic to retrieve the ID of a document from an array
        // You can call $this->getClassMetadata($className) to get the ClassMetadata for the given class
    }
}
```

#### DocumentMapper

Service responsible for mapping documents (arrays) to objects and vice versa.

```php
namespace MyODM\Mapper;

use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Mapper\DocumentMapperTrait;

final readonly class DocumentMapper implements DocumentMapperInterface
{
    use DocumentMapperTrait; // <-- That's it - the default implementation leverages Symfony's PropertyAccess component
}
```

#### TransportInterface

Handles communication with your data source:

```php
interface TransportInterface
{
    public function retrieveDocuments(mixed $criteria): iterable;
    public function retrieveDocumentById(ClassMetadata $classMetadata, mixed $id): ?array;
    public function flushPendingOperations(UnitOfWork $unitOfWork): void;
}
```

Important: 
- `$criteria` depends on your own implementation. It is your role to translate it into a query that your data source can understand.
- `retrieveDocuments` can return any type of document collections. It can be a simple array of arrays, a `Generator`, or any other type of collection (with metadata such as facets, aggregations, etc).
- Important: documents must be returned as associative arrays. The `Transport` is not responsible for converting them to objects.
- In `flushPendingOperations`, you'll read the Unit of Work for scheduled insertions / updates / deletions and perform the necessary operations.

#### ObjectRepositoryInterface

Provides repository pattern methods:

```php
interface ObjectRepositoryInterface
{
    public function findBy(mixed $criteria): iterable;
    public function findAll(): iterable;
    public function findOneBy(mixed $criteria): ?object;
    public function find(mixed $id): ?object;
}
```

Your `ObjectRepository` implementation will likely depend on the `ObjectManager`:
- `$objectManager->transport` will give you access to the transport layer to retrieve documents as raw arrays
- `$objectManager->classMetadataRegistry` will help you retrieve metadata about your document classes
- `$objectManager->factory()` will instantiate (or reuse) objects from the documents returned by the transport layer

#### ObjectManager

Once you have implemented the above components, you can implement your own ObjectManager:

```php

namespace MyODM\Manager;

use Honey\ODM\Core\Manager\ObjectManager as BaseObjectManager
use MyODM\Repository\MyObjectRepository; // <-- Your repository implementation

final class ObjectManager extends BaseObjectManager {

    public function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className]
            ??= $this->registerRepository($className, new MyObjectRepository($this, $className));
    }
}
```

The `ObjectManager` is the central component that orchestrates all ODM operations:

```php
namespace App;

use MyODM\Manager\ObjectManager;

$objectManager = new ObjectManager(
    $classMetadataRegistry, // <-- Your ClassMetadataRegistry implementation
    $documentMapper, // <-- Your DocumentMapper implementation
    $eventDispatcher, // <-- A PSR-14 Event Dispatcher implementation
    $transport, // <-- Your Transport implementation
);

// Persist objects
$objectManager->persist($object);
$objectManager->flush();

// Retrieve objects
$object = $objectManager->find(MyEntity::class, $id);
$repository = $objectManager->getRepository(MyEntity::class)->findBy(['id' => $id]); // <-- Repository pattern
```

## Example Implementation: RESTful API ODM

Here's a complete example of building an ODM that consumes a RESTful API:

### 1. Imagine your user entities

```php
<?php

namespace App;

use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use RestBookODM\AsDocument;
use RestBookODM\AsField;

#[AsDocument(endpoint: '/api/books')]
final class Book
{
    public function __construct(
        #[AsField(primary: true)]
        public string $id,
        
        #[AsField(name: 'title')]
        public string $title,
        
        #[AsField(name: 'author_id', transformer: new TransformerMetadata(RelationTransformer::class))]
        public ?Author $author = null,
        
        #[AsField(name: 'published_at', transformer: 'datetime')]
        public ?DateTimeImmutable $publishedAt = null,
    ) {}
}

#[AsDocument(endpoint: '/api/authors')]
final class Author
{
    public function __construct(
        #[AsField(primary: true)]
        public string $id,
        
        #[AsField(name: 'name')]
        public string $name,
        
        #[AsField(name: 'email')]
        public string $email,
    ) {}
}
```

### 2. Create Metadata Attributes

```php
<?php

namespace RestBookODM;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Config\PropertyMetadata;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument extends ClassMetadata
{
    public function __construct(
        public readonly string $endpoint,
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
final class AsField extends PropertyMetadata
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $primary = false,
        protected TransformerMetadataInterface|string|null $transformer = null,
    ) {}
}
```

### 3. Implement REST Transport

```php
<?php

namespace RestBookODM;

use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use GuzzleHttp\Client;

final class RestTransport implements TransportInterface
{
    public function __construct(
        private Client $httpClient,
        private string $baseUrl,
    ) {}

    public function flushPendingOperations(UnitOfWork $unitOfWork): void
    {
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        // Handle upserts (create/update)
        foreach ($unitOfWork->getPendingUpserts() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $context = new MappingContext($classMetadata, $objectManager, $object, []);
            $document = $mapper->objectToDocument($object, [], $context);
            
            $id = $classMetadataRegistry->getIdFromObject($object);
            $endpoint = $this->baseUrl . $classMetadata->endpoint;
            
            if ($id) {
                // Update existing
                $this->httpClient->put("{$endpoint}/{$id}", ['json' => $document]);
            } else {
                // Create new
                $response = $this->httpClient->post($endpoint, ['json' => $document]);
                $data = json_decode($response->getBody()->getContents(), true);
                // Set the generated ID back to the object
                $idProperty = $classMetadata->getIdPropertyMetadata()->reflection;
                $idProperty->setValue($object, $data['id']);
            }
        }

        // Handle deletes
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $id = $classMetadataRegistry->getIdFromObject($object);
            $endpoint = $this->baseUrl . $classMetadata->endpoint;
            
            $this->httpClient->delete("{$endpoint}/{$id}");
        }
    }

    public function retrieveDocuments(mixed $criteria): iterable
    {
        // Implementation depends on your API's query capabilities
        // This is a simplified example
        throw new LogicException('Query implementation depends on your specific API');
    }

    public function retrieveDocumentById(ClassMetadata $classMetadata, mixed $id): ?array
    {
        $endpoint = $this->baseUrl . $classMetadata->endpoint;
        
        try {
            $response = $this->httpClient->get("{$endpoint}/{$id}");
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            if ($e->getResponse()?->getStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }
}
```

### 4. Set Up the ODM

```php
<?php

namespace APp;

use RestBookODM\ObjectManager;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcher;

// Create HTTP client
$httpClient = new Client([
    'timeout' => 30,
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
]);

// Set up components
$transport = new RestTransport($httpClient, 'https://api.example.com');
$eventDispatcher = new EventDispatcher();
$classMetadataRegistry = new ClassMetadataRegistry(); // <-- Your implementation
$documentMapper = new DocumentMapper(); // <-- Your implementation

// Create ObjectManager
$objectManager = new ObjectManager(
    $classMetadataRegistry,
    $documentMapper,
    $eventDispatcher,
    $transport
);

// Use the ODM
$book = new Book(
    id: 123456,
    title: 'The Great Gatsby',
    publishedAt: new DateTimeImmutable('1925-04-10')
);

$objectManager->persist($book);
$objectManager->flush(); // Makes HTTP POST to /api/books

// Retrieve data
$foundBook = $objectManager->find(Book::class, $book->id); // Makes HTTP GET
```

## Built-in Features

### Property Transformers

The library includes several built-in transformers:

- **DateTimeImmutableTransformer**: Handles DateTime objects
- **RelationTransformer**: Manages object relationships
- **Custom transformers**: Implement `PropertyTransformerInterface`

### Event System

Listen to object lifecycle events:

```php
use Honey\ODM\Core\Event\PrePersistEvent;

$eventDispatcher->addListener(PrePersistEvent::class, function (PrePersistEvent $event) {
    $object = $event->object;
    // Modify object before persistence
});
```

Available events:
- `PrePersistEvent` / `PostPersistEvent`
- `PreUpdateEvent` / `PostUpdateEvent`  
- `PreRemoveEvent` / `PostRemoveEvent`
- `PostLoadEvent` (when an object is retrieved from the persistence layer)

### Identity Management

Objects are automatically tracked and managed:

```php
$book1 = $objectManager->find(Book::class, '123');
$book2 = $objectManager->find(Book::class, '123');

var_dump($book1 === $book2); // true - same instance returned
```

## Contributing

We welcome contributions! Here's how to get started:

### Development Setup

1. Clone the repository:
```bash
git clone https://github.com/bpolaszek/honey-odm.git
cd honey-odm
```

2. Install dependencies:
```bash
composer install
```

3. Run checks:
```bash
composer ci:check
```

### Testing

The library uses Pest for testing. Tests are located in the `tests/` directory:

- `tests/Unit/` - Unit tests
- `tests/Behavior/` - Behavioral tests
- `tests/Implementation/` - Example implementation (great for understanding usage patterns)

Run the full test suite:
```bash
composer tests:run
```

### Code Standards

- Follow PSR-12 coding standards
- Use strict types (`declare(strict_types=1)`)
- Maintain 100% test coverage
- Use PHPStan level 9 for static analysis

### Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with tests
4. Ensure all checks pass (`composer ci:check`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Reporting Issues

Please use GitHub Issues to report bugs or request features. Include:

- PHP version
- Library version
- Clear description of the issue
- Code examples to reproduce the problem

## Known Implementations

- [honey-odm/meilisearch](https://github.com/bpolaszek/honey-meilisearch) - A Meilisearch ODM

## License

MIT.
