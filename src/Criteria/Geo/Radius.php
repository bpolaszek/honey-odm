<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria\Geo;

use InvalidArgumentException;

use function sprintf;

/**
 * A circular area, centered on a point.
 */
final readonly class Radius
{
    /**
     * @param float $meters Distance from the center, in meters
     */
    public function __construct(
        public Coordinates $center,
        public float $meters,
    ) {
        if ($meters <= 0) {
            throw new InvalidArgumentException(
                sprintf('Radius must be greater than 0, got `%s`.', $meters),
            );
        }
    }
}
