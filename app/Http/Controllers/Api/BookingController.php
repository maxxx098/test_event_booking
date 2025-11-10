<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Display a listing of the user's bookings
     */
    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::with(['ticket.event', 'payment'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Bookings retrieved successfully',
            'data' => $bookings,
        ], 200);
    }

    /**
     * Store a newly created booking
     */
    public function store(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::with('event')->findOrFail($id);

        // Check if ticket has enough quantity
        if ($ticket->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough tickets available',
                'available_quantity' => $ticket->quantity,
            ], 400);
        }

        // Check if event date hasn't passed
        if ($ticket->event->date < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot book tickets for past events',
            ], 400);
        }

        // Create booking
        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'ticket_id' => $ticket->id,
            'quantity' => $request->quantity,
            'status' => 'pending',
        ]);

        $booking->load(['ticket.event', 'user']);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully. Please proceed to payment.',
            'data' => $booking,
        ], 201);
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $booking = Booking::with(['payment', 'ticket'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Check if booking can be cancelled
        if ($booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Booking is already cancelled',
            ], 400);
        }

        // If payment exists and was successful, handle refund
        if ($booking->payment && $booking->payment->status === 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel a paid booking. Please request a refund.',
            ], 400);
        }

        // Cancel the booking
        $booking->update(['status' => 'cancelled']);

        // Restore ticket quantity if it was pending
        if ($booking->status === 'pending') {
            $booking->ticket->increment('quantity', $booking->quantity);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ], 200);
    }
}