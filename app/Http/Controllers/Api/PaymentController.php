<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Process payment for a booking
     */
    public function processPayment(Request $request, $id): JsonResponse
    {
        try {
            $booking = Booking::with(['ticket.event', 'user'])
                ->where('id', $id)
                ->where('user_id', $request->user()->id)
                ->firstOrFail();

            // Optional test mode parameters
            $paymentData = $request->only(['test_mode', 'force_result']);

            // Process payment using service
            $payment = $this->paymentService->processPayment($booking, $paymentData);

            // Reload booking with updated relationships
            $booking->refresh();
            $booking->load(['ticket.event', 'payment']);

            if ($payment->status === 'success') {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'payment' => $payment,
                        'booking' => $booking,
                    ],
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment failed. Please try again.',
                    'data' => [
                        'payment' => $payment,
                        'booking' => $booking,
                    ],
                ], 402); // 402 Payment Required
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified payment
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $payment = Payment::with(['booking.ticket.event', 'booking.user'])
                ->findOrFail($id);

            // Check authorization
            if ($payment->booking->user_id !== $request->user()->id 
                && !in_array($request->user()->role, ['admin', 'organizer'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this payment',
                ], 403);
            }

            $paymentStatus = $this->paymentService->getPaymentStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Payment details retrieved successfully',
                'data' => $paymentStatus,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
            ], 404);
        }
    }
}