# Handoff — compiling Honey ODM criteria to Meilisearch

Target: `honey-odm/meilisearch`, `MeiliTransport::retrieveDocuments(AsDocument $classMetadata, Criteria $criteria)`.

This document maps every node and operator of `Honey\ODM\Core\Criteria` onto Meilisearch's search API. It is written
against core as of the 7-operator extension (`ENDS_WITH`, `HAS_ALL`, `EXISTS`, `IS_EMPTY`, `BETWEEN`,
`WITHIN_GEO_RADIUS`, `WITHIN_GEO_BOUNDING_BOX`).

Reference implementation of the same contract, in memory:
`tests/Implementation/Transport/TestTransport.php` in this repository.

## Ground rules

1. **Criteria speak PHP property names, Meilisearch speaks field names.** Translate every property with
   `$classMetadata->getFieldName($property)` before emitting anything. Never pass `$comparison->property` through.
2. **Values are already in storage form.** Property transformers are not applied to criteria values — a
   `DateTimeImmutable` property stored as `Y-m-d` is filtered with the string `'1925-04-10'`, not a `DateTimeImmutable`.
3. **Anything you cannot express must throw**, never silently degrade:
   `throw UnsupportedExpressionException::operator($operator)` / `::expression($expression)` / `::feature('...')`.
   A filter that silently widens the result set is worse than a hard failure.
4. **Filterable attributes.** Meilisearch rejects filters on attributes missing from the index's `filterableAttributes`.
   The adapter knows this at compile time via its platform metadata (`#[Meili\Attribute(filterable: true)]`) — failing
   early with a message naming the property beats forwarding a 400 from the engine.

## Criteria → search parameters

| Criteria       | Meilisearch parameter                                                   |
|----------------|-------------------------------------------------------------------------|
| `search`       | `q`                                                                     |
| `where`        | `filter` (compiled expression tree)                                     |
| `orderBy`      | `sort`, as `["field:asc", "other:desc"]`                                 |
| `limit`        | `limit`                                                                 |
| `offset`       | `offset`                                                                |

`SortDirection::Ascending` → `asc`, `SortDirection::Descending` → `desc`. Sorted attributes must be declared in
`sortableAttributes`.

## Nodes

| Node                                 | Compiles to                                              |
|--------------------------------------|----------------------------------------------------------|
| `CompositeExpression` (`AND`)        | `(a AND b AND c)` — always parenthesize                   |
| `CompositeExpression` (`OR`)         | `(a OR b OR c)` — always parenthesize                     |
| `Negation`                           | `NOT (expression)`                                        |
| `Comparison`                         | see the operator table below                              |
| Anything else                        | `UnsupportedExpressionException::expression($expression)` |

Parenthesizing unconditionally is the safe move: `a AND b OR c` and `a AND (b OR c)` differ, and the AST is the only
thing that knows which one the caller meant.

## Operators

| `Operator`                 | Meilisearch filter                                  | Notes |
|----------------------------|-----------------------------------------------------|-------|
| `EQUALS`                   | `field = value`                                     | |
| `NOT_EQUALS`               | `field != value`                                    | |
| `GREATER_THAN`             | `field > value`                                     | |
| `GREATER_THAN_OR_EQUALS`   | `field >= value`                                    | |
| `LESS_THAN`                | `field < value`                                     | |
| `LESS_THAN_OR_EQUALS`      | `field <= value`                                    | |
| `BETWEEN`                  | `field left TO right`, **or** a pair of comparisons | see below |
| `IN`                       | `field IN [a, b]`                                   | |
| `NOT_IN`                   | `field NOT IN [a, b]`                               | |
| `HAS_ALL`                  | `(field = a AND field = b)`                         | see below |
| `CONTAINS`                 | `field CONTAINS substring`                          | version-gated, see below |
| `STARTS_WITH`              | `field STARTS WITH prefix`                          | version-gated, see below |
| `ENDS_WITH`                | ❌                                                   | **throw** `::operator()` — no suffix filter exists |
| `IS_NULL`                  | `field IS NULL`                                     | |
| `IS_NOT_NULL`              | `field IS NOT NULL`                                 | |
| `EXISTS`                   | `field EXISTS`                                      | |
| `IS_EMPTY`                 | `field IS EMPTY`                                    | |
| `WITHIN_GEO_RADIUS`        | `_geoRadius(lat, lng, meters)`                      | see below |
| `WITHIN_GEO_BOUNDING_BOX`  | `_geoBoundingBox([neLat, neLng], [swLat, swLng])`   | **corner order flips**, see below |

