<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use ReflectionProperty;

/**
 * @implements PropertyMetadataInterface<TestAsDocument>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TestAsField implements PropertyMetadataInterface
{
    public ReflectionProperty $reflection;

    /**
     * @var TestAsDocument<TestAsField>
     */
    public ClassMetadataInterface $classMetadata;

    public function __construct(
        public readonly bool $primary = false,
    ) {
    }
}
