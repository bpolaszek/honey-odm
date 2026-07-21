<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Implementation\Examples;

use Honey\ODM\Core\Config\AsDocument;
use Honey\ODM\Core\Config\AsField;

#[AsDocument(collection: 'places')]
final class TestPlace
{
    /**
     * @param list<string>|null $tags
     * @param array{lat: float, lng: float}|null $coordinates
     */
    public function __construct(
        #[AsField(primary: true)]
        public string $id,
        #[AsField(name: 'label')]
        public string $name,
        #[AsField(name: 'tags')]
        public ?array $tags = null,
        #[AsField(name: '_geo')]
        public ?array $coordinates = null,
        #[AsField(name: 'rating')]
        public ?int $rating = null,
        #[AsField(name: 'summary')]
        public ?string $summary = null,
    ) {
    }
}
