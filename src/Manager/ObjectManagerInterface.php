<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Manager;

use Honey\ODM\Core\Config\ClassMetadataInterface;
use Honey\ODM\Core\Config\PropertyMetadataInterface;

/**
 * @template C of ClassMetadataInterface
 * @template P of PropertyMetadataInterface
 */
interface ObjectManagerInterface
{
    public function getIdFromObject(object $object): mixed;
}
