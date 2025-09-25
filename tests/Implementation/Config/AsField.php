<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use ReflectionProperty;

/**
 * @implements PropertyMetadataInterface<AsDocument>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class AsField implements PropertyMetadataInterface
{
    public ReflectionProperty $reflection;

    /**
     * @var AsDocument<AsField>
     */
    public ClassMetadataInterface $classMetadata;

    public function __construct(
        public readonly bool $primary = false,
    ) {
    }
}
