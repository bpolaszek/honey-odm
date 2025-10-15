<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use LogicException;

use function get_debug_type;
use function is_array;
use function sprintf;

final class RelationsTransformer implements PropertyTransformerInterface
{
    /**
     * @return object[]|null
     */
    public function fromDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
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

        return array_map(static fn (mixed $v) => $context->objectManager->find($targetClass, $v), $value); // @phpstan-ignore return.type, argument.templateType
    }

    /**
     * @param object[]|null $value
     */
    // @phpstan-ignore missingType.iterableValue
    public function toDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): ?array {
        if (null === $value) {
            return null;
        }

        if (!is_array($value)) {
            throw new LogicException(sprintf('Expected array, got %s', get_debug_type($value))); // @codeCoverageIgnore
        }

        $output = [];
        foreach ($value as $object) {
            $classMetadataRegistry = $context->objectManager->classMetadataRegistry;
            $propertyAccessor = $classMetadataRegistry->propertyAccessor;
            $classMetadata = $classMetadataRegistry->getClassMetadata($object::class);
            $idPropMetadata = $classMetadata->getIdPropertyMetadata();
            $output[] = $propertyAccessor->getValue($object, $idPropMetadata->reflection->name);
        }

        return $output;
    }
}
