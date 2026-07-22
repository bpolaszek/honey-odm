<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use Attribute;
use ReflectionProperty;

use function array_find;
use function is_string;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class AsField
{
    // @phpstan-ignore missingType.generics
    public private(set) AsDocument $classMetadata;
    public private(set) ReflectionProperty $reflection;

    /**
     * @var list<PlatformMetadataInterface>
     */
    public private(set) array $platformMetadata = [];

    /**
     * The storage-side field name.
     */
    // phpcs:disable PSR2.Classes.PropertyDeclaration -- phpcs does not fully support property hooks yet
    public string $fieldName {
        get => $this->name ?? $this->reflection->name;
    }
    // phpcs:enable

    /**
     * @param string|null $name Storage-side field name (defaults to the property name)
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly bool $primary = false,
        private readonly TransformerMetadataInterface|string|null $transformer = null,
    ) {
    }

    public function getTransformer(): ?TransformerMetadataInterface
    {
        if (is_string($this->transformer)) {
            return new TransformerMetadata($this->transformer);
        }

        return $this->transformer;
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
