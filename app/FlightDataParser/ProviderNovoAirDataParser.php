<?php

namespace App\FlightDataParser;


use App\DTOs\NormalizedFlight;
use App\FlightDataParser\Contracts\FlightDataParserInterface;
use Carbon\Carbon;

class ProviderNovoAirDataParser implements FlightDataParserInterface
{

    public function format(array $providerFetchData): array
    {
        return array_map(function ($f) {
            // Unix timestamp → ISO string
            $dep = Carbon::createFromTimestamp($f['times']['dep'])->toIso8601String();
            $arr = Carbon::createFromTimestamp($f['times']['arr'])->toIso8601String();

            return new NormalizedFlight(
                flightNumber:   $f['code'],
                carrier:        $f['iata'],
                origin:         $f['route']['src'],
                destination:    $f['route']['dst'],
                departureTime:  $dep,
                arrivalTime:    $arr,
                stops:          $f['layovers'],
                price:          $f['total_price'],
                currency:       $f['currency'],
                source:         'novo-air',
            );
        }, $providerFetchData['results']);
    }
}
