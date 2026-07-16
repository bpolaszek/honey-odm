<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Manager\ObjectManager;

final readonly class MappingContext implements MappingContextInterface
{
    /**
     * @param AsDocument<object> $classMetadata
     * @param array<string, mixed> $document
     */
    public function __construct(
        public AsDocument $classMetadata,
        public ObjectManager $objectManager,
        public object $object,
        public array $document,
    ) {
    }
}
