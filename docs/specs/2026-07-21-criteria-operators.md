# Criteria: geo, substring, existence, range and set operators

- **Date:** 2026-07-21
- **Status:** approved
- **Scope:** `honey-odm/core` — `Honey\ODM\Core\Criteria`

## Goal

The generic criteria model shipped with the portable-metadata refactor covers 12 operators. Real-world adapters
(Meilisearch first) need seven more: geo filtering, substring suffix matching, field existence, emptiness, ranges, and
set containment.

Everything below stays platform-agnostic: the core describes *intent*, each transport compiles it to its native
dialect, and throws `UnsupportedExpressionException::operator()` for what it cannot express.

## Value objects

### `Honey\ODM\Core\Criteria\Geo\Coordinates`

```php
final readonly class Coordinates
{
    public function __construct(public float $latitude, public float $longitude);
}
```

Throws `InvalidArgumentException` when latitude is outside `[-90, 90]` or longitude outside `[-180, 180]`.

### `Honey\ODM\Core\Criteria\Geo\Radius`

```php
final readonly class Radius
{
    public function __construct(public Coordinates $center, public float $meters);
}
```

Throws `InvalidArgumentException` when `$meters <= 0`.

### `Honey\ODM\Core\Criteria\Geo\BoundingBox`

```php
final readonly class BoundingBox
{
    public function __construct(public Coordinates $southWest, public Coordinates $northEast);
}
```

**Corner convention: south-west then north-east**, i.e. the min corner then the max corner. This follows GeoJSON
(RFC 7946), PostGIS `ST_MakeEnvelope`, OGC/WMS `BBOX`, Leaflet, Google Maps and Mapbox GL.

Two conventions were deliberately rejected for the core contract:

- **NW/SE** (`top_left` / `bottom_right`) — the raster/screen convention, where Y grows downward. Elasticsearch uses
  it; cartography libraries generally do not.
- **NE/SW** — what Meilisearch's `_geoBoundingBox` expects. Max corner first, the exact reverse of GeoJSON.

Adapters convert. See `docs/handoffs/meilisearch-adapter.md`.

Validation: throws `InvalidArgumentException` when `northEast->latitude < southWest->latitude`. Longitudes are **not**
compared: `southWest->longitude > northEast->longitude` legitimately means the box crosses the antimeridian.

No derived cardinal accessors (`$bbox->north`, ...): PHP forbids hooked properties on readonly classes
(`Hooked properties cannot be readonly`). Adapters read `$bbox->northEast->latitude` and friends.

### `Honey\ODM\Core\Criteria\Range`

```php
final readonly class Range
{
    public function __construct(
        public mixed $left,
        public mixed $right,
        public bool $includeLeft = true,
        public bool $includeRight = true,
    );
}
```

Either bound may be `null` (open-ended range), but **not both** — throws `InvalidArgumentException`.

## Operators

Seven new cases on `Honey\ODM\Core\Criteria\Operator`, keeping the existing camelCase backing values:

| `Operator`                  | Backing value            | `Comparison::$value` |
|-----------------------------|--------------------------|----------------------|
| `ENDS_WITH`                 | `endsWith`               | `string`             |
| `EXISTS`                    | `exists`                 | `null`               |
| `IS_EMPTY`                  | `isEmpty`                | `null`               |
| `HAS_ALL`                   | `hasAll`                 | `list<mixed>`        |
| `BETWEEN`                   | `between`                | `Range`              |
| `WITHIN_GEO_RADIUS`         | `withinGeoRadius`        | `Radius`             |
| `WITHIN_GEO_BOUNDING_BOX`   | `withinGeoBoundingBox`   | `BoundingBox`        |

## `Field` API

```php
field('title')->endsWith('Night');
field('deletedAt')->exists();
field('summary')->isEmpty();
field('tags')->hasAll(['php', 'odm']);
field('price')->between(10, 100, includeRight: false);
field('price')->between(null, 100);                       // open-ended
field('coordinates')->withinGeoRadius(48.8566, 2.3522, 5000);
field('coordinates')->withinGeoBoundingBox(48.80, 2.22, 48.90, 2.47);  // swLat, swLon, neLat, neLon
```

