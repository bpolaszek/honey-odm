<?php

declare(strict_types=1);

namespace Honey\ODM\Core\UnitOfWork;

use function is_object;

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
            if (self::hasChanged($newValue, $oldValue)) {
                $changedProperties[$attribute] = [$newValue, $oldValue];
            }
        }
        foreach ($previousDocument as $attribute => $oldValue) {
            $newValue = $newDocument[$attribute] ?? null;
            if (self::hasChanged($newValue, $oldValue)) {
                $changedProperties[$attribute] = [$newValue, $oldValue];
            }
        }

        $this->changedProperties = $changedProperties;
    }

    /**
     * Values are compared strictly, so that `null`, `''`, `[]`, `0` and `false` remain distinct from each other -
     * the storage layer does tell them apart, and a loose comparison would silently drop such a change.
     *
     * Objects are the exception: value objects (ids, dates, ...) are compared by value, since two equivalent
     * instances end up stored identically.
     */
    private static function hasChanged(mixed $newValue, mixed $oldValue): bool
    {
        if (is_object($newValue) && is_object($oldValue)) {
            return $newValue != $oldValue;
        }

        return $newValue !== $oldValue;
    }
}
