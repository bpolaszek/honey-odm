<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Mapper;

final readonly class MappingContext implements MappingContextInterface
{
    public function __construct(
        public object $object,
        public array $document,
    ) {
    }
}
