<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;

#[AsDocument(collection: 'books')]
final class TestBook
{
    public function __construct(
        #[AsField(primary: true)]
        public string $id,
        #[AsField(name: 'title')]
        public string $name,
        #[AsField(name: 'author_id', transformer: new TransformerMetadata(RelationTransformer::class))]
        public ?TestAuthor $author = null,
    ) {
    }
}
