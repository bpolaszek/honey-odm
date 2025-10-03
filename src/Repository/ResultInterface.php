<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Repository;

use Countable;
use Traversable;

/**
 * @template TObject of object
 *
 * @extends Traversable<int, TObject>
 */
interface ResultInterface extends Traversable, Countable
{
}
