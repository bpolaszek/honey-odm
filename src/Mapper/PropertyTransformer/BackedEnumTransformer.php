<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use BackedEnum;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use LogicException;
use ReflectionNamedType;

use function get_debug_type;
use function is_a;
use function is_int;
use function is_string;
use function ltrim;
use function sprintf;

final readonly class BackedEnumTransformer implements PropertyTransformerInterface
{
    /**
     * @param int|string|null $value
     */
    public function fromDocument(
        mixed $value,
        AsField $propertyMetadata,
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

        if (!is_string($targetClass) || !is_a($targetClass, BackedEnum::class, true)) {
            throw new LogicException('Invalid target class.'); // @codeCoverageIgnore
        }
        if (!is_int($value) && !is_string($value)) {
            throw new LogicException(sprintf('Expected int|string, got %s', get_debug_type($value))); // @codeCoverageIgnore
        }

        return $targetClass::from($value);
    }

    /**
     * @param BackedEnum|null $value
     */
    public function toDocument(
        mixed $value,
        AsField $propertyMetadata,
        MappingContextInterface $context,
    ): int|string|null {
        if (null === $value) {
            return null;
        }

        return $value->value;
    }
}
