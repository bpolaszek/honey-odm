<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

interface TransformerMetadataInterface
{
    public string $service {get; }

    /**
     * @var array<string, mixed>
     */
    public array $options {get; }
}
