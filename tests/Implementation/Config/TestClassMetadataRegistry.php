<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Config;

use Honey\ODM\Core\Config\ClassMetadataRegistryInterface;
use Honey\ODM\Core\Config\ClassMetadataRegistryTrait;

/**
 * @implements ClassMetadataRegistryInterface<TestAsDocument, TestAsField>
 */
final class TestClassMetadataRegistry implements ClassMetadataRegistryInterface
{
    /**
     * @use ClassMetadataRegistryTrait<TestAsDocument, TestAsField>
     */
    use ClassMetadataRegistryTrait;
}
