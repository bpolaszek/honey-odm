<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

final class TestEventDispatcher implements EventDispatcherInterface
{
    public array $firedEvents = [];

    public function dispatch(object $event): void
    {
        $this->firedEvents[] = $event;
    }

    public function reset(): void
    {
        $this->firedEvents = [];
    }
}
