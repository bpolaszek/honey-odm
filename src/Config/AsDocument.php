<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use Attribute;
use ReflectionClass;
use RuntimeException;

use function array_find;

/**
 * @template TObject of object
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsDocument
{
    /**
     * @var class-string<TObject>
     */
    public private(set) string $className;

    /**
     * @var ReflectionClass<TObject>
     */
    public private(set) ReflectionClass $reflection;

    /**
     * @var array<string, AsField>
     */
    public private(set) array $propertiesMetadata = [];

    /**
     * @var list<PlatformMetadataInterface>
     */
    public private(set) array $platformMetadata = [];

    /**
     * @param string|null $collection Logical name of the storage container (index, table, endpoint, ...)
     */
    public function __construct(
        public readonly ?string $collection = null,
    ) {
    }

    public function getIdPropertyMetadata(): AsField
    {
        return array_find(
            $this->propertiesMetadata,
            fn (AsField $metadata) => $metadata->primary,
        ) ?? throw new RuntimeException('No primary property found in class metadata');
    }

    public function getPropertyMetadata(string $propertyName): AsField
    {
        return $this->propertiesMetadata[$propertyName]
            ?? throw new RuntimeException("No field mapped to property `$propertyName` was found.");
    }

    /**
     * Returns the storage-side field name mapped to the given PHP property.
     */
    public function getFieldName(string $propertyName): string
    {
        return $this->getPropertyMetadata($propertyName)->fieldName;
    }

    /**
     * @template T of PlatformMetadataInterface
     *
     * @param class-string<T> $className
     *
     * @return T|null
     */
    public function getPlatformMetadata(string $className): ?PlatformMetadataInterface
    {
        return array_find(
            $this->platformMetadata,
            fn (PlatformMetadataInterface $metadata) => $metadata instanceof $className,
        );
    }
}
