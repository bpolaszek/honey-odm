<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;

final readonly class MappingContext implements MappingContextInterface
{
    /**
     * @param array<string, mixed> $document
     */
    // @phpstan-ignore missingType.generics, missingType.generics
    public function __construct(
        public ClassMetadataInterface $classMetadata,
        public ObjectManager $objectManager,
        public object $object,
        public array $document,
    ) {
    }
}
