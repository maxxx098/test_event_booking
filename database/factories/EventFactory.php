<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(5),
            'date' => fake()->dateTimeBetween('now', '+6 months'),
            'location' => fake()->city() . ', ' . fake()->country(),
            'created_by' => User::factory(),
        ];
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => fake()->dateTimeBetween('now', '+6 months'),
        ]);
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->addHours(fake()->numberBetween(1, 23)),
        ]);
    }
}