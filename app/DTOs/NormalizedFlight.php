<?php

namespace App\DTOs;

readonly class NormalizedFlight
{
    public function __construct(
        public string $flightNumber,
        public string $carrier,
        public string $origin,
        public string $destination,
        public string $departureTime,  // ISO: 2026-07-01T08:00:00
        public string $arrivalTime,
        public int    $stops,
        public float  $price,
        public string $currency,
        public string $source,         // "providerA"
    ) {}

    // Unique key for deduplication
    public function deduplicationKey(): string
    {
        return $this->flightNumber . '_' . $this->departureTime;
    }

    // Stable ID for booking reference
    public function stableId(): string
    {
        return base64_encode($this->flightNumber . '|' . $this->departureTime);
    }
}
