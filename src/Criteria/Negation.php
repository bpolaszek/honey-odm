<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

final readonly class Negation implements ExpressionInterface
{
    public function __construct(
        public ExpressionInterface $expression,
    ) {
    }
}
