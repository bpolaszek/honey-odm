<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use Honey\ODM\Core\Config\TransformerMetadataInterface;
use ReflectionProperty;

/**
 * @implements PropertyMetadataInterface<TestAsDocument<object, TestAsField>>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class TestAsField implements PropertyMetadataInterface
{
    public ReflectionProperty $reflection;

    /**
     * @var TestAsDocument<object, TestAsField>
     */
    public ClassMetadataInterface $classMetadata;

    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $primary = false,
        public readonly ?TransformerMetadataInterface $transformer = null,
    ) {
    }
}
