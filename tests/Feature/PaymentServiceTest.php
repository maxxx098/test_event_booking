<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = new PaymentService();
        Notification::fake();
    }

    /** @test */
    public function it_can_process_successful_payment()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'price' => 100,
            'quantity' => 50,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 3,
            'status' => 'pending',
        ]);

        $payment = $this->paymentService->processPayment($booking, [
            'test_mode' => true,
            'force_result' => 'success',
        ]);

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals('success', $payment->status);
        $this->assertEquals(300, $payment->amount);
        $this->assertEquals($booking->id, $payment->booking_id);

        // Check booking updated
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);

        // Check ticket quantity reduced
        $ticket->refresh();
        $this->assertEquals(47, $ticket->quantity);
    }

    /** @test */
    public function it_can_process_failed_payment()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'price' => 50,
            'quantity' => 50,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        $payment = $this->paymentService->processPayment($booking, [
            'test_mode' => true,
            'force_result' => 'failed',
        ]);

        $this->assertEquals('failed', $payment->status);
        $this->assertEquals(100, $payment->amount);

        // Check booking cancelled
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);

        // Check ticket quantity unchanged
        $ticket->refresh();
        $this->assertEquals(50, $ticket->quantity);
    }

    /** @test */
    public function it_throws_exception_if_payment_already_exists()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment already exists for this booking');

        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
        ]);

        Payment::factory()->create(['booking_id' => $booking->id]);

        $this->paymentService->processPayment($booking);
    }

    /** @test */
    public function it_throws_exception_if_booking_not_pending()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking must be in pending status to process payment');

        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'status' => 'confirmed',
        ]);

        $this->paymentService->processPayment($booking);
    }

    /** @test */
    public function it_calculates_correct_payment_amount()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'price' => 75.50,
            'quantity' => 100,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 4,
            'status' => 'pending',
        ]);

        $payment = $this->paymentService->processPayment($booking, [
            'test_mode' => true,
            'force_result' => 'success',
        ]);

        $this->assertEquals(302.00, $payment->amount); // 75.50 * 4
    }

    /** @test */
    public function it_can_refund_successful_payment()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'price' => 100,
            'quantity' => 45,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 5,
            'status' => 'confirmed',
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 500,
            'status' => 'success',
        ]);

        $result = $this->paymentService->refundPayment($payment);

        $this->assertTrue($result);

        // Check payment refunded
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);

        // Check booking cancelled
        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);

        // Check ticket quantity restored
        $ticket->refresh();
        $this->assertEquals(50, $ticket->quantity);
    }

    /** @test */
    public function it_throws_exception_when_refunding_non_successful_payment()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only successful payments can be refunded');

        $payment = Payment::factory()->create(['status' => 'failed']);

        $this->paymentService->refundPayment($payment);
    }

    /** @test */
    public function it_can_get_payment_status()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['title' => 'Test Event']);
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'type' => 'VIP',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
        ]);

        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'amount' => 200,
            'status' => 'success',
        ]);

        $status = $this->paymentService->getPaymentStatus($payment->id);

        $this->assertEquals($payment->id, $status['payment_id']);
        $this->assertEquals(200, $status['amount']);
        $this->assertEquals('success', $status['status']);
        $this->assertEquals('VIP', $status['booking']['ticket']['type']);
        $this->assertEquals('Test Event', $status['booking']['ticket']['event']);
    }

    /** @test */
    public function payment_processing_uses_database_transaction()
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create([
            'event_id' => $event->id,
            'quantity' => 50,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'ticket_id' => $ticket->id,
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $initialTicketQuantity = $ticket->quantity;

        // Process payment
        $this->paymentService->processPayment($booking, [
            'test_mode' => true,
            'force_result' => 'success',
        ]);

        // Verify all changes are committed
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id]);
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'quantity' => $initialTicketQuantity - 5]);
    }
}