<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Mapper\PropertyTransformer;

use Honey\ODM\Core\Config\PropertyMetadata;
use Honey\ODM\Core\Mapper\MappingContextInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\BackedEnumTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\DateTimeImmutableTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformerInterface;
use Honey\ODM\Core\Mapper\PropertyTransformer\PropertyTransformers;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationsTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\RelationTransformer;
use Honey\ODM\Core\Mapper\PropertyTransformer\StringableTransformer;
use RuntimeException;

use function expect;
use function it;
use function test;

describe('PropertyTransformer', function () {
    $custom = new class implements PropertyTransformerInterface {
        public function fromDocument(
            mixed $value,
            PropertyMetadata $propertyMetadata,
            MappingContextInterface $context,
        ): mixed {
            return $value;
        }

        public function toDocument(
            mixed $value,
            PropertyMetadata $propertyMetadata,
            MappingContextInterface $context,
        ): mixed {
            return $value;
        }
    };

    it('initializes with default transformers', function () {
        $transformers = new PropertyTransformers();

        $list = [...$transformers];

        expect($list)->toHaveCount(5)
            ->and($list)->toHaveKey(StringableTransformer::class)
            ->and($list)->toHaveKey(DateTimeImmutableTransformer::class)
            ->and($list)->toHaveKey(BackedEnumTransformer::class)
            ->and($list)->toHaveKey(RelationTransformer::class)
            ->and($list)->toHaveKey(RelationsTransformer::class);
    });

    it('initializes with custom transformers', function () use ($custom) {
        $transformers = new PropertyTransformers([$custom]);

        $list = [...$transformers];

        expect($list)->toHaveCount(1)
            ->and($list)->toHaveKey($custom::class)
            ->and($list[$custom::class])->toBe($custom);
    });

    it('initializes with empty transformers', function () {
        $transformers = new PropertyTransformers([]);

        $list = [...$transformers];

        expect($list)->toHaveCount(0);
    });

    it('registers a new transformer', function () use ($custom) {
        $transformers = new PropertyTransformers([]);

        expect([...$transformers])->toHaveCount(0);

        $transformers->register($custom);
        $list = [...$transformers];

        expect($list)->toHaveCount(1)
            ->and($list)->toHaveKey($custom::class)
            ->and($list[$custom::class])->toBe($custom);
    });

    it('registers multiple transformers', function () {
        $transformers = new PropertyTransformers([]);

        $custom1 = new class implements PropertyTransformerInterface {
            public function fromDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return $value;
            }

            public function toDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return $value;
            }
        };

        $custom2 = new class implements PropertyTransformerInterface {
            public function fromDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return $value;
            }

            public function toDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return $value;
            }
        };

        $transformers->register($custom1);
        $transformers->register($custom2);

        $list = [...$transformers];

        expect($list)->toHaveCount(2)
            ->and($list)->toHaveKey($custom1::class)
            ->and($list)->toHaveKey($custom2::class);
    });

    it('implements ContainerInterface::get() method', function () {
        $transformers = new PropertyTransformers();

        $transformer = $transformers->get(StringableTransformer::class);

        expect($transformer)->toBeInstanceOf(StringableTransformer::class);
    });

    it('throws a RuntimeException when transformer is not found', function () {
        $transformers = new PropertyTransformers([]);

        $transformers->get('NonExistentTransformer');
    })->throws(RuntimeException::class, 'Service NonExistentTransformer not found');

    it('implements ContainerInterface::has() method', function () {
        $transformers = new PropertyTransformers();

        expect($transformers->has(StringableTransformer::class))->toBeTrue()
            ->and($transformers->has(DateTimeImmutableTransformer::class))->toBeTrue()
            ->and($transformers->has(BackedEnumTransformer::class))->toBeTrue()
            ->and($transformers->has(RelationTransformer::class))->toBeTrue()
            ->and($transformers->has(RelationsTransformer::class))->toBeTrue();
    });

    test('has method returns false for non-existent transformer', function () {
        $transformers = new PropertyTransformers([]);

        expect($transformers->has('NonExistentTransformer'))->toBeFalse()
            ->and($transformers->has(StringableTransformer::class))->toBeFalse();
    });

    it('has method returns true after registering a transformer', function () use ($custom) {
        $transformers = new PropertyTransformers([]);

        expect($transformers->has($custom::class))->toBeFalse();

        $transformers->register($custom);

        expect($transformers->has($custom::class))->toBeTrue();
    });

    it('implements IteratorAggregate interface', function () {
        $transformers = new PropertyTransformers();

        expect($transformers)->toBeInstanceOf(\IteratorAggregate::class);
    });

    it('can be iterated over', function () {
        $transformers = new PropertyTransformers();

        $count = 0;
        foreach ($transformers as $key => $transformer) {
            expect($transformer)->toBeInstanceOf(PropertyTransformerInterface::class)
                ->and($key)->toBe($transformer::class)
            ;
            $count++;
        }

        expect($count)->toBe(5);
    });


    it('works with transformer instances from constructor and register method', function () {
        $custom1 = new class implements PropertyTransformerInterface {
            public function fromDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return 'custom1';
            }

            public function toDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return 'custom1';
            }
        };

        $transformers = new PropertyTransformers([$custom1]);

        $custom2 = new class implements PropertyTransformerInterface {
            public function fromDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return 'custom2';
            }

            public function toDocument(
                mixed $value,
                PropertyMetadata $propertyMetadata,
                MappingContextInterface $context,
            ): mixed {
                return 'custom2';
            }
        };

        $transformers->register($custom2);

        $list = [...$transformers];

        expect($list)->toHaveCount(2)
            ->and($transformers->has($custom1::class))->toBeTrue()
            ->and($transformers->has($custom2::class))->toBeTrue()
            ->and($transformers->get($custom1::class))->toBe($custom1)
            ->and($transformers->get($custom2::class))->toBe($custom2);
    });
});
