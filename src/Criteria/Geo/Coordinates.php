<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria\Geo;

use InvalidArgumentException;

use function sprintf;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException(
                sprintf('Invalid latitude `%s`: must be between -90 and 90.', $latitude),
            );
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException(
                sprintf('Invalid longitude `%s`: must be between -180 and 180.', $longitude),
            );
        }
    }
}
