<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

function field(string $property): Field
{
    return new Field($property);
}

function not(ExpressionInterface $expression): Negation
{
    return new Negation($expression);
}
