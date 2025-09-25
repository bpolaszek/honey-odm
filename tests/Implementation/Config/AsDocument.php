<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use ReflectionClass;

/**
 * @template P of AsField
 * @implements ClassMetadataInterface<P>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument implements ClassMetadataInterface
{
    public string $className;

    /**
     * @var ReflectionClass<object>
     */
    public ReflectionClass $reflection;

    /**
     * @var array<string, P>
     */
    public array $propertiesMetadata = [];
}
