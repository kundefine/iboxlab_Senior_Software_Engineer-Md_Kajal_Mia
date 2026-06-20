<?php

namespace App\Services;

use App\DTOs\FlightSearchParams;
use Illuminate\Support\Facades\Log;

class FlightAggregatorService
{
    public function __construct(
        private array               $providers,
        private FlightDeduplicator  $deduplicator,
    ) {}

    public function search(FlightSearchParams $params): array
    {
        $allFlights    = [];
        $providerStatus = [];

        // প্রতিটা provider থেকে data নাও
        // PHP-তে true parallel নেই, তাই sequential —
        // Production-এ Fibers বা ReactPHP ব্যবহার করা যায়
        foreach ($this->providers as $provider) {
            try {
                $flights = $provider->fetch($params);
                $allFlights = array_merge($allFlights, $flights);
                $providerStatus[$provider->getName()] = 'success';
            } catch (\Throwable $e) {
                Log::warning("Provider {$provider->getName()} failed", [
                    'error' => $e->getMessage()
                ]);
                $providerStatus[$provider->getName()] = 'failed';
            }
        }

        // Deduplicate
        $flights = $this->deduplicator->deduplicate($allFlights);

        // Filter
        $flights = $this->applyFilters($flights, $params);

        // Sort
        $flights = $this->applySort($flights, $params->sortBy);

        // Total price with passengers
        $flights = array_map(function ($f) use ($params) {
            $f['totalPrice'] = $f['price'] * $params->passengers;
            return $f;
        }, $flights);

        $failedCount = count(array_filter($providerStatus, fn($s) => $s === 'failed'));

        return [
            'results' => array_values($flights),
            'meta'    => [
                'totalResults'    => count($flights),
                'isPartialResult' => $failedCount > 0,
                'providerStatus'  => $providerStatus,
                'searchParams'    => [
                    'from'       => $params->from,
                    'to'         => $params->to,
                    'date'       => $params->date,
                    'passengers' => $params->passengers,
                ],
            ],
        ];
    }

    private function applyFilters(array $flights, FlightSearchParams $params): array
    {
        return array_filter($flights, function ($f) use ($params) {
            if ($params->maxPrice !== null && $f['price'] > $params->maxPrice) return false;
            if ($params->maxStops !== null && $f['stops'] > $params->maxStops) return false;
            return true;
        });
    }

    private function applySort(array $flights, string $sortBy): array
    {
        usort($flights, match($sortBy) {
            'duration'  => fn($a, $b) => $a['durationMins'] <=> $b['durationMins'],
            'departure' => fn($a, $b) => $a['departureTime'] <=> $b['departureTime'],
            default     => fn($a, $b) => $a['price'] <=> $b['price'], // price
        });

        return $flights;
    }


}
