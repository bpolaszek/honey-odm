<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;

interface MappingContextInterface
{
    public ClassMetadataInterface $classMetadata {get; }
    public ObjectManager $objectManager {get; }

    public object $object {get; }

    /**
     * @var array<string, mixed>
     */
    public array $document {get; }
}
