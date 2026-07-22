<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

/**
 * Marker interface for filter expressions.
 *
 * Implementations of the ODM compile expressions into their native query language,
 * and throw UnsupportedExpressionException when they cannot.
 * Platform packages may define their own expression types (e.g. geo queries).
 */
interface ExpressionInterface
{
}
