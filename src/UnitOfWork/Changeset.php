<?php

declare(strict_types=1);

namespace Honey\ODM\Core\UnitOfWork;

final readonly class Changeset
{
    /**
     * @var array<string, array{0: mixed, 1: mixed}>
     */
    public array $changedProperties;

    /**
     * @param array<string, mixed> $newDocument
     * @param array<string, mixed> $previousDocument
     */
    public function __construct(
        public array $newDocument,
        public array $previousDocument,
    ) {
        $changedProperties = [];

        foreach ($newDocument as $attribute => $newValue) {
            $oldValue = $previousDocument[$attribute] ?? null;
            if (0 !== ($oldValue <=> $newValue)) {
                $changedProperties[$attribute] = [$newValue, $oldValue];
            }
        }
        foreach ($previousDocument as $attribute => $oldValue) {
            $newValue = $newDocument[$attribute] ?? null;
            if (0 !== ($oldValue <=> $newValue)) {
                $changedProperties[$attribute] = [$newValue, $oldValue];
            }
        }

        $this->changedProperties = $changedProperties;
    }
}
