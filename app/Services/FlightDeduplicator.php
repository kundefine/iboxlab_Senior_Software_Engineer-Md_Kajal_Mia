<?php


namespace App\Services;

use App\DTOs\NormalizedFlight;

class FlightDeduplicator
{
    public function deduplicate(array $flights): array
    {
        // flight number + departure time দিয়ে group করো
        $groups = [];
        foreach ($flights as $flight) {
            $key = $flight->deduplicationKey(); // "EK585_2026-07-01T03:45:00"
            $groups[$key][] = $flight;
        }

        $result = [];
        foreach ($groups as $key => $duplicates) {
            $result[] = $this->selectBest($duplicates);
        }

        return $result;
    }

    private function selectBest(array $duplicates): array
    {
        // সবচেয়ে কম দামেরটা নাও
        usort($duplicates, fn($a, $b) => $a->price <=> $b->price);
        $best = $duplicates[0];

        return [
            'id' => $best->stableId(),
            'flightNumber' => $best->flightNumber,
            'carrier' => $best->carrier,
            'origin' => $best->origin,
            'destination' => $best->destination,
            'departureTime' => $best->departureTime,
            'arrivalTime' => $best->arrivalTime,
            'stops' => $best->stops,
            'price' => $best->price,
            'currency' => $best->currency,
            'durationMins' => $this->calcDuration($best),
            // সব provider-এর দাম দেখাও transparency-র জন্য
            'pricesBySource' => array_map(fn($d) => [
                'source' => $d->source,
                'price' => $d->price,
            ], $duplicates),
        ];
    }

    private function calcDuration(NormalizedFlight $f): int
    {
        $dep = strtotime($f->departureTime);
        $arr = strtotime($f->arrivalTime);
        return (int)(($arr - $dep) / 60); // minutes
    }
}
