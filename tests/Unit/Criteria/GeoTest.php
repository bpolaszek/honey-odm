<?php

declare(strict_types=1);

namespace Honey\ODM\Core\Tests\Unit\Criteria;

use Honey\ODM\Core\Criteria\Geo\BoundingBox;
use Honey\ODM\Core\Criteria\Geo\Coordinates;
use Honey\ODM\Core\Criteria\Geo\Radius;
use InvalidArgumentException;

use function expect;

describe('Coordinates', function () {
    it('holds a latitude and a longitude', function () {
        $coordinates = new Coordinates(48.8566, 2.3522);

        expect($coordinates->latitude)->toBe(48.8566)
            ->and($coordinates->longitude)->toBe(2.3522);
    });

    it('accepts the extreme values', function (float $latitude, float $longitude) {
        expect(new Coordinates($latitude, $longitude))->toBeInstanceOf(Coordinates::class);
    })->with([
        'north pole' => [90.0, 0.0],
        'south pole' => [-90.0, 0.0],
        'antimeridian, east' => [0.0, 180.0],
        'antimeridian, west' => [0.0, -180.0],
    ]);

    it('rejects out-of-range latitudes', function (float $latitude) {
        new Coordinates($latitude, 0.0);
    })->with([[90.1], [-90.1]])->throws(InvalidArgumentException::class, 'Invalid latitude');

    it('rejects out-of-range longitudes', function (float $longitude) {
        new Coordinates(0.0, $longitude);
    })->with([[180.1], [-180.1]])->throws(InvalidArgumentException::class, 'Invalid longitude');
});

describe('Radius', function () {
    it('holds a center and a distance in meters', function () {
        $radius = new Radius($center = new Coordinates(48.8566, 2.3522), 5000.0);

        expect($radius->center)->toBe($center)
            ->and($radius->meters)->toBe(5000.0);
    });

    it('rejects non-positive distances', function (float $meters) {
        new Radius(new Coordinates(0.0, 0.0), $meters);
    })->with([[0.0], [-1.0]])->throws(InvalidArgumentException::class, 'Radius must be greater than 0');
});

describe('BoundingBox', function () {
    it('holds a south-west and a north-east corner', function () {
        $boundingBox = new BoundingBox(
            $southWest = new Coordinates(48.80, 2.22),
            $northEast = new Coordinates(48.90, 2.47),
        );

        expect($boundingBox->southWest)->toBe($southWest)
            ->and($boundingBox->northEast)->toBe($northEast);
    });

    it('rejects a north-east corner south of the south-west corner', function () {
        new BoundingBox(new Coordinates(48.90, 2.22), new Coordinates(48.80, 2.47));
    })->throws(InvalidArgumentException::class, 'north-east corner');

    it('accepts a box crossing the antimeridian', function () {
        $boundingBox = new BoundingBox(new Coordinates(-20.0, 170.0), new Coordinates(-10.0, -170.0));

        expect($boundingBox->southWest->longitude)->toBeGreaterThan($boundingBox->northEast->longitude);
    });
});
