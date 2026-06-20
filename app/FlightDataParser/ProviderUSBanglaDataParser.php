<?php

namespace App\FlightDataParser;


use App\DTOs\NormalizedFlight;
use App\FlightDataParser\Contracts\FlightDataParserInterface;

class ProviderUSBanglaDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData) : array {
        return array_map(fn($f) => new NormalizedFlight(
            flightNumber:   $f['flight_no'],
            carrier:        $f['carrier'],
            origin:         $f['from'],
            destination:    $f['to'],
            departureTime:  $f['depart'],
            arrivalTime:    $f['arrive'],
            stops:          $f['stops'],
            price:          $f['fare_usd'],
            currency:       'USD',
            source:         'us-bangla',
        ), $providerFetchData['flights']);
    }
}
