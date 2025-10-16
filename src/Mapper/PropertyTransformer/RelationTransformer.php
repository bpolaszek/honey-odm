<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use LogicException;
use ReflectionNamedType;

use function is_object;
use function ltrim;

final class RelationTransformer implements PropertyTransformerInterface
{
    public function fromDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): mixed {
        if (null === $value) {
            return null;
        }
        $targetClass = $propertyMetadata->getTransformer()->options['target_class'] ?? null;
        if (!$targetClass) {
            $reflType = $propertyMetadata->reflection->getSettableType();
            if (!$reflType instanceof ReflectionNamedType || $reflType->isBuiltin()) {
                throw new LogicException('Invalid target class.'); // @codeCoverageIgnore
            }
            $targetClass = ltrim($reflType->getName(), '?');
        }

        return $context->objectManager->find($targetClass, $value); // @phpstan-ignore argument.templateType
    }

    public function toDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): mixed {
        if (null === $value) {
            return null;
        }

        if (!is_object($value)) {
            throw new LogicException(sprintf('Invalid type for %s::%s', $propertyMetadata->classMetadata->className, $propertyMetadata->reflection->name)); // @codeCoverageIgnore
        }

        $classMetadataRegistry = $context->objectManager->classMetadataRegistry;
        $propertyAccessor = $classMetadataRegistry->propertyAccessor;
        $classMetadata = $classMetadataRegistry->getClassMetadata($value::class);
        $idPropMetadata = $classMetadata->getIdPropertyMetadata();

        return $propertyAccessor->getValue($value, $idPropMetadata->reflection->name);
    }
}
