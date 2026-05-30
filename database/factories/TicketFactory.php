<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'requester_email' => fake()->safeEmail(),
            'requester_name' => fake()->name(),
            'subject' => fake()->sentence(4),
            'status' => 'new',
            'priority' => 'medium',
            'source' => 'manual',
            'last_activity_at' => now(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }
}
