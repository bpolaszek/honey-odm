<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use BackedEnum;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use LogicException;
use ReflectionNamedType;

use function ltrim;

final readonly class BackedEnumTransformer implements PropertyTransformerInterface
{
    /**
     * @param int|string|null $value
     */
    public function fromDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): ?BackedEnum {
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

        return $targetClass::from($value);
    }

    /**
     * @param BackedEnum|null $value
     */
    public function toDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): int|string|null {
        if (null === $value) {
            return null;
        }

        return $value->value;
    }
}
