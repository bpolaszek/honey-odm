<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Config;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\ClassMetadataRegistry;
use Honey\ODM\Core\Tests\Implementation\Examples\TestAuthor;
use RuntimeException;

use function expect;

describe('AsDocument', function () {
    it('maps property names to field names', function () {
        $classMetadata = new ClassMetadataRegistry()->getClassMetadata(TestAuthor::class);

        expect($classMetadata->getFieldName('name'))->toBe('author_name')
            ->and($classMetadata->getFieldName('createdAt'))->toBe('created_at')
            ->and($classMetadata->getPropertyMetadata('id')->fieldName)->toBe('author_id');
    });

    it('complains when no field is mapped to a property', function () {
        $classMetadata = new ClassMetadataRegistry()->getClassMetadata(TestAuthor::class);

        $classMetadata->getFieldName('unknownProperty');
    })->throws(RuntimeException::class, 'No field mapped to property `unknownProperty` was found.');

    it('complains when no primary property is found', function () {
        $classMetadata = new AsDocument('foo');

        $classMetadata->getIdPropertyMetadata();
    })->throws(RuntimeException::class, 'No primary property found in class metadata');
});
