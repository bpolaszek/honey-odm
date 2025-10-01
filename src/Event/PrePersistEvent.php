<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Manager\ObjectManagerInterface;

/**
 * @template T
 */
final readonly class PrePersistEvent
{
    /**
     * @param T $object
     */
    public function __construct(
        public object $object,
        public ObjectManagerInterface $objectManager,
    ) {
    }
}
