<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

use Countable;
use Traversable;

/**
 * @template O of object
 *
 * @extends Traversable<int, O>
 */
interface ResultInterface extends Traversable, Countable
{
}
