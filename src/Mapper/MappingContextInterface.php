<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

interface MappingContextInterface
{
    public object $object {get; }

    /**
     * @var array<string, mixed>
     */
    public array $document {get; }
}
