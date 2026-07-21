<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Transport;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\Geo\BoundingBox;
use Honey\ODM\Core\Criteria\Geo\Radius;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Negation;
use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\Range;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use SortDirection;

use function array_all;
use function array_any;
use function array_filter;
use function array_key_exists;
use function array_merge;
use function array_slice;
use function array_values;
use function asin;
use function cos;
use function deg2rad;
use function in_array;
use function is_array;
use function is_string;
use function sin;
use function sqrt;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strtolower;
use function usort;

/**
 * In-memory reference implementation of a Honey ODM transport.
 */
final class TestTransport implements TransportInterface
{
    /**
     * @var array<string, mixed>
     */
    private(set) array $passedFlushOptions = [];

    /**
     * @var array<string, array<int|string, array<string, mixed>>>
     */
    public array $storage = [];

    public function flushPendingOperations(UnitOfWork $unitOfWork, array $flushOptions = []): void
    {
        $this->passedFlushOptions = $flushOptions;
        $objectManager = $unitOfWork->objectManager;
        $classMetadataRegistry = $objectManager->classMetadataRegistry;
        $mapper = $objectManager->documentMapper;

        foreach ($unitOfWork->getPendingUpserts() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $id = $classMetadataRegistry->getIdFromObject($object);
            $collection = (string) $classMetadata->collection;
            $context = new MappingContext($classMetadata, $objectManager, $object, []);
            $document = $mapper->objectToDocument($object, [], $context);
            $this->storage[$collection][$id] = array_merge($this->storage[$collection][$id] ?? [], $document);
        }
        foreach ($unitOfWork->getPendingDeletes() as $object) {
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $id = $classMetadataRegistry->getIdFromObject($object);
            unset($this->storage[(string) $classMetadata->collection][$id]);
        }
    }

    public function retrieveDocuments(AsDocument $classMetadata, Criteria $criteria): iterable
    {
        $documents = array_values($this->storage[(string) $classMetadata->collection] ?? []);

        if (null !== $criteria->search) {
            $search = strtolower($criteria->search);
            $documents = array_filter(
                $documents,
                fn (array $document) => array_any(
                    $document,
                    fn (mixed $value) => is_string($value) && str_contains(strtolower($value), $search),
                ),
            );
        }

        $where = $criteria->where;
        if (null !== $where) {
            $documents = array_filter(
                $documents,
                fn (array $document) => $this->matches($where, $document, $classMetadata),
            );
        }

        if ([] !== $criteria->orderBy) {
            usort($documents, function (array $a, array $b) use ($criteria, $classMetadata): int {
                foreach ($criteria->orderBy as $sort) {
                    $fieldName = $classMetadata->getFieldName($sort->property);
                    $result = ($a[$fieldName] ?? null) <=> ($b[$fieldName] ?? null);
                    if (SortDirection::Descending === $sort->direction) {
                        $result = -$result;
                    }
                    if (0 !== $result) {
                        return $result;
                    }
                }

                return 0;
            });
        }

        return array_slice(array_values($documents), $criteria->offset, $criteria->limit);
    }

    /**
     * @param AsDocument<object> $classMetadata
     */
    public function retrieveDocumentById(AsDocument $classMetadata, mixed $id): ?array
    {
        return $this->storage[(string) $classMetadata->collection][$id] ?? null;
    }

    /**
     * @param array<string, mixed> $document
     * @param AsDocument<object> $classMetadata
     */
    private function matches(ExpressionInterface $expression, array $document, AsDocument $classMetadata): bool
    {
        return match (true) {
            $expression instanceof Comparison => self::compare($expression, $document, $classMetadata),
            $expression instanceof CompositeExpression => match ($expression->operator) {
                LogicalOperator::AND => array_all(
                    $expression->expressions,
                    fn (ExpressionInterface $e) => $this->matches($e, $document, $classMetadata),
                ),
                LogicalOperator::OR => array_any(
                    $expression->expressions,
                    fn (ExpressionInterface $e) => $this->matches($e, $document, $classMetadata),
                ),
            },
            $expression instanceof Negation => !$this->matches($expression->expression, $document, $classMetadata),
            default => throw UnsupportedExpressionException::expression($expression),
        };
    }

