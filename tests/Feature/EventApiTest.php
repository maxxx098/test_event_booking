<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test anyone can view events list
     */
    public function test_anyone_can_view_events_list(): void
    {
        Event::factory()->count(3)->create();

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'description', 'date', 'location', 'created_by'],
                ],
                'meta',
            ]);
    }

    /**
     * Test events pagination works
     */
    public function test_events_pagination_works(): void
    {
        Event::factory()->count(20)->create();

        $response = $this->getJson('/api/events?per_page=5');

        $response->assertStatus(200)
            ->assertJsonPath('meta.per_page', 5)
            ->assertJsonPath('meta.total', 20);
    }

    /**
     * Test search functionality
     */
    public function test_can_search_events_by_title(): void
    {
        Event::factory()->create(['title' => 'Laravel Conference']);
        Event::factory()->create(['title' => 'PHP Meetup']);
        Event::factory()->create(['title' => 'JavaScript Workshop']);

        $response = $this->getJson('/api/events?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel Conference');
    }

    /**
     * Test filter by location
     */
    public function test_can_filter_events_by_location(): void
    {
        Event::factory()->create(['location' => 'New York']);
        Event::factory()->create(['location' => 'Los Angeles']);
        Event::factory()->create(['location' => 'New York']);

        $response = $this->getJson('/api/events?location=New York');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test filter by date
     */
    public function test_can_filter_events_by_date(): void
    {
        Event::factory()->create(['date' => '2025-12-01']);
        Event::factory()->create(['date' => '2025-12-15']);
        Event::factory()->create(['date' => '2026-01-01']);

        $response = $this->getJson('/api/events?date_from=2025-12-01&date_to=2025-12-31');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test can view single event with tickets
     */
    public function test_can_view_single_event_with_tickets(): void
    {
        $event = Event::factory()->hasTickets(3)->create();

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'tickets' => [
                        '*' => ['id', 'type', 'price', 'quantity'],
                    ],
                ],
            ]);
    }

    /**
     * Test organizer can create event
     */
    public function test_organizer_can_create_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $token = $organizer->createToken('test')->plainTextToken;

        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'location' => 'Test Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Event');

        $this->assertDatabaseHas('events', [
            'title' => 'Test Event',
            'created_by' => $organizer->id,
        ]);
    }

    /**
     * Test customer cannot create event
     */
    public function test_customer_cannot_create_event(): void
    {
        $customer = User::factory()->customer()->create();
        $token = $customer->createToken('test')->plainTextToken;

        $eventData = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'location' => 'Test Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/events', $eventData);

        $response->assertStatus(403);
    }

    /**
     * Test organizer can update their own event
     */
    public function test_organizer_can_update_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    /**
     * Test organizer cannot update other's event
     */
    public function test_organizer_cannot_update_others_event(): void
    {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer1->id]);
        $token = $organizer2->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/events/{$event->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test admin can update any event
     */
    public function test_admin_can_update_any_event(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/events/{$event->id}", [
            'title' => 'Admin Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Admin Updated Title');
    }

    /**
     * Test organizer can delete their own event
     */
    public function test_organizer_can_delete_own_event(): void
    {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    }

    /**
     * Test organizer can view their events
     */
    public function test_organizer_can_view_their_events(): void
    {
        $organizer = User::factory()->organizer()->create();
        Event::factory()->count(3)->create(['created_by' => $organizer->id]);
        Event::factory()->count(2)->create(); // Other organizer's events
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/my-events');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test validation for event creation
     */
    public function test_event_creation_validation(): void
    {
        $organizer = User::factory()->organizer()->create();
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/events', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'date', 'location']);
    }

    /**
     * Test event date must be in future
     */
    public function test_event_date_must_be_in_future(): void
    {
        $organizer = User::factory()->organizer()->create();
        $token = $organizer->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/events', [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'date' => now()->subDays(1)->format('Y-m-d H:i:s'),
            'location' => 'Test Location',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }
}