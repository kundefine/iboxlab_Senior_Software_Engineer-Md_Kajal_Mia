<?php

namespace App\Contacts\FlightDataParser;


interface FlightDataParserInterface {
    public function format(array $providerFetchData): array;
}
