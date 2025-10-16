<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Mapper\MappingContextInterface;

interface PropertyTransformerInterface
{
    public function fromDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;

    public function toDocument(
        mixed $value,
        PropertyMetadata $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;
}
