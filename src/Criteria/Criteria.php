<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use InvalidArgumentException;
use SortDirection;

use function strtolower;

final class Criteria
{
    public private(set) ?ExpressionInterface $where = null;

    /**
     * @var list<Sort>
     */
    public private(set) array $orderBy = [];

    public private(set) ?string $search = null;

    /**
     * @var non-negative-int|null
     */
    public private(set) ?int $limit = null;

    /**
     * @var non-negative-int
     */
    public private(set) int $offset = 0;

    /**
     * Platform-specific criteria (vectors, images, ...), for implementations to handle or ignore.
     *
     * @var array<string, mixed>
     */
    public private(set) array $metadata = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Builds criteria from an array of equality filters (property => value), AND-combined.
     *
     * @param array<string, mixed> $filters
     */
    public static function fromArray(array $filters): self
    {
        $criteria = new self();
        foreach ($filters as $property => $value) {
            $criteria->andWhere(new Comparison($property, Operator::EQUALS, $value));
        }

        return $criteria;
    }

    /**
     * Full-text search term - interpreted by search-capable platforms.
     */
    public function search(?string $search): self
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Replaces the filter with the given expression(s), AND-combined.
     */
    public function where(ExpressionInterface $expression, ExpressionInterface ...$expressions): self
    {
        $this->where = self::combine($expression, ...$expressions);

        return $this;
    }

    /**
     * AND-combines the given expression(s) with the current filter.
     */
    public function andWhere(ExpressionInterface $expression, ExpressionInterface ...$expressions): self
    {
        $combined = self::combine($expression, ...$expressions);
        $this->where = match ($this->where) {
            null => $combined,
            default => CompositeExpression::and($this->where, $combined),
        };

        return $this;
    }

    /**
     * OR-combines the given expression(s) with the current filter.
     */
    public function orWhere(ExpressionInterface $expression, ExpressionInterface ...$expressions): self
    {
        $combined = self::combine($expression, ...$expressions);
        $this->where = match ($this->where) {
            null => $combined,
            default => CompositeExpression::or($this->where, $combined),
        };

        return $this;
    }

    /**
     * @param SortDirection|'asc'|'desc' $direction
     */
    public function orderBy(string $property, SortDirection|string $direction = SortDirection::Ascending): self
    {
        if (!$direction instanceof SortDirection) {
            $direction = match (strtolower($direction)) {
                'asc' => SortDirection::Ascending,
                'desc' => SortDirection::Descending,
                default => throw new InvalidArgumentException("Invalid sort direction `$direction`."),
            };
        }
        $this->orderBy[] = new Sort($property, $direction);

        return $this;
    }

    /**
     * @param non-negative-int|null $limit
     */
    public function limit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param non-negative-int $offset
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Attaches a platform-specific criterion. Implementations that don't know the key ignore it.
     */
    public function metadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    private static function combine(
        ExpressionInterface $expression,
        ExpressionInterface ...$expressions,
    ): ExpressionInterface {
        return match ($expressions) {
            [] => $expression,
            default => CompositeExpression::and($expression, ...$expressions),
        };
    }
}
