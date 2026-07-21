<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria;

enum Operator: string
{
    case EQUALS = 'eq';
    case NOT_EQUALS = 'neq';
    case GREATER_THAN = 'gt';
    case GREATER_THAN_OR_EQUALS = 'gte';
    case LESS_THAN = 'lt';
    case LESS_THAN_OR_EQUALS = 'lte';
    case IN = 'in';
    case NOT_IN = 'nin';
    case CONTAINS = 'contains';
    case STARTS_WITH = 'startsWith';
    case ENDS_WITH = 'endsWith';
    case HAS_ALL = 'hasAll';
    case IS_NULL = 'isNull';
    case IS_NOT_NULL = 'isNotNull';
    case EXISTS = 'exists';
    case IS_EMPTY = 'isEmpty';
    case BETWEEN = 'between';
    case WITHIN_GEO_RADIUS = 'withinGeoRadius';
    case WITHIN_GEO_BOUNDING_BOX = 'withinGeoBoundingBox';
}
