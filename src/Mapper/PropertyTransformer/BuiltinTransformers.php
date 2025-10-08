<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Exception;
use IteratorAggregate;
use Psr\Container\ContainerInterface;
use Traversable;

/**
 * @implements IteratorAggregate<string, PropertyTransformerInterface>
 */
final readonly class BuiltinTransformers implements ContainerInterface, IteratorAggregate
{
    /**
     * @param array<string, PropertyTransformerInterface> $transformers
     */
    public function __construct(
        private array $transformers = [
            DateTimeImmutableTransformer::class => new DateTimeImmutableTransformer(),
            RelationTransformer::class => new RelationTransformer(),
            RelationsTransformer::class => new RelationsTransformer(),
        ],
    ) {
    }

    public function get(string $id)
    {
        return $this->transformers[$id] ?? throw new class ("Service $id not found") extends Exception {
        };
    }

    public function has(string $id): bool
    {
        return isset($this->transformers[$id]); // @codeCoverageIgnore
    }

    public function getIterator(): Traversable
    {
        yield from $this->transformers; // @codeCoverageIgnore
    }
}
