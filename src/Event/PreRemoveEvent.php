<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Event;

use Honey\ODM\Core\Manager\ObjectManager;

/**
 * @template TObject of object
 */
final readonly class PreRemoveEvent
{
    /**
     * @param TObject $object
     */
    public function __construct(
        public object $object,
        public ObjectManager $objectManager,
    ) {
    }
}
