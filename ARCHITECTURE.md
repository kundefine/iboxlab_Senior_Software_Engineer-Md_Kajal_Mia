# All code is hand coded from migration to service to service provider (similar feature implemented before in my company)
# note: only READMD.md and ARCHITECHTRUE.md generate with claude for better view.
# I rename the provider with ProviderUsBangla (ProviderA), ProviderBimanBangla (ProviderB), ProviderNovoAir(ProviderC), now the system is scalable so in the future the provider comes in like Provider Airastra then need to add to service array in AppServiceProvider

# Architecture — Flight Search Aggregator

This document explains the key architectural decisions made in this project,
the trade-offs considered, and what would change in a production system.

---

## High-Level Overview

```
┌─────────────────────────────────────────────────────────┐
│                     Client (HTTP)                       │
└───────────────────────────┬─────────────────────────────┘
                            │ GET /api/flights/search
                            ▼
┌─────────────────────────────────────────────────────────┐
│               FlightController                          │
│  - Validates query params via FlightSearchRequest       │
│  - Builds FlightSearchParams DTO                        │
│  - Delegates entirely to FlightAggregatorService        │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│            FlightAggregatorService  (Orchestrator)      │
│                                                         │
│  foreach provider:                                      │
│    try → fetch() → collect NormalizedFlight[]           │
│    catch → log + mark provider as "failed"              │
│                                                         │
│  → FlightDeduplicator::deduplicate()                    │
│  → applyFilters()                                       │
│  → applySort()                                          │
│  → attachTotalPrice()                                   │
└──────┬─────────────────┬──────────────────┬────────────┘
       │                 │                  │
       ▼                 ▼                  ▼
  ProviderA          ProviderB          ProviderC
  (+ Normalizer)     (+ Normalizer)     (+ Normalizer)
```

---

## Design Decisions

### 1. Provider → Normalizer → Aggregator Pipeline

**Decision:** Each provider has its own dedicated Normalizer class that converts
provider-specific raw data into a common `NormalizedFlight` DTO. The Aggregator
only ever works with `NormalizedFlight` objects — it knows nothing about
individual provider schemas.

**Why:**
- Adding a new provider means creating one Provider class and one Normalizer class.
  Nothing else in the codebase needs to change.
- Each Normalizer has a single responsibility and is independently testable.
- Provider schema changes (e.g. a field rename) are isolated to one file.

**Trade-off:**
- Slightly more files up front. Justified because provider schemas drift over time
  and isolation prevents one provider's change from breaking others.

---

### 2. Deduplication Strategy

**Decision:** Flights are grouped by `flightNumber + departureTime`. When the same
flight appears in multiple providers, the cheapest price is selected and surfaced.
All provider prices are preserved in the `pricesBySource` field.

**Why `flightNumber + departureTime` as the key:**
- A flight number alone is not unique — airlines reuse codes on different routes
  or different departure times on the same day.
- Adding departure time makes the key specific enough to identify a physical
  departure without needing a central registry.

**Why cheapest price:**
- Best user experience — surface the best deal available.
- The `pricesBySource` array lets the client show alternative prices if needed.

**Edge cases considered:**
- Same flight number, different departure times → treated as two distinct flights
  (e.g. BS220 at 09:15 and BS118 at 14:30 are separate).
- Overnight flights where arrival is the next day → duration calculated correctly
  by adding 86,400 seconds when `arrival < departure`.

---

### 3. Stable Flight ID

**Decision:** `base64url(flightNumber|departureTime)` — a deterministic ID
generated from the flight's natural key.

**Why:**
- The same flight always produces the same ID regardless of which provider
  returned it. This means a client can receive an ID from the search response
  and safely use it in a booking request without ambiguity.
- No database lookup is needed to generate or resolve the ID.
- URL-safe (base64url) so it can be used directly in query strings or path params.

**Trade-off:**
- The ID is not opaque — it can be decoded to reveal the flight number and time.
  For this use case that is acceptable. For a production system where IDs should
  be opaque, a UUID stored in a cache or database would be preferred.

---

### 4. Partial Results & Provider Resilience

**Decision:** Each provider is called inside an individual `try/catch`. A failing
provider is logged and marked as `"failed"` in the response metadata. The API
still returns results from healthy providers.

**Why:**
- A single slow or broken third-party provider should not block the entire search.
- The `meta.isPartialResult` flag tells the client the results may be incomplete
  so it can display a user-facing warning ("Some providers unavailable").
- Failing fast per provider (rather than timing out the whole request) keeps
  response times predictable.

**What the response communicates:**
```json
"meta": {
  "isPartialResult": true,
  "providerStatus": {
    "providerA": "success",
    "providerB": "failed",
    "providerC": "success"
  }
}
```

---

