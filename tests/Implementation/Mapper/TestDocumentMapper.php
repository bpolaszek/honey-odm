<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Mapper;

use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\BuiltinTransformers;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @implements DocumentMapperInterface<array<string, mixed>, object>
 */
final readonly class TestDocumentMapper implements DocumentMapperInterface
{
    public function __construct(
        private PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
        private ContainerInterface $transformers = new BuiltinTransformers(),
    ) {
    }

    public function documentToObject(array $source, object $target, MappingContextInterface $context): object
    {
        $document = (object) $source;
        foreach ($context->classMetadata->propertiesMetadata as $propertyName => $propertyMetadata) {
            $sourcePropertyName = $propertyMetadata->name ?? $propertyName;
            /** @var PropertyTransformerInterface|null $transformer */
            $transformer = $propertyMetadata->transformer?->service ? $this->transformers->get($propertyMetadata->transformer->service) : null;
            try {
                $rawValue = $this->propertyAccessor->getValue($document, $sourcePropertyName);
                $value = match ($transformer) {
                    null => $rawValue,
                    default => $transformer->fromDocument(
                        $rawValue,
                        $propertyMetadata,
                        $context,
                    )
                };
                $this->propertyAccessor->setValue($target, $propertyName, $value);
            } catch (NoSuchPropertyException) {
            }
        }

        return $target;
    }

    /**
     * @param array<string, mixed> $target
     * @return array<string, mixed>
     */
    public function objectToDocument(object $source, array $target, MappingContextInterface $context): array // @phpstan-ignore missingType.generics
    {
        foreach ($context->classMetadata->propertiesMetadata as $propertyName => $propertyMetadata) {
            $targetPropertyName = $propertyMetadata->name ?? $propertyName;
            /** @var PropertyTransformerInterface|null $transformer */
            $transformer = $propertyMetadata->transformer?->service ? $this->transformers->get($propertyMetadata->transformer->service) : null;
            /** @var array<string, mixed> $target */
            $rawValue = $this->propertyAccessor->getValue($source, $propertyName);
            $value = match ($transformer) {
                null => $rawValue,
                default => $transformer->toDocument(
                    $rawValue,
                    $propertyMetadata,
                    $context,
                )
            };
            $target[$targetPropertyName] = $value;
        }

        /** @var array<string, mixed> $target */
        return $target;
    }
}
