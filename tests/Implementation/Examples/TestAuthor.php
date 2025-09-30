<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

#[TestAsDocument]
final class TestAuthor
{
    public function __construct(
        #[TestAsField(name: 'author_id', primary: true)]
        public string $id,
        #[TestAsField(name: 'author_name')]
        public string $name,
    ) {
    }
}
