<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Config\ClassMetadata;
use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Manager\ObjectManager;

/**
 * @template TClassMetadata of ClassMetadata
 * @template TPropertyMetadata of PropertyMetadata
 * @template TCriteria of mixed
 * @template TObject of object
 */
final readonly class PreRemoveEvent
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
