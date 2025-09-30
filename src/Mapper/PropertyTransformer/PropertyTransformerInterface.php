<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;

interface PropertyTransformerInterface
{
    /**
     * @param PropertyMetadataInterface<ClassMetadataInterface<object, PropertyMetadataInterface>> $propertyMetadata
     */
    public function fromDocument(// @phpstan-ignore missingType.generics
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;

    /**
     * @param PropertyMetadataInterface<ClassMetadataInterface<object, PropertyMetadataInterface>> $propertyMetadata
     */
    public function toDocument(// @phpstan-ignore missingType.generics
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;
}
