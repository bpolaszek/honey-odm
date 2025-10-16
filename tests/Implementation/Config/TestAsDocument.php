<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Attribute;
use Honey\ODM\Core\Config\ClassMetadata;

/**
 * @template O of object
 * @template P of TestAsField
 * @extends ClassMetadata<O, P>
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TestAsDocument extends ClassMetadata
{
    public function __construct(
        public ?string $bucket = null,
    ) {
    }
}
