<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationsTransformer;

#[AsDocument(collection: 'authors')]
final class TestAuthor
{
    #[AsField(name: 'created_at', transformer: new TransformerMetadata(DateTimeImmutableTransformer::class))]
    public ?\DateTimeInterface $createdAt = null;

    public function __construct(
        #[AsField(name: 'author_id', primary: true)]
        public int $id,
        #[AsField(name: 'author_name')]
        public string $name,
        #[AsField(name: 'books', transformer: new TransformerMetadata(RelationsTransformer::class, ['target_class' => TestBook::class]))]
        public ?array $books = null,
    ) {
    }
}
