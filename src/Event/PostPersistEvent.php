<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Manager\ObjectManager;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 * @template F of mixed
 * @template O of object
 */
final readonly class PostPersistEvent
{
    /**
     * @param O $object
     * @param ObjectManager<C, P, F> $objectManager
     */
    public function __construct(
        public object $object,
        public ObjectManager $objectManager,
    ) {
    }
}
