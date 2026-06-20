<?php

namespace App\Http\Controllers;

use App\DTOs\FlightSearchParams;
use App\Http\Requests\FlightSearchRequest;
use App\Services\FlightAggregatorService;

class FlightController extends Controller
{
    public function __construct(
        private FlightAggregatorService $aggregator
    ) {}
    public function search(FlightSearchRequest $request): \Illuminate\Http\JsonResponse
    {
        $params = new FlightSearchParams(
            from:       $request->from,
            to:         $request->to,
            date:       $request->date,
            passengers: (int) $request->get('passengers', 1),
            sortBy:     $request->get('sort_by', 'price'),
            maxPrice:   $request->filled('max_price') ? (float) $request->max_price : null,
            maxStops:   $request->filled('max_stops') ? (int) $request->max_stops : null,
        );

        $result = $this->aggregator->search($params);

        return response()->json($result);
    }
}
