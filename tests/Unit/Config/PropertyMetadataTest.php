<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use Honey\ODM\Core\Config\TransformerMetadataInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Tests\Implementation\Config\TestAsField;

use function expect;

it('accepts a string as Transformer', function () {
    $propertyMetadata = new TestAsField(transformer: DateTimeImmutableTransformer::class);

    expect($propertyMetadata->getTransformer())->toBeInstanceOf(TransformerMetadataInterface::class);
});
