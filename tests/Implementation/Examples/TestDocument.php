<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\BackedEnumTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestPlatformMetadata;

#[AsDocument(collection: 'documents')]
#[TestPlatformMetadata(['foo' => 'bar'])]
final class TestDocument
{
    public function __construct(
        #[AsField(primary: true)]
        public int $id,
        #[AsField]
        #[TestPlatformMetadata(['searchable' => true])]
        public string $name,
        public string $foo = 'bar',
        #[AsField(transformer: new TransformerMetadata(BackedEnumTransformer::class))]
        public ?TestStringStatus $publicationState = null,
        #[AsField(transformer: new TransformerMetadata(BackedEnumTransformer::class))]
        public ?TestIntStatus $done = null,
    ) {
    }
}
