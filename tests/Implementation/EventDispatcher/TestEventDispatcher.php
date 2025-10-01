<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

final class TestEventDispatcher implements EventDispatcherInterface
{
    public function dispatch(object $event): void
    {
    }
}
