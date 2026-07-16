<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Mapper\MappingContextInterface;

interface PropertyTransformerInterface
{
    public function fromDocument(
        mixed $value,
        AsField $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;

    public function toDocument(
        mixed $value,
        AsField $propertyMetadata,
        MappingContextInterface $context,
    ): mixed;
}
