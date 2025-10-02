<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Manager\ObjectManager;

/**
 * @template T
 */
final readonly class PostUpdateEvent
{
    /**
     * @param T $object
     */
    public function __construct(
        public object $object,
        public ObjectManager $objectManager,
    ) {
    }
}
