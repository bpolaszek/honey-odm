<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use Honey\ODM\Core\Config\AsField;
use Honey\ODM\Core\Config\TransformerMetadata;
use Honey\ODM\Core\Config\TransformerMetadataInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;

use function expect;

describe('AsField', function () {
    it('accepts a string as Transformer', function () {
        $propertyMetadata = new AsField(transformer: DateTimeImmutableTransformer::class);

        expect($propertyMetadata->getTransformer())->toBeInstanceOf(TransformerMetadataInterface::class)
            ->and($propertyMetadata->getTransformer()?->service)->toBe(DateTimeImmutableTransformer::class);
    });

    it('accepts a TransformerMetadata instance as Transformer', function () {
        $transformer = new TransformerMetadata(DateTimeImmutableTransformer::class, ['foo' => 'bar']);
        $propertyMetadata = new AsField(transformer: $transformer);

        expect($propertyMetadata->getTransformer())->toBe($transformer);
    });

    it('has no transformer by default', function () {
        expect(new AsField()->getTransformer())->toBeNull();
    });
});
