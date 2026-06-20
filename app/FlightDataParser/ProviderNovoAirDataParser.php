<?php

namespace App\FlightDataParser;

use App\Contacts\FlightDataParser\FlightDataParserInterface;

class ProviderNovoAirDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData): array
    {
        $formatted = [];

        foreach ($providerFetchData['results'] as $flight) {
            $formatted[] = [
                'flightNumber'  => $flight['code'],
                'carrier'       => $flight['iata'],
                'origin'        => $flight['route']['src'],
                'destination'   => $flight['route']['dst'],
                'departureTime' => date('Y-m-d H:i:s', $flight['times']['dep']),
                'arrivalTime'   => date('Y-m-d H:i:s', $flight['times']['arr']),
                'stops'         => $flight['layovers'],
                'price'         => $flight['total_price'],
                'currency'      => $flight['currency'],
                'source'        => 'NovoAir',
            ];
        }

        return $formatted;
    }
}
