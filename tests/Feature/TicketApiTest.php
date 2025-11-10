<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test anyone can view tickets for an event
     */
    public function test_anyone_can_view_event_tickets(): void
    {
        $event = Event::factory()->hasTickets(3)->create();

        $response = $this->getJson("/api/events/{$event->id}/tickets");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'type', 'price', 'quantity', 'event_id'],
                ],
            ]);
    }

    /**
     * Test can view single ticket
     */
    public function test_can_view_single_ticket(): void
    {
        $event = Event::factory()->create();
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        $response = $this->getJson("/api/events/{$event->id}/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ticket->id);
    }

    /**
     * Test organizer can create ticket for their event
     */
    public function test_organizer_can_create_ticket_for_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $ticketData = [
            'type' => 'VIP',
            'price' => 100.00,
            'quantity' => 50,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'VIP')
           ->assertJsonPath('data.price', '100.00');


        $this->assertDatabaseHas('tickets', [
            'event_id' => $event->id,
            'type' => 'VIP',
        ]);
    }

    /**
     * Test organizer cannot create ticket for other's event
     */
    public function test_organizer_cannot_create_ticket_for_others_event(): void
    {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer1->id]);
        $token = $organizer2->createToken('test')->plainTextToken;

        $ticketData = [
            'type' => 'VIP',
            'price' => 100.00,
            'quantity' => 50,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(403);
    }

    /**
     * Test customer cannot create tickets
     */
    public function test_customer_cannot_create_tickets(): void
    {
        $customer = User::factory()->customer()->create();
        $event = Event::factory()->create();
        $token = $customer->createToken('test')->plainTextToken;

        $ticketData = [
            'type' => 'VIP',
            'price' => 100.00,
            'quantity' => 50,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(403);
    }

    /**
     * Test organizer can update ticket for their event
     */
    public function test_organizer_can_update_ticket_for_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/events/{$event->id}/tickets/{$ticket->id}", [
            'price' => 150.00,
        ]);

    $response->assertJsonPath('data.price', '150.00');

    }

    /**
     * Test organizer can delete ticket for their event
     */
    public function test_organizer_can_delete_ticket_for_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/events/{$event->id}/tickets/{$ticket->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    /**
     * Test admin can manage any tickets
     */
    public function test_admin_can_manage_any_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $admin->createToken('test')->plainTextToken;

        // Create ticket
        $ticketData = [
            'type' => 'Standard',
            'price' => 50.00,
            'quantity' => 100,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(201);
    }

    /**
     * Test ticket validation
     */
    public function test_ticket_creation_validation(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'price', 'quantity']);
    }

    /**
     * Test ticket price must be non-negative
     */
    public function test_ticket_price_must_be_non_negative(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", [
            'type' => 'VIP',
            'price' => -10,
            'quantity' => 50,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test ticket quantity must be positive
     */
    public function test_ticket_quantity_must_be_positive(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/events/{$event->id}/tickets", [
            'type' => 'VIP',
            'price' => 100,
            'quantity' => 0,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test returns 404 for non-existent event
     */
    public function test_returns_404_for_non_existent_event(): void
    {
        $response = $this->getJson('/api/events/99999/tickets');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /**
     * Test returns 404 for non-existent ticket
     */
    public function test_returns_404_for_non_existent_ticket(): void
    {
        $event = Event::factory()->create();

        $response = $this->getJson("/api/events/{$event->id}/tickets/99999");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}