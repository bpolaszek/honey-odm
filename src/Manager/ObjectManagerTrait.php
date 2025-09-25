<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 *
 * @implements ObjectManagerInterface<C, P>
 */
trait ObjectManagerTrait
{
    /**
     * @param ClassMetadataRegistryInterface<C<P>, P> $classMetadataRegistry
     */
    public function __construct(
        public readonly ClassMetadataRegistryInterface $classMetadataRegistry,
        public readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
    ) {
    }

    public function getIdFromObject(object $object): mixed
    {
        $classMetadata = $this->classMetadataRegistry->getClassMetadata($object::class);

        $idPropertyMetadata = $classMetadata->getIdPropertyMetadata();
        $propertyName = $idPropertyMetadata->reflection->name;

        return $this->propertyAccessor->getValue($object, $propertyName);
    }
}
