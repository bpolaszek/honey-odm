<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

#[TestAsDocument]
final class TestAuthor
{
    #[TestAsField(name: 'created_at', transformer: new TransformerMetadata(DateTimeImmutableTransformer::class))]
    public ?\DateTimeInterface $createdAt = null;

    public function __construct(
        #[TestAsField(name: 'author_id', primary: true)]
        public string $id,
        #[TestAsField(name: 'author_name')]
        public string $name,
    ) {
    }
}
