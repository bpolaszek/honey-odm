<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;

/**
 * @template TClassMetadata of ClassMetadataInterface
 * @template TPropertyMetadata of PropertyMetadataInterface
 * @template TCriteria of mixed
 * @template TObject of object
 */
final readonly class PrePersistEvent
{
    /**
     * @param TObject $object
     * @param ObjectManager<TClassMetadata, TPropertyMetadata, TCriteria> $objectManager
     */
    public function __construct(
        public object $object,
        public ObjectManager $objectManager,
    ) {
    }
}
