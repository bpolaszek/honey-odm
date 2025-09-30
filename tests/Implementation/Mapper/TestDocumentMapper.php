<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\DocumentMapperInterface;
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
            try {
                $rawValue = $this->propertyAccessor->getValue($document, $sourcePropertyName);
                $this->propertyAccessor->setValue($target, $propertyName, $rawValue);
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
            /** @var array<string, mixed> $target */
            $target[$targetPropertyName] ??= $this->propertyAccessor->getValue($source, $propertyName);
        }

        /** @var array<string, mixed> $target */
        return $target;
    }
}