The fluent methods take scalars only. Callers holding a pre-built value object use the node directly:
`new Comparison('coordinates', Operator::WITHIN_GEO_RADIUS, $radius)`.

## Semantics contract

These are the guarantees adapters must honour:

- **`exists()` is not `isNotNull()`.** It tests the presence of the *key* in the document, regardless of its value.
  A field present and set to `null` satisfies `exists()` and fails `isNotNull()`.
- **`isEmpty()`** matches `null`, `""`, `[]` and `{}`. The exact set is platform-dependent; a platform that cannot
  distinguish empty from missing should document it rather than silently widen the match.
- **`hasAll()` vs `in()`.** On an array field, `in()` matches when *at least one* of the given values is present,
  `hasAll()` when *all* of them are. On a scalar field, `hasAll()` with more than one value can never match.
- **`between()`** bound inclusivity is per-side. An open-ended range is equivalent to a single comparison
  (`between(null, 100)` ≡ `lessThanOrEquals(100)`), but is kept as a `BETWEEN` node so adapters can emit native range
  syntax.
- **Geo distances** are always expressed in **meters**.

Values are compared against their **storage** representation, as with every other operator: property transformers are
not applied to criteria values.

## Negative variants

Out of scope. `NOT_EXISTS`, `IS_NOT_EMPTY`, `NOT_CONTAINS`, `NOT_STARTS_WITH` and `NOT_ENDS_WITH` are not added — the
existing `Negation` node covers them (`not(field('deletedAt')->exists())`) and each extra operator is compilation work
for every adapter. `EQUALS`/`NOT_EQUALS` and `IN`/`NOT_IN` stay as they are; they predate this decision.

## Reference transport

`tests/Implementation/Transport/TestTransport` implements all seven operators in memory:

| Operator                   | In-memory implementation                                                        |
|----------------------------|---------------------------------------------------------------------------------|
| `ENDS_WITH`                | `str_ends_with()`                                                                |
| `EXISTS`                   | `array_key_exists()` on the raw document — evaluated before the value is read     |
| `IS_EMPTY`                 | `null`, `''` or `[]`                                                             |
| `HAS_ALL`                  | every requested value present in the array-cast field value                       |
| `BETWEEN`                  | spaceship comparison per bound, honouring `includeLeft` / `includeRight`          |
| `WITHIN_GEO_RADIUS`        | haversine distance (earth radius 6371008.8 m) against `Radius::$meters`           |
| `WITHIN_GEO_BOUNDING_BOX`  | latitude range + longitude range, **handling antimeridian crossing**              |

Geo fields are read as `['lat' => float, 'lng' => float]`; anything else fails to match.

Note: `EXISTS` cannot go through the existing `compare()` helper, which resolves the value with `?? null` and so
cannot distinguish a missing key from a null value. It is handled before that resolution.

## Test plan

- `tests/Unit/Criteria/ExpressionTest.php` — the four scalar-valued methods join the existing dataset; the three
  value-object methods get dedicated cases (`toBe` cannot compare freshly built objects).
- `tests/Unit/Criteria/GeoTest.php` — `Coordinates` / `Radius` / `BoundingBox` validation, including the accepted
  antimeridian case.
- `tests/Unit/Criteria/RangeTest.php` — open-ended bounds accepted, both-null rejected.
- `tests/Unit/Repository/ObjectRepositoryTest.php` — one behavioural assertion per operator against `TestTransport`,
  on a fixture set carrying tags and coordinates. This is where the semantics contract is proven.

100% coverage on `src` must hold (`composer tests:run` runs with `--min=100`).

## Documentation

- `README.md` — new rows in the operators table, plus a "Geo, ranges and sets" subsection covering the value objects,
  the SW/NE convention and the `exists`/`isNull`/`isEmpty` distinction.
- `docs/handoffs/meilisearch-adapter.md` — per-operator mapping to Meilisearch filter syntax for the adapter
  implementation, including what Meilisearch cannot express and must reject.
