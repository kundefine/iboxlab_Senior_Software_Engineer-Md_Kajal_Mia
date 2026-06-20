<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    /**
     * POST /api/bookings
     *
     * Body (JSON):
     * {
     *   "flight_id":     "QUExMDF8MjAyNi0wNy0wMVQwODowMDowMA",
     *   "flight_number": "AA101",
     *   "origin":        "DAC",
     *   "destination":   "DXB",
     *   "departure_time":"2026-07-01T08:00:00",
     *   "price":         320.00,
     *   "currency":      "USD",
     *   "passengers": [
     *     {
     *       "first_name":      "John",
     *       "last_name":       "Doe",
     *       "date_of_birth":   "1990-05-15",
     *       "passport_number": "AB1234567",
     *       "nationality":     "BD"
     *     }
     *   ]
     * }
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create($request->validated());

        return response()->json([
            'booking'   => $booking,
            'message'   => 'Booking confirmed successfully.',
        ], 201);
    }

    /**
     * GET /api/bookings/{reference}
     *
     * Returns the full booking for a given reference code (e.g. BK-20260701-A3F9K2).
     */
    public function show(string $reference): JsonResponse
    {
        $booking = $this->bookingService->findByReference($reference);

        return response()->json(['booking' => $booking]);
    }
}
