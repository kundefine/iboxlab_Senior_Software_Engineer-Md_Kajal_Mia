<?php

use App\DTOs\FlightSearchParams;
use App\Models\Booking;
use App\Services\FlightAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores a booking', function () {
    $payload = [
        'flight_id' => 'QUExMDF8MjAyNi0wNy0wMVQwODowMDowMA',
        'flight_number' => 'AA101',
        'origin' => 'DAC',
        'destination' => 'DXB',
        'departure_time' => '2026-07-01T08:00:00',
        'price' => 320,
        'passengers' => [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-05-15',
                'passport_number' => 'AB1234567',
                'nationality' => 'BD',
            ],
        ],
    ];

    $response = $this->postJson('/api/bookings', $payload);

    $response->assertCreated()
        ->assertJsonPath('message', 'Booking confirmed successfully.')
        ->assertJsonPath('booking.flight_number', 'AA101')
        ->assertJsonPath('booking.total_price', 320);

    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseHas('bookings', [
        'flight_id' => 'QUExMDF8MjAyNi0wNy0wMVQwODowMDowMA',
        'reference' => $response->json('booking.reference'),
        'total_price' => 320,
        'status' => 'confirmed',
    ]);
});

it('shows a booking by reference', function () {
    $booking = Booking::create([
        'reference' => 'BK-20260701-ABC123',
        'flight_id' => 'QUExMDF8MjAyNi0wNy0wMVQwODowMDowMA',
        'flight_number' => 'AA101',
        'origin' => 'DAC',
        'destination' => 'DXB',
        'departure_time' => '2026-07-01T08:00:00',
        'price_per_passenger' => 320,
        'total_price' => 320,
        'passengers' => [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-05-15',
                'passport_number' => 'AB1234567',
                'nationality' => 'BD',
            ],
        ],
        'status' => 'confirmed',
    ]);

    $response = $this->getJson('/api/bookings/' . $booking->reference);

    $response->assertOk()
        ->assertJsonPath('booking.reference', 'BK-20260701-ABC123')
        ->assertJsonPath('booking.flight_number', 'AA101');
});

it('searches flights through the api', function () {
    $this->app->instance(FlightAggregatorService::class, new class extends FlightAggregatorService {
        public function __construct()
        {
            parent::__construct([], new \App\Services\FlightDeduplicator());
        }

        public function search(FlightSearchParams $params): array
        {
            expect($params->from)->toBe('DAC');
            expect($params->to)->toBe('DXB');
            expect($params->date)->toBe('2026-07-01');
            expect($params->passengers)->toBe(2);
            expect($params->sortBy)->toBe('price');

            return [
                'results' => [
                    [
                        'id' => 'AA101|2026-07-01T08:00:00',
                        'flightNumber' => 'AA101',
                        'carrier' => 'AA',
                        'origin' => 'DAC',
                        'destination' => 'DXB',
                        'departureTime' => '2026-07-01T08:00:00',
                        'arrivalTime' => '2026-07-01T12:30:00',
                        'stops' => 0,
                        'price' => 300,
                        'currency' => 'USD',
                        'durationMins' => 270,
                        'pricesBySource' => [
                            ['source' => 'provider-a', 'price' => 300],
                        ],
                        'totalPrice' => 600,
                    ],
                ],
                'meta' => [
                    'totalResults' => 1,
                    'isPartialResult' => false,
                    'providerStatus' => [
                        'provider-a' => 'success',
                    ],
                    'searchParams' => [
                        'from' => 'DAC',
                        'to' => 'DXB',
                        'date' => '2026-07-01',
                        'passengers' => 2,
                    ],
                ],
            ];
        }
    });

    $response = $this->getJson('/api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2&sort_by=price');

    $response->assertOk()
        ->assertJsonPath('meta.totalResults', 1)
        ->assertJsonPath('meta.isPartialResult', false)
        ->assertJsonPath('results.0.flightNumber', 'AA101')
        ->assertJsonPath('results.0.totalPrice', 600);
});
