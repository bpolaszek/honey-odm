<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Manager;

use Honey\ODM\Core\Tests\Implementation\Examples\Document;
use Honey\ODM\Core\Tests\Implementation\Manager\ObjectManager;

it('returns the id of an object', function () {
    $object = new Document(42, 'foo');
    $manager = ObjectManager::make();
    expect($manager->getIdFromObject($object))->toBe(42);
});
