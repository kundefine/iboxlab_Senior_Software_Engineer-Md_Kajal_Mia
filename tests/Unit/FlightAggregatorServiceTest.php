<?php

use App\DTOs\FlightSearchParams;
use App\DTOs\NormalizedFlight;
use App\FlightProviders\Contacts\FlightProviderInterface;
use App\Services\FlightAggregatorService;
use App\Services\FlightDeduplicator;

it('searches, deduplicates, and sorts flight results', function () {
    $providerA = new class implements FlightProviderInterface {
        public function fetch(): array
        {
            return [
                new NormalizedFlight(
                    flightNumber: 'AA101',
                    carrier: 'AA',
                    origin: 'DAC',
                    destination: 'DXB',
                    departureTime: '2026-07-01T08:00:00',
                    arrivalTime: '2026-07-01T12:30:00',
                    stops: 0,
                    price: 320.00,
                    currency: 'USD',
                    source: 'us-bangla',
                ),
                new NormalizedFlight(
                    flightNumber: 'BS220',
                    carrier: 'BS',
                    origin: 'DAC',
                    destination: 'DXB',
                    departureTime: '2026-07-01T09:15:00',
                    arrivalTime: '2026-07-01T15:00:00',
                    stops: 1,
                    price: 310.00,
                    currency: 'USD',
                    source: 'us-bangla',
                ),
            ];
        }

        public function getName(): string
        {
            return 'provider-a';
        }
    };

    $providerB = new class implements FlightProviderInterface {
        public function fetch(): array
        {
            return [
                new NormalizedFlight(
                    flightNumber: 'AA101',
                    carrier: 'AA',
                    origin: 'DAC',
                    destination: 'DXB',
                    departureTime: '2026-07-01T08:00:00',
                    arrivalTime: '2026-07-01T12:30:00',
                    stops: 0,
                    price: 300.00,
                    currency: 'USD',
                    source: 'biman-bangla',
                ),
            ];
        }

        public function getName(): string
        {
            return 'provider-b';
        }
    };

    $service = new FlightAggregatorService([$providerA, $providerB], new FlightDeduplicator());

    $result = $service->search(new FlightSearchParams(
        from: 'DAC',
        to: 'DXB',
        date: '2026-07-01',
        passengers: 2,
        sortBy: 'price',
    ));

    expect($result['meta']['totalResults'])->toBe(2);
    expect($result['meta']['isPartialResult'])->toBeFalse();
    expect($result['meta']['providerStatus'])->toBe([
        'provider-a' => 'success',
        'provider-b' => 'success',
    ]);
    expect($result['results'])->toHaveCount(2);
    expect($result['results'][0]['flightNumber'])->toBe('AA101');
    expect($result['results'][0]['price'])->toBe(300.0);
    expect($result['results'][0]['totalPrice'])->toBe(600.0);
});
