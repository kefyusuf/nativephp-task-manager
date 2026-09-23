<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional(0.5)->date(),
        ];
    }

    /**
     * Live-feed task (filtered in the list by the 📡 title prefix).
     */
    public function feed(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => '📡 BTC #1: $100.00',
            'body' => 'Live feed task',
            'status' => TaskStatus::Pending,
            'priority' => TaskPriority::Low,
            'due_date' => null,
        ]);
    }
}
