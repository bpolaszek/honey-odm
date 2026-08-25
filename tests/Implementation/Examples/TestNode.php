<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\StringableTransformer;
use Symfony\Component\Uid\Ulid;

/**
 * A self-related document, whose primary key is a stringable value object.
 */
#[AsDocument(collection: 'nodes')]
final class TestNode
{
    #[AsField(primary: true, transformer: StringableTransformer::class)]
    public ?Ulid $id = null;

    #[AsField(name: 'parent_id', transformer: new TransformerMetadata(RelationTransformer::class, ['target_class' => self::class]))]
    public ?self $parent = null;

    #[AsField]
    public string $label = '';
}
