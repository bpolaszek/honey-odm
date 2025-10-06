<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Misc;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 * @codeCoverageIgnore
 */
final class NullEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): object
    {
        return $event;
    }
}
