<?php

namespace App\FlightDataParser;

use App\Contacts\FlightDataParser\FlightDataParserInterface;

class ProviderUSBanglaDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData) : array {

        $formated = [];

        foreach ($providerFetchData['flights'] as $flight) {
            $formated[] = [
                'flightNumber'  => $flight['flight_no'],
                'carrier'       => $flight['carrier'],
                'origin'        => $flight['from'],
                'destination'   => $flight['to'],
                'departureTime' => $flight['depart'],
                'arrivalTime'   => $flight['arrive'],
                'stops'         => $flight['stops'],
                'price'         => $flight['fare_usd'],
                'currency'      => 'USD',
                'source'        => 'UsBangla',
            ];
        }

        return $formated;
    }
}
