<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Manager\ObjectManager;

interface MappingContextInterface
{
    public ClassMetadata $classMetadata {get; } // @phpstan-ignore missingType.generics
    public ObjectManager $objectManager {get; } // @phpstan-ignore missingType.generics

    public object $object {get; }

    /**
     * @var array<string, mixed>
     */
    public array $document {get; }
}