### `BETWEEN`

`$comparison->value` is a `Range` with `left`, `right`, `includeLeft`, `includeRight`.

Meilisearch's native `field 1 TO 10` syntax is **inclusive on both ends** and requires both bounds. So:

- both bounds present and both inclusive → `field left TO right`
- anything else → emit comparisons and AND them:
  `left` → `>=` when `includeLeft`, `>` otherwise; `right` → `<=` when `includeRight`, `<` otherwise
- one bound `null` → a single comparison (the core guarantees they are never both null)

### `HAS_ALL`

On an array attribute, Meilisearch's `=` means "the array contains this value". `HAS_ALL` is therefore an `AND` of
equalities on the same field: `(tags = "monument" AND tags = "paris")`.

`IN` keeps its natural meaning (any of), which is exactly the core contract. This is the one place where the two
operators visibly diverge, and it is worth an integration test on a real array attribute.

On a scalar attribute, `HAS_ALL` with more than one value can never match — that is expected, not a bug to work around.

### `CONTAINS` / `STARTS_WITH`

These arrived in Meilisearch 1.12 behind the `containsFilter` experimental feature flag. **Verify against the version
you target**: if the flag is off, the engine returns an error the adapter cannot recover from. Two defensible options —
pick one and document it:

- always emit them, and let the engine's error surface
- expose an adapter-level switch, and throw `UnsupportedExpressionException::operator()` when disabled

The second is friendlier: the failure names the operator instead of surfacing an opaque 400.

### Geo

Documents must carry a `_geo` object shaped `{"lat": …, "lng": …}`, and `_geo` must be listed in
`filterableAttributes`.

**`WITHIN_GEO_RADIUS`** — `$comparison->value` is a `Radius`:

```php
sprintf('_geoRadius(%s, %s, %s)', $radius->center->latitude, $radius->center->longitude, $radius->meters)
```

Distances are in meters on both sides, so no conversion. Note that Meilisearch's geo filters always apply to `_geo`,
not to the mapped field name — if the property maps to something else, that is a mapping error worth rejecting
explicitly.

**`WITHIN_GEO_BOUNDING_BOX`** — ⚠️ **the corner convention differs.**

Core uses **south-west → north-east** (min corner, then max corner), following GeoJSON, PostGIS, OGC/WMS, Leaflet and
Google Maps. Meilisearch's `_geoBoundingBox` takes **top-right (NE) then bottom-left (SW)** — the reverse order.

The conversion is a straight swap, since both conventions use true corners:

```php
sprintf(
    '_geoBoundingBox([%s, %s], [%s, %s])',
    $boundingBox->northEast->latitude, $boundingBox->northEast->longitude,
    $boundingBox->southWest->latitude, $boundingBox->southWest->longitude,
);
```

**Open question for the implementer:** a box crossing the antimeridian is legal in core — it is signalled by
`southWest->longitude > northEast->longitude`, and `TestTransport` handles it by testing
`lng >= west || lng <= east`. Confirm how Meilisearch behaves on such a box against your target version. If it does
not support the wrap, the two correct options are to split it into two OR'd boxes (`[west, 180]` and `[-180, east]`),
or to throw `UnsupportedExpressionException::feature('antimeridian-crossing bounding box')`. Do not let it through
untested — the failure mode is silently empty results.

## Escaping

Meilisearch filter values need quoting whenever they are strings, and embedded quotes need escaping. Build values
through a single helper rather than inline `sprintf` at each call site: booleans and numbers go bare, strings are
double-quoted with `"` escaped, `null` only ever appears through `IS NULL` (never as a value).

## Suggested test coverage

The core's `tests/Unit/Repository/ObjectRepositoryTest.php` (`describe('ObjectRepository operators')`) is a ready-made
checklist — the same fixture set exercised against a real index proves the compilation end to end:

- suffix filter → asserts `ENDS_WITH` throws rather than returning wrong results
- `exists()` on a document missing the key vs. one holding `null` — the distinction `EXISTS` / `IS_NULL` / `IS_EMPTY`
- `hasAll()` vs `in()` on the same array attribute
- inclusive, exclusive and open-ended ranges, plus a `null` value never matching
- geo radius at two distances, geo bounding box, and the antimeridian case
