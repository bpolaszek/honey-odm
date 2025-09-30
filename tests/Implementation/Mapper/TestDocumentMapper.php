<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
use Honey\ODM\Core\Mapper\MappingContext;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformerInterface;
use Honey\ODM\Core\Misc\DummyContainer;
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
        private ContainerInterface $transformers = new DummyContainer(),
    ) {
    }

    /**
     * @param ClassMetadataInterface<object, PropertyMetadataInterface> $classMetadata
     */
    // @phpstan-ignore-next-line missingType.generics
    public function documentToObject(ClassMetadataInterface $classMetadata, mixed $source, object $target): object
    {
        $document = (object) $source;
        foreach ($classMetadata->propertiesMetadata as $propertyName => $propertyMetadata) {
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
                        new MappingContext($target, $source),
                    )
                };
                $this->propertyAccessor->setValue($target, $propertyName, $value);
            } catch (NoSuchPropertyException) {
            }
        }

        return $target;
    }

    /**
     * @param ClassMetadataInterface<object, PropertyMetadataInterface> $classMetadata
     * @param array<string, mixed> $target
     * @return array<string, mixed>
     */
    public function objectToDocument(ClassMetadataInterface $classMetadata, object $source, mixed $target = []): array // @phpstan-ignore missingType.generics
    {
        foreach ($classMetadata->propertiesMetadata as $propertyName => $propertyMetadata) {
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
                    new MappingContext($source, $target),
                )
            };
            $target[$targetPropertyName] = $value;
        }

        /** @var array<string, mixed> $target */
        return $target;
    }
}
