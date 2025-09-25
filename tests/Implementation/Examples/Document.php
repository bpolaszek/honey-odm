<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Tests\Implementation\Config\AsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\AsField;

#[AsDocument]
final class Document
{
    public function __construct(
        #[AsField(primary: true)]
        public int $id,
        #[AsField]
        public string $name,
        public string $foo = 'bar',
    ) {
    }
}
