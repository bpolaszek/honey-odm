<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Manager;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Manager\ObjectManagerInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @implements ObjectManagerInterface<TestAsDocument, TestAsField>
 */
final class TestObjectManager implements ObjectManagerInterface
{
    /**
     * @param ClassMetadataRegistryInterface<array<string, mixed>, object, TestAsDocument<object, TestAsField>, TestAsField> $classMetadataRegistry
     */
    public function __construct(
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        public readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
    ) {
    }
}
