<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Transport;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Criteria\Comparison;
use Honey\ODM\Core\Criteria\CompositeExpression;
use Honey\ODM\Core\Criteria\Criteria;
use Honey\ODM\Core\Criteria\ExpressionInterface;
use Honey\ODM\Core\Criteria\LogicalOperator;
use Honey\ODM\Core\Criteria\Negation;
use Honey\ODM\Core\Criteria\Operator;
use Honey\ODM\Core\Criteria\UnsupportedExpressionException;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Transport\TransportInterface;
use Honey\ODM\Core\UnitOfWork\UnitOfWork;
use SortDirection;

use function array_all;
use function array_any;
use function array_filter;
use function array_merge;
use function array_slice;
use function array_values;
use function in_array;
use function is_string;
use function str_contains;
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
        $value = $document[$classMetadata->getFieldName($comparison->property)] ?? null;

        return match ($comparison->operator) {
            Operator::EQUALS => 0 === ($value <=> $comparison->value),
            Operator::NOT_EQUALS => 0 !== ($value <=> $comparison->value),
            Operator::GREATER_THAN => 1 === ($value <=> $comparison->value),
            Operator::GREATER_THAN_OR_EQUALS => -1 !== ($value <=> $comparison->value),
            Operator::LESS_THAN => -1 === ($value <=> $comparison->value),
            Operator::LESS_THAN_OR_EQUALS => 1 !== ($value <=> $comparison->value),
            Operator::IN => in_array($value, (array) $comparison->value, true),
            Operator::NOT_IN => !in_array($value, (array) $comparison->value, true),
            Operator::CONTAINS => is_string($value) && str_contains($value, (string) $comparison->value), // @phpstan-ignore cast.string
            Operator::STARTS_WITH => is_string($value) && str_starts_with($value, (string) $comparison->value), // @phpstan-ignore cast.string
            Operator::IS_NULL => null === $value,
            Operator::IS_NOT_NULL => null !== $value,
        };
    }
}
