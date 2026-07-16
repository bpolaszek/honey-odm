<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

enum LogicalOperator: string
{
    case AND = 'and';
    case OR = 'or';
}
