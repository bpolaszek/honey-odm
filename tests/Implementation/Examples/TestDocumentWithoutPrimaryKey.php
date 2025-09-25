<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

#[TestAsDocument]
final class TestDocumentWithoutPrimaryKey
{
    public function __construct(
        #[TestAsField]
        public int $id,
        #[TestAsField]
        public string $name,
        public string $foo = 'bar',
    ) {
    }
}
