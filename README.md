# ✈️ Flight Search Aggregator

A backend API that aggregates flight data from multiple providers into a single unified response.  
Built with **PHP 8.3 + Laravel 12 or 13 + sqlite**.

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Running the Application](#running-the-application)
- [Running Tests](#running-tests)
- [API Reference](#api-reference)
- [Project Structure](#project-structure)

---

## Requirements

- PHP 8.3+
- Composer

---

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/kundefine/iboxlab_Senior_Software_Engineer-Md_Kajal_Mia.git
cd flight-aggregator
```

### 2. Install dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure the database

Open `.env` and update the PostgreSQL credentials:

```env
DB_CONNECTION=sqlite
```

---

### 5. Run migrations

```bash
php artisan migrate
```

---

## Running the Application

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`.

---

## Running Tests

```bash
php artisan test
```
## API Reference

### 1. Search Flights

```
GET /api/flights/search
```

**Query Parameters**

| Parameter    | Type    | Required | Default    | Description                              |
|-------------|---------|----------|------------|------------------------------------------|
| `from`      | string  | ✅       | —          | 3-letter IATA origin code (e.g. `DAC`)   |
| `to`        | string  | ✅       | —          | 3-letter IATA destination code (e.g. `DXB`) |
| `date`      | string  | ✅       | —          | Travel date in `YYYY-MM-DD` format       |
| `passengers`| integer | ❌       | `1`        | Number of passengers (1–9)               |
| `sort_by`   | string  | ❌       | `price`    | Sort order: `price` \| `duration` \| `departure` |
| `max_price` | number  | ❌       | —          | Filter: max price per passenger (USD)    |
| `max_stops` | integer | ❌       | —          | Filter: max number of stops (0–5)        |

**Example Request**

```bash
curl "http://localhost:8000/api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2&sort_by=price"
```

**Example Response**

```json
{
  "results": [
    {
      "id": "QlMyMThfMjAyNi0wNy0wMVQxNDozMDowMA",
      "flightNumber": "BS118",
      "carrier": "BS",
      "origin": "DAC",
      "destination": "DXB",
      "departureTime": "2026-07-01T14:30:00",
      "arrivalTime": "2026-07-01T19:20:00",
      "stops": 1,
      "price": 265.00,
      "currency": "USD",
      "durationMins": 290,
      "totalPrice": 530.00,
      "passengers": 2,
      "pricesBySource": [
        { "source": "providerB", "price": 265.00 }
      ]
    },
    {
      "id": "Q0ozMDBfMjAyNi0wNy0wMVQwNjowMDowMA",
      "flightNumber": "CJ300",
      "carrier": "CJ",
      "origin": "DAC",
      "destination": "DXB",
      "departureTime": "2026-07-01T06:00:00",
      "arrivalTime": "2026-07-01T11:00:00",
      "stops": 2,
      "price": 270.00,
      "currency": "USD",
      "durationMins": 300,
      "totalPrice": 540.00,
      "passengers": 2,
      "pricesBySource": [
        { "source": "providerC", "price": 270.00 }
      ]
    }
  ],
  "meta": {
    "totalResults": 6,
    "isPartialResult": false,
    "providerStatus": {
      "providerA": "success",
      "providerB": "success",
      "providerC": "success"
    },
    "searchParams": {
      "from": "DAC",
      "to": "DXB",
      "date": "2026-07-01",
      "passengers": 2,
      "sortBy": "price",
      "maxPrice": null,
      "maxStops": null
    }
  }
}
```

**Response Fields**

| Field                   | Description                                                          |
|------------------------|----------------------------------------------------------------------|
| `id`                   | Stable, deterministic flight identifier safe for booking references  |
| `price`                | Best (lowest) price per passenger across all providers               |
| `totalPrice`           | `price × passengers`                                                 |
| `durationMins`         | Flight duration in minutes                                           |
| `pricesBySource`       | All provider prices for this flight (transparency)                   |
| `meta.isPartialResult` | `true` if one or more providers failed — results may be incomplete   |
| `meta.providerStatus`  | Per-provider fetch status (`success` or `failed`)                    |

---

### 2. Create Booking

```
POST /api/bookings
Content-Type: application/json
```

**Request Body**

```json
{
  "flight_id":      "QUExMDFfMjAyNi0wNy0wMVQwODowMDowMA",
  "flight_number":  "AA101",
  "origin":         "DAC",
  "destination":    "DXB",
  "departure_time": "2026-07-01T08:00:00",
  "price":          320.00,
  "currency":       "USD",
  "passengers": [
    {
      "first_name":      "John",
      "last_name":       "Doe",
      "date_of_birth":   "1990-05-15",
      "passport_number": "AB1234567",
      "nationality":     "BD"
    }
  ]
}
```

**Example Request**

```bash
curl -X POST http://localhost:8000/api/bookings \
  -H "Content-Type: application/json" \
  -d '{
    "flight_id": "QUExMDFfMjAyNi0wNy0wMVQwODowMDowMA",
    "flight_number": "AA101",
    "origin": "DAC",
    "destination": "DXB",
    "departure_time": "2026-07-01T08:00:00",
    "price": 320.00,
    "currency": "USD",
    "passengers": [
      {
        "first_name": "John",
        "last_name": "Doe",
        "date_of_birth": "1990-05-15",
        "passport_number": "AB1234567",
        "nationality": "BD"
      }
    ]
  }'
```

**Example Response** `201 Created`

```json
{
  "message": "Booking confirmed successfully.",
  "booking": {
    "id": 1,
    "reference": "BK-20260701-A3F9K2",
    "flight_id": "QUExMDFfMjAyNi0wNy0wMVQwODowMDowMA",
    "flight_number": "AA101",
    "origin": "DAC",
    "destination": "DXB",
    "departure_time": "2026-07-01T08:00:00",
    "price_per_passenger": 320.00,
    "currency": "USD",
    "total_price": 320.00,
    "status": "confirmed",
    "passengers": [
      {
        "first_name": "John",
        "last_name": "Doe",
        "date_of_birth": "1990-05-15",
        "passport_number": "AB1234567",
        "nationality": "BD"
      }
    ],
    "created_at": "2026-07-01T10:00:00.000000Z",
    "updated_at": "2026-07-01T10:00:00.000000Z"
  }
}
```

---

### 3. Retrieve Booking

```
GET /api/bookings/{reference}
```

**Example Request**

```bash
curl http://localhost:8000/api/bookings/BK-20260701-A3F9K2
```

**Example Response** `200 OK`

```json
{
  "booking": {
    "id": 1,
    "reference": "BK-20260701-A3F9K2",
    "flight_id": "QUExMDFfMjAyNi0wNy0wMVQwODowMDowMA",
    "flight_number": "AA101",
    "origin": "DAC",
    "destination": "DXB",
    "departure_time": "2026-07-01T08:00:00",
    "price_per_passenger": 320.00,
    "currency": "USD",
    "total_price": 320.00,
    "status": "confirmed",
    "passengers": [...],
    "created_at": "2026-07-01T10:00:00.000000Z",
    "updated_at": "2026-07-01T10:00:00.000000Z"
  }
}
```

---

### Error Responses

All errors follow a consistent format:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "from": ["The from field is required."],
    "date": ["Travel date cannot be in the past."]
  }
}
```

| HTTP Status | Meaning                                      |
|------------|----------------------------------------------|
| `400`      | Validation error (missing/invalid parameters) |
| `404`      | Booking reference not found                  |
| `500`      | Unexpected server error                      |

---

## Project Structure

```
app/
├── DTOs/                   # Immutable data transfer objects
│   ├── FlightSearchParams.php
│   └── NormalizedFlight.php
├── Http/
│   ├── Controllers/        # Thin controllers — delegate to services
│   ├── Requests/           # Form request validation
│   └── Resources/          # (reserved for future API transformers)
├── Models/
│   └── Booking.php
├── FlightDataParser/            # One normalizer per provider
│   ├── Contracts/FlightDataParserInterface.php
│   ├── ProviderBimanBanglaDataParser.php
│   ├── ProviderNovoAirDataParser.php
│   └── ProviderUSBangla.php
├── FlightProviders/              # Mock provider clients
│   ├── Contracts/FlightProviderInterface.php
│   ├── ProviderBimanBangla.php
│   ├── ProviderNovoAir.php
│   └── ProviderUSBangla.php
└── Services/
    ├── FlightAggregatorService.php   # Main orchestrator
    ├── FlightDeduplicator.php        # Merge + best-price logic
    └── BookingService.php
```
