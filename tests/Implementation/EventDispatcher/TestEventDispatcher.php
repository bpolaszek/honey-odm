<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\EventDispatcher;

use Psr\EventDispatcher\EventDispatcherInterface;

final class TestEventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];
    public array $firedEvents = [];

    public function listen(string $eventClass, callable $callback): void
    {
        $this->listeners[$eventClass][] = $callback;
    }

    public function dispatch(object $event): void
    {
        foreach ($this->listeners[$event::class] ?? [] as $callback) {
            $callback($event);
        }
        $this->firedEvents[] = $event;
    }

    public function resetEvents(): void
    {
        $this->firedEvents = [];
    }
}
