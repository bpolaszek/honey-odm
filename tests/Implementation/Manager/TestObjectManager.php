<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Manager;

use Honey\ODM\Core\Manager\ObjectManager;
use Honey\ODM\Core\Repository\ObjectRepositoryInterface;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;
use Honey\ODM\Core\Tests\Implementation\Repository\TestObjectRepository;

/**
 * @implements ObjectManager<TestAsDocument, TestAsField>
 */
final class TestObjectManager extends ObjectManager
{
    public function getRepository(string $className): ObjectRepositoryInterface
    {
        return $this->repositories[$className]
            ??= $this->registerRepository($className, new TestObjectRepository($this, $className));
    }
}
