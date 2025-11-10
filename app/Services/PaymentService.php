<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Booking;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentService
{
    /**
     * Process a payment for a booking
     *
     * @param Booking $booking
     * @param array $paymentData
     * @return Payment
     * @throws Exception
     */
    public function processPayment(Booking $booking, array $paymentData = []): Payment
    {
        // Check if booking already has a payment
        if ($booking->payment()->exists()) {
            throw new Exception('Payment already exists for this booking');
        }

        // Check if booking is in a valid state for payment
        if ($booking->status !== 'pending') {
            throw new Exception('Booking must be in pending status to process payment');
        }

        // Calculate total amount
        $amount = $booking->ticket->price * $booking->quantity;

        // Simulate payment processing (70% success rate for testing)
        $isSuccess = $this->simulatePaymentGateway($paymentData);

        return DB::transaction(function () use ($booking, $amount, $isSuccess) {
            // Create payment record
            $payment = Payment::create([
                'booking_id' => $booking->id,
                'amount' => $amount,
                'status' => $isSuccess ? 'success' : 'failed',
            ]);

            // Update booking status based on payment result
            if ($isSuccess) {
                $booking->update(['status' => 'confirmed']);
                
                // Reduce ticket quantity
                $booking->ticket->decrement('quantity', $booking->quantity);
            } else {
                $booking->update(['status' => 'cancelled']);
            }

            return $payment;
        });
    }

    /**
     * Simulate payment gateway processing
     *
     * @param array $paymentData
     * @return bool
     */
    protected function simulatePaymentGateway(array $paymentData = []): bool
    {
        // Simulate processing delay
        usleep(500000); // 0.5 second delay

        // If test mode is enabled, use forced result
        if (isset($paymentData['test_mode']) && isset($paymentData['force_result'])) {
            return $paymentData['force_result'] === 'success';
        }

        // 70% success rate for realistic simulation
        return rand(1, 100) <= 70;
    }

    /**
     * Refund a payment
     *
     * @param Payment $payment
     * @return bool
     * @throws Exception
     */
    public function refundPayment(Payment $payment): bool
    {
        if ($payment->status !== 'success') {
            throw new Exception('Only successful payments can be refunded');
        }

        return DB::transaction(function () use ($payment) {
            // Update payment status
            $payment->update(['status' => 'refunded']);

            // Update booking status
            $payment->booking->update(['status' => 'cancelled']);

            // Restore ticket quantity
            $payment->booking->ticket->increment('quantity', $payment->booking->quantity);

            return true;
        });
    }

    /**
     * Get payment status
     *
     * @param int $paymentId
     * @return array
     */
    public function getPaymentStatus(int $paymentId): array
    {
        $payment = Payment::with(['booking.ticket.event', 'booking.user'])
            ->findOrFail($paymentId);

        return [
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'created_at' => $payment->created_at,
            'booking' => [
                'id' => $payment->booking->id,
                'quantity' => $payment->booking->quantity,
                'status' => $payment->booking->status,
                'ticket' => [
                    'type' => $payment->booking->ticket->type,
                    'event' => $payment->booking->ticket->event->title,
                ],
            ],
        ];
    }
}