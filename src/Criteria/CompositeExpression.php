<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use function array_values;

final readonly class CompositeExpression implements ExpressionInterface
{
    /**
     * @var non-empty-list<ExpressionInterface>
     */
    public array $expressions;

    public function __construct(
        public LogicalOperator $operator,
        ExpressionInterface $expression,
        ExpressionInterface ...$expressions,
    ) {
        $this->expressions = [$expression, ...array_values($expressions)];
    }

    public static function and(ExpressionInterface $expression, ExpressionInterface ...$expressions): self
    {
        return new self(LogicalOperator::AND, $expression, ...$expressions);
    }

    public static function or(ExpressionInterface $expression, ExpressionInterface ...$expressions): self
    {
        return new self(LogicalOperator::OR, $expression, ...$expressions);
    }
}
