<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryTrait;

/**
 * @implements ClassMetadataRegistryInterface<TestAsDocument, TestAsField>
 */
final class TestClassMetadataRegistry implements ClassMetadataRegistryInterface
{
    /**
     * @use ClassMetadataRegistryTrait<TestAsDocument, TestAsField>
     */
    use ClassMetadataRegistryTrait;

    public function getIdFromObject(object $object): mixed
    {
        $classMetadata = $this->getClassMetadata($object::class);

        $idPropertyMetadata = $classMetadata->getIdPropertyMetadata();
        $propertyName = $idPropertyMetadata->reflection->name;

        return $this->propertyAccessor->getValue($object, $propertyName);
    }

    /**
     * @param array<string, mixed> $document
     * @param class-string $className
     * @return mixed
     */
    public function getIdFromDocument(array $document, string $className): mixed
    {
        $classMetadata = $this->getClassMetadata($className);

        $idPropertyMetadata = $classMetadata->getIdPropertyMetadata();
        $propertyName = $idPropertyMetadata->name ?? $idPropertyMetadata->reflection->name;

        return $this->propertyAccessor->getValue((object) $document, $propertyName);
    }
}
