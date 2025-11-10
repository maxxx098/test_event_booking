<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Booking;

class PreventDoubleBooking
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only check for POST requests on booking endpoints
        if ($request->isMethod('post') && $request->route('id')) {
            $userId = $request->user()->id;
            $ticketId = $request->route('id');

            // Check if user already has a pending or confirmed booking for this ticket
            $existingBooking = Booking::where('user_id', $userId)
                ->where('ticket_id', $ticketId)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($existingBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active booking for this ticket.',
                    'error' => 'DUPLICATE_BOOKING'
                ], 409); // 409 Conflict
            }
        }

        return $next($request);
    }
}