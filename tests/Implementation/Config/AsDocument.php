<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;
use ReflectionClass;

use function array_find;

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

    public function getIdPropertyMetadata(): PropertyMetadataInterface
    {
        return array_find($this->propertiesMetadata, fn (PropertyMetadataInterface $metadata) => $metadata->primary); // @phpstan-ignore return.type
    }
}
