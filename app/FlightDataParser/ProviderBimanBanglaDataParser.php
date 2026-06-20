<?php

namespace App\FlightDataParser;

use App\Contacts\FlightDataParser\FlightDataParserInterface;

class ProviderBimanBanglaDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData): array
    {
        $formatted = [];

        foreach ($providerFetchData['data'] as $flight) {
            $formatted[] = [
                'flightNumber'  => $flight['number'],
                'carrier'       => $flight['airline_code'],
                'origin'        => $flight['origin'],
                'destination'   => $flight['destination'],
                'departureTime' => $flight['departure_time'],
                'arrivalTime'   => $flight['arrival_time'],
                'stops'         => $flight['segments'],
                'price'         => $flight['price']['amount'],
                'currency'      => $flight['price']['currency'],
                'source'        => 'BimanBangla',
            ];
        }

        return $formatted;
    }

}
