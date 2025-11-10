<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['VIP', 'Standard', 'Economy', 'Premium', 'General']),
            'price' => fake()->randomFloat(2, 10, 500),
            'quantity' => fake()->numberBetween(50, 500),
            'event_id' => Event::factory(),
        ];
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'VIP',
            'price' => fake()->randomFloat(2, 200, 500),
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Standard',
            'price' => fake()->randomFloat(2, 50, 150),
        ]);
    }

    public function economy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Economy',
            'price' => fake()->randomFloat(2, 10, 50),
        ]);
    }

    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}