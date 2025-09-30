<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

final readonly class TransformerMetadata implements TransformerMetadataInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $service,
        public array $options = [],
    ) {
    }
}
