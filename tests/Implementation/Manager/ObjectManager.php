<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManagerInterface;
use Honey\ODM\Core\Manager\ObjectManagerTrait;
use Honey\ODM\Core\Tests\Implementation\Config\AsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\AsField;
use Honey\ODM\Core\Tests\Implementation\Config\ClassMetadataRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @implements ObjectManagerInterface<AsDocument, AsField>
 */
final class ObjectManager implements ObjectManagerInterface
{
    /**
     * @use ObjectManagerTrait<AsDocument, AsField>
     */
    use ObjectManagerTrait;

    /**
     * @param ClassMetadataRegistryInterface<AsDocument<AsField>, AsField> $classMetadataRegistry
     */
    public static function make(
        ClassMetadataRegistryInterface $classMetadataRegistry = new ClassMetadataRegistry(), // @phpstan-ignore parameter.defaultValue
        PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
    ): self {
        return new self($classMetadataRegistry, $propertyAccessor);
    }
}
