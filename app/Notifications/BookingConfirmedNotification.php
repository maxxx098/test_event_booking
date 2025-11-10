<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $event = $this->booking->ticket->event;
        $amount = $this->booking->ticket->price * $this->booking->quantity;

        return (new MailMessage)
            ->subject('Booking Confirmed - ' . $event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been confirmed successfully.')
            ->line('**Event Details:**')
            ->line('Event: ' . $event->title)
            ->line('Date: ' . $event->date->format('F d, Y'))
            ->line('Location: ' . $event->location)
            ->line('Ticket Type: ' . $this->booking->ticket->type)
            ->line('Quantity: ' . $this->booking->quantity)
            ->line('Total Amount: $' . number_format($amount, 2))
            ->action('View Booking Details', url('/api/bookings/' . $this->booking->id))
            ->line('Thank you for your booking!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'event_title' => $this->booking->ticket->event->title,
            'ticket_type' => $this->booking->ticket->type,
            'quantity' => $this->booking->quantity,
            'amount' => $this->booking->ticket->price * $this->booking->quantity,
            'status' => $this->booking->status,
            'message' => 'Your booking for ' . $this->booking->ticket->event->title . ' has been confirmed.',
        ];
    }
}