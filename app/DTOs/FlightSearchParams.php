<?php

namespace App\DTOs;

readonly class FlightSearchParams
{
    public function __construct(
        public string  $from,
        public string  $to,
        public string  $date,
        public int     $passengers = 1,
        public string  $sortBy = 'price',     // price | duration | departure
        public ?float  $maxPrice = null,
        public ?int    $maxStops = null,
    ) {}
}
