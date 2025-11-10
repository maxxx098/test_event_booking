<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin User
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Organizer Users
        $organizer1 = User::factory()->organizer()->create([
            'name' => 'John Organizer',
            'email' => 'organizer@example.com',
            'password' => bcrypt('password'),
        ]);

        $organizer2 = User::factory()->organizer()->create([
            'name' => 'Jane Organizer',
            'email' => 'organizer2@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create Customer Users
        $customers = User::factory()->customer()->count(10)->create();

        // Create Events for Organizer 1
        $events1 = Event::factory()->count(5)->create([
            'created_by' => $organizer1->id,
        ]);

        // Create Events for Organizer 2
        $events2 = Event::factory()->count(5)->create([
            'created_by' => $organizer2->id,
        ]);

        $allEvents = $events1->concat($events2);

        // Create Tickets for each Event
        foreach ($allEvents as $event) {
            // VIP Tickets
            $vipTicket = Ticket::factory()->vip()->create([
                'event_id' => $event->id,
            ]);

            // Standard Tickets
            $standardTicket = Ticket::factory()->standard()->create([
                'event_id' => $event->id,
            ]);

            // Economy Tickets
            $economyTicket = Ticket::factory()->economy()->create([
                'event_id' => $event->id,
            ]);

            // Create some bookings for each ticket type
            $tickets = [$vipTicket, $standardTicket, $economyTicket];

            foreach ($tickets as $ticket) {
                // Random number of bookings per ticket (0-3)
                $bookingCount = rand(0, 3);

                for ($i = 0; $i < $bookingCount; $i++) {
                    $customer = $customers->random();
                    $quantity = rand(1, 5);

                    $booking = Booking::factory()->create([
                        'user_id' => $customer->id,
                        'ticket_id' => $ticket->id,
                        'quantity' => $quantity,
                        'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled']),
                    ]);

                    // Create payment for confirmed bookings
                    if ($booking->status === 'confirmed') {
                        Payment::factory()->success()->create([
                            'booking_id' => $booking->id,
                            'amount' => $ticket->price * $quantity,
                        ]);
                    } elseif ($booking->status === 'pending') {
                        // Some pending bookings might have pending payments
                        if (rand(0, 1)) {
                            Payment::factory()->create([
                                'booking_id' => $booking->id,
                                'amount' => $ticket->price * $quantity,
                                'status' => 'success',
                            ]);
                        }
                    } elseif ($booking->status === 'cancelled') {
                        // Cancelled bookings have refunded payments
                        Payment::factory()->refunded()->create([
                            'booking_id' => $booking->id,
                            'amount' => $ticket->price * $quantity,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin: admin@example.com / password');
        $this->command->info('Organizer: organizer@example.com / password');
        $this->command->info('Organizer 2: organizer2@example.com / password');
    }
}