<?php

namespace App\FlightDataParser;



use App\DTOs\NormalizedFlight;
use App\FlightDataParser\Contracts\FlightDataParserInterface;

class ProviderBimanBanglaDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData): array
    {
        return array_map(function ($f) {
            // "2026-07-01 09:15" → "2026-07-01T09:15:00"
            $dep = str_replace(' ', 'T', $f['departure_time']) . ':00';
            $arr = str_replace(' ', 'T', $f['arrival_time']) . ':00';

            return new NormalizedFlight(
                flightNumber:   $f['number'],
                carrier:        $f['airline_code'],
                origin:         $f['origin'],
                destination:    $f['destination'],
                departureTime:  $dep,
                arrivalTime:    $arr,
                stops:          $f['segments'],
                price:          $f['price']['amount'],
                currency:       $f['price']['currency'],
                source:         'biman bangla',
            );
        }, $providerFetchData['data']);
    }

}
