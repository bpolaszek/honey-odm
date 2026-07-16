<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

use LogicException;

use function get_debug_type;
use function sprintf;

/**
 * Thrown by an implementation when it cannot compile an expression, operator or feature
 * of the generic Criteria into its native query language.
 */
final class UnsupportedExpressionException extends LogicException
{
    public static function expression(ExpressionInterface $expression): self
    {
        return new self(sprintf('Unsupported expression `%s`.', get_debug_type($expression)));
    }

    public static function operator(Operator $operator): self
    {
        return new self(sprintf('Unsupported operator `%s`.', $operator->name));
    }

    public static function feature(string $feature): self
    {
        return new self(sprintf('Unsupported feature `%s`.', $feature));
    }
}
