<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Misc;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
final readonly class EmptyContainer implements ContainerInterface
{
    public function get(string $id): never
    {
        throw new class ("No entry found for {$id}.") extends Exception implements NotFoundExceptionInterface {
        };
    }

    public function has(string $id): bool
    {
        return false;
    }
}
