<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Criteria\Geo;

use InvalidArgumentException;

/**
 * A rectangular area, expressed from its south-west (min) corner to its north-east (max) one.
 *
 * This follows the GeoJSON / PostGIS / OGC convention. Platforms expecting another corner pair
 * (Elasticsearch takes north-west / south-east, Meilisearch north-east / south-west) convert on their side.
 */
final readonly class BoundingBox
{
    public function __construct(
        public Coordinates $southWest,
        public Coordinates $northEast,
    ) {
        if ($northEast->latitude < $southWest->latitude) {
            throw new InvalidArgumentException(
                'The north-east corner cannot be south of the south-west corner.',
            );
        }
    }
}
