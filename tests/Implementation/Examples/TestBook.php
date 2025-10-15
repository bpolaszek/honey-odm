<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

#[TestAsDocument(bucket: 'books')]
final class TestBook
{
    public function __construct(
        #[TestAsField(primary: true)]
        public string $id,
        #[TestAsField(name: 'title')]
        public string $name,
        #[TestAsField(name: 'author_id', transformer: new TransformerMetadata(RelationTransformer::class))]
        public ?TestAuthor $author = null,
    ) {
    }
}
