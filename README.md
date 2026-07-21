# 🐝 Honey / ODM

A framework-agnostic, core foundation library for building modern Object Document Mappers (ODM) in PHP.

[![CI Workflow](https://github.com/bpolaszek/honey-odm/actions/workflows/ci-workflow.yml/badge.svg)](https://github.com/bpolaszek/honey-odm/actions/workflows/ci-workflow.yml)
[![codecov](https://codecov.io/gh/bpolaszek/honey-odm/branch/main/graph/badge.svg)](https://codecov.io/gh/bpolaszek/honey-odm)

## Overview

Honey ODM provides the essential components and patterns needed to build robust ODMs on top of any data source:
REST APIs, search engines, NoSQL databases, or any custom storage backend.

The mapping layer is **portable**: a class annotated for Honey ODM can run on any implementation (Meilisearch, SQLite,
Elasticsearch, ...) without being re-mapped. Everything platform-specific lives in dedicated attributes, and querying
goes through a generic, compilable criteria model.

## Key Features

- **Portable mapping**: `#[AsDocument]` / `#[AsField]` are core attributes — no subclassing, no per-implementation attributes
- **Platform metadata**: implementations ship their own attributes, placed alongside the core ones
- **Platform-agnostic criteria**: a fluent query builder + expression AST that each transport compiles to its own dialect
- **Built-in property transformers**: dates, backed enums, relations, stringable value objects
- **Event system**: full lifecycle events (pre/post persist, update, remove, load)
- **Identity management**: objects are tracked, deduplicated, and lazily hydrated
- **Unit of Work**: change tracking and batched insert / update / delete operations

## Requirements

- PHP 8.4 or higher
- (Optional) PSR-14 Event Dispatcher implementation
- (Optional) PSR-11 Container implementation

## Installation

```bash
composer require honey-odm/core
```

> The library ships a polyfill for the native PHP 8.6 `SortDirection` enum, so sorting works on PHP 8.4+.

## Glossary

- **Class Metadata** (`#[AsDocument]`): metadata about a document class (collection name, platform metadata, properties)
- **Property Metadata** (`#[AsField]`): metadata about a document property (field name, primary key, transformer)
- **Platform Metadata**: implementation-specific configuration, attached to a class or a property
- **Transport**: handles communication with your data source, and compiles criteria into its native query language
- **Object Manager**: central component that orchestrates all ODM operations and events
- **Unit of Work**: tracks changes and scheduled actions (insert, update, delete). It is destructed and recreated after each flush.
- **Object Repository**: provides repository pattern methods for retrieving documents as objects

## Mapping your documents

Mapping relies on two **final** core attributes:

```php
namespace App;

use DateTimeInterface;
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;

#[AsDocument(collection: 'books')]
final class Book
{
    public function __construct(
        #[AsField(primary: true)]
        public string $id,

        #[AsField(name: 'title')]
        public string $name,

        #[AsField(name: 'author_id', transformer: RelationTransformer::class)]
        public ?Author $author = null,

        #[AsField(name: 'published_at', transformer: new TransformerMetadata(DateTimeImmutableTransformer::class, ['from_format' => 'Y-m-d', 'to_format' => 'Y-m-d']))]
        public ?DateTimeInterface $publishedAt = null,
    ) {
    }
}
```

- `collection` is the logical name of the storage container (index, table, bucket, endpoint, ... — up to the implementation).
- `AsField::$name` defaults to the PHP property name. The resolved value is exposed as `AsField::$fieldName`.
- Exactly one property must be flagged `primary: true`, otherwise metadata reading throws.
- `transformer` accepts either a service id (usually a class name) or a `TransformerMetadata` instance when you need options.

### Platform-specific metadata

Implementations provide their own attributes implementing `PlatformMetadataInterface`. They are placed alongside the
core attributes and collected by the registry:

```php
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Meilisearch\Config as Meili;

#[AsDocument(collection: 'books')]
#[Meili\Document(rankingRules: ['words', 'typo', 'sort'])]
final class Book
{
    public function __construct(
        #[AsField(primary: true)]
        #[Meili\Attribute(filterable: true, sortable: true)]
        public int $id,
    ) {
    }
}
```

Retrieve them from either level:

```php
$classMetadata = $objectManager->getClassMetadata(Book::class);

$classMetadata->getPlatformMetadata(Meili\Document::class)?->rankingRules;
$classMetadata->getPropertyMetadata('id')->getPlatformMetadata(Meili\Attribute::class)?->filterable;
```

The same class can therefore carry metadata for several platforms at once — each implementation simply ignores what
isn't addressed to it.

### External metadata

Classes you cannot annotate (third-party classes, anonymous classes) can be registered by providing their
`AsDocument` instance to the registry:

```php
use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\ClassMetadataRegistry;

$registry = new ClassMetadataRegistry(configurations: [
    Book::class => new AsDocument(collection: 'books'),
]);
```

Passing a plain list of class names instead warms up the registry eagerly:

```php
$registry = new ClassMetadataRegistry(configurations: [Book::class, Author::class]);
```

Otherwise, metadata is read lazily on first access. Note that external metadata replaces the **class-level** attribute
only: properties are still read from their `#[AsField]` attributes.

## Querying

Queries are expressed with the generic `Criteria` object, in **PHP property names**. Each transport compiles them into
its own native query language.

```php
use Honey\ODM\Core\Criteria\Criteria;

use function Honey\ODM\Core\Criteria\field;
use function Honey\ODM\Core\Criteria\not;

$criteria = Criteria::create()
    ->search('gatsby')
    ->where(field('publishedAt')->greaterThan('1920-01-01'))
    ->andWhere(field('author')->in([1, 2, 3]))
    ->orWhere(not(field('name')->startsWith('Draft:')))
    ->orderBy('publishedAt', 'desc')
    ->orderBy('name')
    ->limit(20)
    ->offset(40);

$books = $objectManager->getRepository(Book::class)->findBy($criteria);
```

Properties are named after their **PHP** name (`publishedAt`, not `published_at`), but values are compared against
their **storage** representation: property transformers are not applied to criteria values. In the example above,
`field('author')` is matched against author ids, since that's what the `author_id` field holds.

For simple equality filters, an array is enough — it is AND-combined:

```php
$repository->findBy(['name' => 'The Great Gatsby']);   // shorthand for Criteria::fromArray([...])
$repository->findOneBy(['id' => '123']);
$repository->findAll();
```

### Expressions

The expression tree is built from three node types, all implementing `ExpressionInterface`:

| Node                 | Built with                                                        |
|----------------------|-------------------------------------------------------------------|
| `Comparison`         | `field('property')->equals($value)`, or `new Comparison(...)`      |
| `CompositeExpression`| `CompositeExpression::and(...)` / `::or(...)`                      |
| `Negation`           | `not($expression)`                                                 |

Available operators (`Honey\ODM\Core\Criteria\Operator`):

| `Field` method                   | Operator                  |
|----------------------------------|---------------------------|
| `equals($value)`                 | `EQUALS`                  |
| `notEquals($value)`              | `NOT_EQUALS`              |
| `greaterThan($value)`            | `GREATER_THAN`            |
| `greaterThanOrEquals($value)`    | `GREATER_THAN_OR_EQUALS`  |
| `lessThan($value)`               | `LESS_THAN`               |
| `lessThanOrEquals($value)`       | `LESS_THAN_OR_EQUALS`     |
| `in(array $values)`              | `IN`                      |
| `notIn(array $values)`           | `NOT_IN`                  |
| `contains(string $value)`        | `CONTAINS`                |
| `startsWith(string $value)`      | `STARTS_WITH`             |
| `isNull()`                       | `IS_NULL`                 |
| `isNotNull()`                    | `IS_NOT_NULL`             |

`search()` is a full-text search term, only meaningful on search-capable platforms.

> `Criteria` is mutable and fluent: `where()` replaces the current filter, `andWhere()` / `orWhere()` combine with it.
> Clone it if you want to derive several queries from a common base.

### Capability mismatches

No platform supports everything. When a transport cannot compile an expression, an operator or a feature, it throws
`UnsupportedExpressionException`:

```php
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;

throw UnsupportedExpressionException::expression($expression);
throw UnsupportedExpressionException::operator(Operator::STARTS_WITH);
throw UnsupportedExpressionException::feature('offset');
```

## Using the Object Manager

```php
use Honey\ODM\Core\Manager\ObjectManager;

$objectManager = new ObjectManager(new MyTransport(...));
```

That's the only mandatory argument. Everything else is optional and has a sensible default:

```php
$objectManager = new ObjectManager(
    transport: $transport,
    classMetadataRegistry: new ClassMetadataRegistry(),  // default
    documentMapper: new DocumentMapper(),                // default
    eventDispatcher: $psr14EventDispatcher,              // defaults to a no-op dispatcher
    defaultFlushOptions: [],                             // implementation-specific options passed to the transport
    repositoryFactory: null,                             // Closure(ObjectManager, class-string): ObjectRepositoryInterface
);
```

### Persisting and retrieving

```php
$book = new Book(id: '1', name: 'The Great Gatsby');

$objectManager->persist($book);
$objectManager->flush();

$objectManager->remove($book);
$objectManager->flush(['wait' => true]); // options are merged with $defaultFlushOptions

$book = $objectManager->find(Book::class, '1');
$books = $objectManager->getRepository(Book::class)->findBy(['name' => 'The Great Gatsby']);

$objectManager->clear(); // detaches everything and resets the Unit of Work
```

Objects returned by `find()` / repositories are **lazy ghosts**: the document is only mapped to the object when one of
its properties is actually accessed.

### Repositories

By default, `getRepository()` returns the generic `ObjectRepository`. Implementations exposing native query
capabilities can provide their own default through the `repositoryFactory` closure:

```php
$objectManager = new ObjectManager(
    transport: $transport,
    repositoryFactory: fn (ObjectManager $om, string $className) => new MyRepository($om, $className),
);
```

You can also register a repository for a single class:

```php
$objectManager->registerRepository(Book::class, new BookRepository($objectManager, Book::class));
```

Repositories implement `ObjectRepositoryInterface`:

```php
interface ObjectRepositoryInterface
{
    public function findBy(Criteria|array|null $criteria): iterable;
    public function findAll(): iterable;
    public function findOneBy(Criteria|array $criteria): ?object;
    public function find(mixed $id): ?object;
}
```

### Identity management

Objects are tracked and deduplicated per class + id:

```php
$book1 = $objectManager->find(Book::class, '123');
$book2 = $objectManager->find(Book::class, '123');

var_dump($book1 === $book2); // true - same instance returned
```

Changes made on managed objects are detected at flush time by the Unit of Work — you don't need to `persist()` an
object that is already managed.

### Events

```php
use Honey\ODM\Core\Event\PrePersistEvent;

$eventDispatcher->addListener(PrePersistEvent::class, function (PrePersistEvent $event) {
    $event->object->createdAt = new DateTimeImmutable();
});
```

Available events:

- `PrePersistEvent` / `PostPersistEvent`
- `PreUpdateEvent` / `PostUpdateEvent`
- `PreRemoveEvent` / `PostRemoveEvent`
- `PostLoadEvent` (when an object is hydrated from the persistence layer — also exposes the raw `$document`)

Pre-flush events may modify objects: changesets are recomputed until they stabilize. Calling `flush()` from within a
listener is a no-op, to prevent recursion.

## Property transformers

Transformers convert values between the storage representation and PHP:

| Transformer                  | Purpose                                                                 | Options                                                     |
|------------------------------|-------------------------------------------------------------------------|-------------------------------------------------------------|
| `DateTimeImmutableTransformer` | Dates                                                                 | `from_format`, `from_tz`, `to_format`, `to_tz`, `to_type`   |
| `BackedEnumTransformer`      | Backed enums (target class inferred from the property type)              | `target_class`                                              |
| `RelationTransformer`        | To-one relation, stored as the related document id                       | `target_class`                                              |
| `RelationsTransformer`       | To-many relation, stored as a list of ids                                | `target_class` (required)                                   |
| `StringableTransformer`      | Value objects exposing `fromString()` and `__toString()` (e.g. `Ulid`)   | —                                                           |

Writing your own is a matter of implementing `PropertyTransformerInterface`:

```php
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformerInterface;

final class MoneyTransformer implements PropertyTransformerInterface
{
    public function fromDocument(mixed $value, AsField $propertyMetadata, MappingContextInterface $context): ?Money
    {
        return null === $value ? null : Money::fromCents($value);
    }

    public function toDocument(mixed $value, AsField $propertyMetadata, MappingContextInterface $context): ?int
    {
        return $value?->cents;
    }
}
```

Then register it in the transformers container passed to the mapper:

```php
use Honey\ODM\Core\Mapper\DocumentMapper;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformers;

$transformers = new PropertyTransformers();
$transformers->register(new MoneyTransformer());

$objectManager = new ObjectManager(
    transport: $transport,
    documentMapper: new DocumentMapper(transformers: $transformers),
);
```

`PropertyTransformers` is a PSR-11 container keyed by class name — any other PSR-11 container will do.

## Building your own ODM

Since metadata, mapping, criteria, repositories and the object manager are all provided by the core, an implementation
boils down to **one transport**, plus optional platform metadata attributes.

### 1. Implement the transport

```php
interface TransportInterface
{
    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void;

    /**
     * @param AsDocument<object> $classMetadata
     * @return iterable<array<string, mixed>>
     * @throws UnsupportedExpressionException
     */
    public function retrieveDocuments(AsDocument $classMetadata, Criteria $criteria): iterable;

    /**
     * @param AsDocument<object> $classMetadata
     * @return array<string, mixed>|null
     */
    public function retrieveDocumentById(AsDocument $classMetadata, mixed $id): ?array;
}
```

Important:

- Documents are exchanged as **associative arrays**. The transport never deals with objects — mapping is the mapper's job.
- `retrieveDocuments()` may return any iterable: a plain array, a `Generator`, or a richer collection carrying facets,
  aggregations, etc.
- `retrieveDocuments()` is where you compile the generic `Criteria` into your native query language. Use
  `$classMetadata->getFieldName($property)` to translate PHP property names into storage-side field names, and throw
  `UnsupportedExpressionException` for anything your platform can't express.
- In `flushPendingOperations()`, read the Unit of Work for scheduled operations and perform them.

`tests/Implementation/Transport/TestTransport.php` is a complete in-memory reference implementation (filters, negation,
composite expressions, multi-key sorting, pagination, search) — a good starting point for adapter authors.

### 2. Flushing pending operations

```php
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;

final class RestTransport implements TransportInterface
{
    public function __construct(
        private ClientInterface $httpClient,
        private string $baseUrl,
    ) {
    }

    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void
    {
        $objectManager = $unitOfWork->objectManager;
        $registry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        foreach ($unitOfWork->getPendingUpserts() as $object) {
            $classMetadata = $registry->getClassMetadata($object::class);
            $context = new MappingContext($classMetadata, $objectManager, $object, []);
            $document = $mapper->objectToDocument($object, [], $context);
            $id = $registry->getIdFromObject($object);
            $endpoint = $this->baseUrl . '/' . $classMetadata->collection;

            $this->httpClient->put("{$endpoint}/{$id}", ['json' => $document]);
        }

        foreach ($unitOfWork->getPendingDeletes() as $object) {
            $classMetadata = $registry->getClassMetadata($object::class);
            $id = $registry->getIdFromObject($object);

            $this->httpClient->delete("{$this->baseUrl}/{$classMetadata->collection}/{$id}");
        }
    }

    // retrieveDocuments() / retrieveDocumentById() omitted for brevity
}
```

The Unit of Work exposes `getPendingInserts()`, `getPendingUpdates()`, `getPendingUpserts()`, `getPendingDeletes()`,
`getChangedObjects()` and `getPendingOperation($object)` — use whichever granularity your backend needs (an API with a
dedicated `POST` for creations, a bulk endpoint, ...).

### 3. Add platform metadata (optional)

```php
namespace MyODM\Config;

use Attribute;
use Honey\ODM\Core\Config\PlatformMetadataInterface;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class Collection implements PlatformMetadataInterface
{
    public function __construct(
        public ?int $shards = null,
    ) {
    }
}
```

### 4. Wire everything

```php
$objectManager = new ObjectManager(new RestTransport($httpClient, 'https://api.example.com'));

$book = new Book(id: '1', name: 'The Great Gatsby');
$objectManager->persist($book);
$objectManager->flush(); // HTTP PUT /books/1

$foundBook = $objectManager->find(Book::class, '1'); // HTTP GET /books/1
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
- Use PHPStan level 8 for static analysis

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
