<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Config;

use ReflectionProperty;

use function is_string;

abstract class PropertyMetadata
{
    // @phpstan-ignore missingType.generics
    final public protected(set) ClassMetadata $classMetadata;
    final public protected(set) ReflectionProperty $reflection;

    abstract public bool $primary {get; }

    protected TransformerMetadataInterface|string|null $transformer;

    final public function getTransformer(): ?TransformerMetadataInterface
    {
        if (is_string($this->transformer)) {
            return new TransformerMetadata($this->transformer);
        }

        return $this->transformer;
    }
}
