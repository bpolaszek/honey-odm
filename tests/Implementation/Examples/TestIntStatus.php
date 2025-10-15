<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

enum TestIntStatus: int
{
    case Pending = 0;
    case Done = 1;
}
