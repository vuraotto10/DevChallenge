<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * List a client's bookings.
     *
     * GET /api/bookings?email=client@example.com
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bookings = Booking::query()
            ->where('email', $request->query('email'))
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'name' => $booking->name,
                'email' => $booking->email,
                'date' => $booking->date->toDateString(),
                'hour' => $booking->hour,
                'scheduled_at' => $booking->scheduled_at->toIso8601String(),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }
}
