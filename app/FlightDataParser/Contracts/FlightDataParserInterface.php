<?php

namespace App\FlightDataParser\Contracts;


interface FlightDataParserInterface {
    public function format(array $providerFetchData): array;
}
