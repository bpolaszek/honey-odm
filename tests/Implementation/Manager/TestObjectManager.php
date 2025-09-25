<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Manager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManagerInterface;
use Honey\ODM\Core\Manager\ObjectManagerTrait;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Honey\ODM\Core\Tests\Implementation\Config\TestClassMetadataRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @implements ObjectManagerInterface<TestAsDocument, TestAsField>
 */
final class TestObjectManager implements ObjectManagerInterface
{
    /**
     * @use ObjectManagerTrait<TestAsDocument, TestAsField>
     */
    use ObjectManagerTrait;

    /**
     * @param ClassMetadataRegistryInterface<TestAsDocument<TestAsField>, TestAsField> $classMetadataRegistry
     */
    public static function make(
        ClassMetadataRegistryInterface $classMetadataRegistry = new TestClassMetadataRegistry(), // @phpstan-ignore parameter.defaultValue
        PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
    ): self {
        return new self($classMetadataRegistry, $propertyAccessor);
    }
}
