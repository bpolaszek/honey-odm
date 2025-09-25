<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryTrait;

/**
 * @implements ClassMetadataRegistryInterface<AsDocument, AsField>
 */
final class ClassMetadataRegistry implements ClassMetadataRegistryInterface
{
    /**
     * @use ClassMetadataRegistryTrait<AsDocument, AsField>
     */
    use ClassMetadataRegistryTrait;
}
