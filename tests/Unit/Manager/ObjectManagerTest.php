<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Tests\Implementation\Examples\TestDocument;
use Honey\ODM\Core\Tests\Implementation\Manager\TestObjectManager;

it('returns the id of an object', function () {
    $object = new TestDocument(42, 'foo');
    $manager = TestObjectManager::make();
    expect($manager->getIdFromObject($object))->toBe(42);
});
