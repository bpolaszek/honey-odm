<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

enum TestStringStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
