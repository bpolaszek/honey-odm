<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use \ReflectionClass;

/**
 * @implements ClassMetadataInterface<AsField>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument implements ClassMetadataInterface
{
    public string $className;
    public ReflectionClass $reflection; // @phpstan-ignore missingType.generics
    public array $propertiesMetadata = [];
}
