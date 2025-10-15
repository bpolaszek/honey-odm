<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\BackedEnumTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsDocument;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

#[TestAsDocument(bucket: 'documents')]
final class TestDocument
{
    public function __construct(
        #[TestAsField(primary: true)]
        public int $id,
        #[TestAsField]
        public string $name,
        public string $foo = 'bar',
        #[TestAsField(transformer: new TransformerMetadata(BackedEnumTransformer::class))]
        public ?TestStringStatus $publicationState = null,
        #[TestAsField(transformer: new TransformerMetadata(BackedEnumTransformer::class))]
        public ?TestIntStatus $done = null,
    ) {
    }
}
