# Handoff — compiling Honey ODM criteria to Meilisearch

Target: `honey-odm/meilisearch`, `MeiliTransport::retrieveDocuments(AsDocument $classMetadata, Criteria $criteria)`.

This document maps every node and operator of `Honey\ODM\Core\Criteria` onto Meilisearch's search API. It is written
against core as of the 19-operator enum, the `not*` / `outside*` shorthands and `Criteria::metadata()`.

Everything marked *verified* was run against a live **Meilisearch 1.49** on a throwaway index; the rest is read from
the docs and deserves an integration test.

Reference implementation of the same contract, in memory:
`src/Transport/InMemoryTransport.php` in this repository.

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
| `metadata`     | opt-in passthrough, see below                                           |

`SortDirection::Ascending` → `asc`, `SortDirection::Descending` → `desc`. Sorted attributes must be declared in
`sortableAttributes`.

### `metadata`

`Criteria::metadata(string $key, mixed $value)` is a free-form bag for what the portable API cannot express. It is the
natural home for Meilisearch's own search parameters — `vector`, `hybrid`, `matchingStrategy`,
`attributesToHighlight`, `distinct`… Pick the keys you support, forward them, and **ignore the rest silently**: unlike
an unsupported operator, an unknown metadata key must never throw, since the same criteria may be aimed at several
platforms.

Whatever you decide to support, document the exact key list in the adapter's README — it is the only part of the
query API that isn't discoverable from the core.

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

`Negation` carries more weight than it looks: most of the `Field` shorthands (`notContains()`, `notExists()`,
`isNotEmpty()`, `notBetween()`, `notHasAll()`, `outsideGeoRadius()`, `outsideGeoBoundingBox()`…) build one around
their positive counterpart instead of introducing an operator. Get `NOT (…)` right and they all work — including
`NOT (_geoRadius(…))`, which Meilisearch does support. Only `NOT_EQUALS`, `NOT_IN` and `IS_NOT_NULL` are operators of
their own.

Meilisearch also has native `field NOT EXISTS`, `field IS NOT EMPTY` and `field IS NOT NULL` forms. Folding
`Negation(Comparison(EXISTS))` into them is a legitimate optimisation, not a requirement — plain `NOT (…)` is
equivalent.

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
| `CONTAINS`                 | `field CONTAINS substring`                          | behind an experimental flag, see below |
| `STARTS_WITH`              | `field STARTS WITH prefix`                          | no flag needed |
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

Both arrived in Meilisearch 1.12, but they are **not** gated the same way — checked against 1.49:

- `STARTS WITH` works out of the box.
- `CONTAINS` requires the `contains filter` experimental feature. Without it, the engine answers:

  ```
  code:    feature_not_enabled
  message: Using `CONTAINS` in a filter requires enabling the `contains filter` experimental feature.
           See https://github.com/orgs/meilisearch/discussions/7636
  ```

**Decision: always emit them, and let the engine's error surface.** No adapter-level switch — it would be a copy of
the instance's configuration, and a copy drifts. The engine is the authority on what it supports, and the error above
points at the actual fix (enable the feature) better than an `UnsupportedExpressionException` naming an operator that
*is* supported, one setting away.

Worth a line in the adapter's README so the error isn't a surprise.

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

**Antimeridian: nothing to do.** A box crossing it is legal in core — signalled by
`southWest->longitude > northEast->longitude`, which `InMemoryTransport` handles by testing `lng >= west || lng <= east`
instead of `&&`. Meilisearch does the same natively, verified against 1.49 on four documents:

| filter                                    | returns                |
|-------------------------------------------|------------------------|
| `_geoBoundingBox([0, 178], [-20, 170])`   | Fiji (177)             |
| `_geoBoundingBox([0, -170], [-20, 170])`  | Fiji, Funafuti (179.19), Samoa (-172.1) |

So the corner swap above is the whole implementation — no splitting into two OR'd boxes, no
`UnsupportedExpressionException`.

**Bonus: forgetting the swap fails loudly**, which is the good news here. Passing the corners in core order gives:

```
code:    invalid_search_filter
message: The top latitude `-20` is below the bottom latitude `0`.
```

An integration test on a real index will catch a missing flip immediately — it does not silently return the wrong
set.

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