### 5. Immutable DTOs (readonly classes)

**Decision:** `NormalizedFlight` and `FlightSearchParams` are PHP 8.2 `readonly`
classes — once constructed they cannot be mutated.

**Why:**
- Eliminates an entire class of bugs where a normalizer or filter accidentally
  modifies shared data.
- Makes the data flow explicit: data flows in one direction through the pipeline.
- Self-documenting — a `readonly` class signals "this is a value object, not an
  entity".

---

### 6. Thin Controllers

**Decision:** Controllers do only three things: validate the request, build a DTO,
call a service, and return the response. No business logic lives in a controller.

**Why:**
- Business logic in a controller cannot be unit tested without an HTTP request.
- Services can be called from console commands, queue jobs, or other controllers
  without duplicating logic.

---

### 7. Database — PostgreSQL with JSONB for Passengers

**Decision:** Passenger details are stored in a `jsonb` column rather than a
separate `passengers` table.

**Why:**
- Passenger details in a booking are a snapshot — they should not change even
  if a "passenger" record elsewhere is updated.
- The number of passengers per booking is small (1–9) and passengers are always
  read alongside their booking, never queried independently.
- `jsonb` in PostgreSQL is indexed and queryable if needed later.

**Trade-off:**
- If the requirement were to query "all bookings for passenger X" efficiently,
  a normalized `passengers` table with a foreign key would be better.
  For the current scope `jsonb` is simpler and sufficient.

---

### 8. Dependency Injection via AppServiceProvider

**Decision:** `FlightAggregatorService` receives its providers as a constructor
array, bound in `AppServiceProvider`.

**Why:**
- Adding a new provider is a one-line change in `AppServiceProvider` — no other
  class is touched.
- In tests, providers can be replaced with fakes/mocks by injecting alternatives.

```php
// Adding ProviderD requires only this change:
$app->make(ProviderD::class),   // ← add here
```

---

## What Would Change in Production

| Area | Current (Assignment) | Production Recommendation |
|---|---|---|
| **Parallelism** | Sequential provider calls | `Illuminate\Support\Facades\Concurrency` (Laravel 11) or Guzzle promise pool for true concurrent HTTP calls |
| **Caching** | No caching | Cache search results in Redis for 60–120 seconds (same route + date = same results) |
| **Provider calls** | In-process mock arrays | Real HTTP clients (Guzzle) with per-provider timeout and retry config |
| **Flight ID** | base64url string | UUID v4 stored in Redis with a TTL, mapping to the full flight snapshot |
| **Rate limiting** | None | Laravel's `throttle` middleware per IP |
| **Observability** | `Log::warning` | Structured logs + Sentry for provider errors + response time metrics |
| **Booking status** | Static `confirmed` | Async status updates from airline APIs via queued jobs |
| **Parallelism** | `usleep()` delay simulation | Remove delays; real providers have their own latency |

---

## Adding a New Provider — Step by Step

1. Create `app/Normalizers/ProviderDNormalizer.php` implementing `NormalizerInterface`
2. Create `app/Providers/ProviderD.php` implementing `FlightProviderInterface`
3. Add `$app->make(ProviderD::class)` to the providers array in `AppServiceProvider`
4. Write unit tests for the new normalizer in `tests/Unit/ProviderDNormalizerTest.php`

No other file needs to change.

---

## Request Lifecycle (Search)

```
GET /api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2

1. routes/api.php
   └─ FlightController@search

2. FlightSearchRequest::rules()
   └─ Validates: from (3-char), to (3-char), date (future), passengers (1-9)
   └─ 422 returned immediately on validation failure

3. FlightController builds FlightSearcfhParams DTO

4. FlightAggregatorService::search()
   ├─ ProviderA::fetch()  → ProviderANormalizer → NormalizedFlight[]
   ├─ ProviderB::fetch()  → ProviderBNormalizer → NormalizedFlight[]
   └─ ProviderC::fetch()  → ProviderCNormalizer → NormalizedFlight[]
   (any provider failure is caught, logged, marked "failed")

5. FlightDeduplicator::deduplicate()
   └─ Group by flightNumber+departureTime, pick cheapest, keep all prices

6. applyFilters()  →  applySort()  →  attachTotalPrice()

7. Return unified JSON response with results + meta
```

---

## Request Lifecycle (Booking)

```
POST /api/bookings

1. CreateBookingRequest::rules()
   └─ Validates flight_id, flight_number, origin, destination,
      departure_time, price, and each passenger's fields

2. BookingController delegates to BookingService::create()

3. BookingService generates unique reference: BK-YYYYMMDD-XXXXXX
   (loop-checks uniqueness against DB before inserting)

4. Booking saved to PostgreSQL bookings table

5. Return 201 with full booking object including reference
```
