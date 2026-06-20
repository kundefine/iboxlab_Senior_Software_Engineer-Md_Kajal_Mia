<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Str;

class BookingService
{
    public function create(array $data): Booking
    {
        $reference = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

        return Booking::create([
            'reference'           => $reference,
            'flight_id'           => $data['flight_id'],
            'flight_number'       => $data['flight_number'],
            'origin'              => $data['origin'],
            'destination'         => $data['destination'],
            'departure_time'      => $data['departure_time'],
            'price_per_passenger' => $data['price'],
            'total_price'         => $data['price'] * count($data['passengers']),
            'passengers'          => $data['passengers'],
            'status'              => 'confirmed',
        ]);
    }

    public function findByReference(string $reference): Booking
    {
        return Booking::where('reference', $reference)->firstOrFail();
    }
}
