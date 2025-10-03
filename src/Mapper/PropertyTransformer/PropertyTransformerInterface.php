<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Mapper\MappingContextInterface;

interface PropertyTransformerInterface
{
    // @phpstan-ignore missingType.generics
    public function fromDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;

    // @phpstan-ignore missingType.generics
    public function toDocument(
        mixed $value,
        PropertyMetadataInterface $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;
}