    /**
     * @param array<string, mixed> $document
     * @param AsDocument<object> $classMetadata
     */
    private static function compare(Comparison $comparison, array $document, AsDocument $classMetadata): bool
    {
        $fieldName = $classMetadata->getFieldName($comparison->property);

        // Resolved before the value itself: a missing key and a null value are indistinguishable afterwards.
        if (Operator::EXISTS === $comparison->operator) {
            return array_key_exists($fieldName, $document);
        }

        $value = $document[$fieldName] ?? null;

        return match ($comparison->operator) {
            Operator::EQUALS => 0 === ($value <=> $comparison->value),
            Operator::NOT_EQUALS => 0 !== ($value <=> $comparison->value),
            Operator::GREATER_THAN => 1 === ($value <=> $comparison->value),
            Operator::GREATER_THAN_OR_EQUALS => -1 !== ($value <=> $comparison->value),
            Operator::LESS_THAN => -1 === ($value <=> $comparison->value),
            Operator::LESS_THAN_OR_EQUALS => 1 !== ($value <=> $comparison->value),
            Operator::IN => self::holdsAny($value, (array) $comparison->value),
            Operator::NOT_IN => !self::holdsAny($value, (array) $comparison->value),
            Operator::HAS_ALL => self::holdsAll($value, (array) $comparison->value),
            Operator::CONTAINS => is_string($value) && str_contains($value, (string) $comparison->value), // @phpstan-ignore cast.string
            Operator::STARTS_WITH => is_string($value) && str_starts_with($value, (string) $comparison->value), // @phpstan-ignore cast.string
            Operator::ENDS_WITH => is_string($value) && str_ends_with($value, (string) $comparison->value), // @phpstan-ignore cast.string
            Operator::IS_NULL => null === $value,
            Operator::IS_NOT_NULL => null !== $value,
            Operator::IS_EMPTY => null === $value || '' === $value || [] === $value,
            Operator::BETWEEN => self::within($value, $comparison->value), // @phpstan-ignore argument.type
            Operator::WITHIN_GEO_RADIUS => self::withinRadius($value, $comparison->value), // @phpstan-ignore argument.type
            Operator::WITHIN_GEO_BOUNDING_BOX => self::withinBoundingBox($value, $comparison->value), // @phpstan-ignore argument.type
            Operator::EXISTS => true, // Unreachable - handled above
        };
    }

    /**
     * On an array field, matches when at least one of the given values is held.
     *
     * @param list<mixed> $values
     */
    private static function holdsAny(mixed $value, array $values): bool
    {
        return match (is_array($value)) {
            true => array_any($values, static fn (mixed $expected) => in_array($expected, $value, true)),
            false => in_array($value, $values, true),
        };
    }

    /**
     * On an array field, matches when every given value is held.
     *
     * @param list<mixed> $values
     */
    private static function holdsAll(mixed $value, array $values): bool
    {
        $held = is_array($value) ? $value : [$value];

        return array_all($values, static fn (mixed $expected) => in_array($expected, $held, true));
    }

    private static function within(mixed $value, Range $range): bool
    {
        if (null === $value) {
            return false; // A range never matches a null value
        }

        if (null !== $range->left) {
            $comparison = $value <=> $range->left;
            if ($range->includeLeft ? $comparison < 0 : $comparison <= 0) {
                return false;
            }
        }

        if (null !== $range->right) {
            $comparison = $value <=> $range->right;
            if ($range->includeRight ? $comparison > 0 : $comparison >= 0) {
                return false;
            }
        }

        return true;
    }

    private static function withinRadius(mixed $value, Radius $radius): bool
    {
        $point = self::point($value);
        if (null === $point) {
            return false;
        }

        [$latitude, $longitude] = $point;
        $distance = self::haversine(
            $latitude,
            $longitude,
            $radius->center->latitude,
            $radius->center->longitude,
        );

        return $distance <= $radius->meters;
    }

    private static function withinBoundingBox(mixed $value, BoundingBox $boundingBox): bool
    {
        $point = self::point($value);
        if (null === $point) {
            return false;
        }

        [$latitude, $longitude] = $point;
        if ($latitude < $boundingBox->southWest->latitude || $latitude > $boundingBox->northEast->latitude) {
            return false;
        }

        $west = $boundingBox->southWest->longitude;
        $east = $boundingBox->northEast->longitude;

        return match ($west <= $east) {
            true => $longitude >= $west && $longitude <= $east,
            false => $longitude >= $west || $longitude <= $east, // The box crosses the antimeridian
        };
    }

    /**
     * @return array{float, float}|null
     */
    private static function point(mixed $value): ?array
    {
        if (!is_array($value) || !isset($value['lat'], $value['lng'])) {
            return null;
        }

        return [(float) $value['lat'], (float) $value['lng']]; // @phpstan-ignore cast.double, cast.double
    }

    /**
     * Great-circle distance, in meters.
     */
    private static function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371008.8;
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);
        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLon / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }
}
