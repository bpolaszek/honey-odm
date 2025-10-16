<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Config\TransformerMetadataInterface;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class TestAsField extends PropertyMetadata
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $primary = false,
        protected TransformerMetadataInterface|string|null $transformer = null,
    ) {
    }
}
