<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'flight_id'                      => ['required', 'string'],
            'flight_number'                  => ['required', 'string'],
            'origin'                         => ['required', 'string', 'size:3'],
            'destination'                    => ['required', 'string', 'size:3'],
            'departure_time'                 => ['required', 'string'],
            'price'                          => ['required', 'numeric', 'min:0'],
            'currency'                       => ['sometimes', 'string', 'size:3'],

            'passengers'                     => ['required', 'array', 'min:1'],
            'passengers.*.first_name'        => ['required', 'string', 'max:100'],
            'passengers.*.last_name'         => ['required', 'string', 'max:100'],
            'passengers.*.date_of_birth'     => ['required', 'date_format:Y-m-d'],
            'passengers.*.passport_number'   => ['required', 'string', 'max:20'],
            'passengers.*.nationality'       => ['required', 'string', 'size:2'],  // ISO 3166-1 alpha-2
        ];
    }
}
