<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;

#[AsDocument(collection: 'documents')]
final class TestDocumentWithoutPrimaryKey
{
    public function __construct(
        #[AsField]
        public int $id,
        #[AsField]
        public string $name,
        public string $foo = 'bar',
    ) {
    }
}
