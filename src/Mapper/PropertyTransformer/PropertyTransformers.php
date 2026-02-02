<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use IteratorAggregate;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SplObjectStorage;
use Traversable;

use function array_find;
use function Honey\ODM\Core\throws;

/**
 * @implements IteratorAggregate<string, PropertyTransformerInterface>
 */
final readonly class PropertyTransformers implements ContainerInterface, IteratorAggregate
{
    /**
     * @var SplObjectStorage<PropertyTransformerInterface, null>
     */
    private SplObjectStorage $transformers;

    /**
     * @param PropertyTransformerInterface[] $transformers
     */
    public function __construct(
        iterable $transformers = [
            new StringableTransformer(),
            new DateTimeImmutableTransformer(),
            new BackedEnumTransformer(),
            new RelationTransformer(),
            new RelationsTransformer(),
        ],
    ) {
        $this->transformers = new SplObjectStorage();
        foreach ($transformers as $transformer) {
            $this->register($transformer);
        }
    }

    public function register(PropertyTransformerInterface $transformer): void
    {
        $this->transformers->attach($transformer);
    }

    public function get(string $id): mixed
    {
        return array_find([...$this], fn (PropertyTransformerInterface $transformer) => $transformer::class === $id)
            ?? throw new RuntimeException("Service $id not found");
    }

    public function has(string $id): bool
    {
        return !throws(fn () => $this->get($id));
    }

    public function getIterator(): Traversable
    {
        foreach ($this->transformers as $transformer) {
            yield $transformer::class => $transformer;
        }
    }
}
