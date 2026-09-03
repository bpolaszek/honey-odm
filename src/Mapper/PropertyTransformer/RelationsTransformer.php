<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use LogicException;

use function array_map;
use function class_exists;
use function get_debug_type;
use function is_array;
use function is_string;
use function sprintf;

final class RelationsTransformer implements PropertyTransformerInterface
{
    /**
     * @return array<object|null>|null a dangling id yields a null entry, as `find()` does for a single relation
     */
    public function fromDocument(
        mixed $value,
        AsField $propertyMetadata,
        MappingContextInterface $context,
    ): ?array {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new LogicException(sprintf('Expected array, got %s', get_debug_type($value))); // @codeCoverageIgnore
        }

        $targetClass = $propertyMetadata->getTransformer()->options['target_class']
            ?? throw new LogicException('`target_class` option not provided.'); // @codeCoverageIgnore

        if (!is_string($targetClass) || !class_exists($targetClass)) {
            throw new LogicException('Invalid target class.'); // @codeCoverageIgnore
        }

        return array_map(static fn (mixed $v) => $context->objectManager->find($targetClass, $v), $value);
    }

    /**
     * @param object[]|null $value
     */
    // @phpstan-ignore missingType.iterableValue
    public function toDocument(
        mixed $value,
        AsField $propertyMetadata,
        MappingContextInterface $context,
    ): ?array {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new LogicException(sprintf('Expected array, got %s', get_debug_type($value))); // @codeCoverageIgnore
        }

        return array_map(
            static fn (object $object) => $context->objectManager->getIdentifier($object),
            $value,
        );
    }
}
