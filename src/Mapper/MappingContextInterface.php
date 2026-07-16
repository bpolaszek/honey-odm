<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Manager\ObjectManager;

interface MappingContextInterface
{
    /**
     * @var AsDocument<object>
     */
    public AsDocument $classMetadata {get; }

    public ObjectManager $objectManager {get; }

    public object $object {get; }

    /**
     * @var array<string, mixed>
     */
    public array $document {get; }
}
