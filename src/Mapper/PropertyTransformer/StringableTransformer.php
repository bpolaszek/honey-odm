<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use BenTools\ReflectionPlus\Reflection;
use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use Stringable;

use function ltrim;

final readonly class StringableTransformer implements PropertyTransformerInterface
{
    public function fromDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): mixed {
        if (null === $value) {
            return null;
        }

        $settableType = $propertyMetadata->reflection->getSettableType();
        if (!$settableType instanceof ReflectionNamedType) {
            throw new RuntimeException("Invalid type for property {$propertyMetadata->reflection->name}.");
        }

        $settableType = ltrim((string) $settableType, '?');
        try {
            Reflection::class($settableType)->getMethod('fromString');

            return $settableType::fromString($value);
        } catch (ReflectionException) {
            throw new RuntimeException("Failed to retrieve `fromString` method for type {$settableType}.");
        }
    }

    public function toDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): ?string {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Stringable) {
            throw new RuntimeException('Value must be an instance of Stringable.');
        }

        return (string) $value;
    }
}
