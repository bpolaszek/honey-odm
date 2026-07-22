<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\PlatformMetadataInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final readonly class TestPlatformMetadata implements PlatformMetadataInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public array $options = [],
    ) {
    }
}
