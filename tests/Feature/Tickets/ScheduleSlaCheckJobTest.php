<?php

namespace Tests\Feature\Tickets;

use App\Jobs\Tickets\ScheduleSlaCheckJob;
use App\Models\Company;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSlaCheckJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_one_internal_event_for_each_active_overdue_ticket(): void
    {
        $company = Company::factory()->create();
        $ticket = Ticket::factory()->for($company)->create([
            'status' => 'open',
            'sla_due_at' => now()->subMinute(),
        ]);

        (new ScheduleSlaCheckJob)->handle();
        (new ScheduleSlaCheckJob)->handle();

        $this->assertDatabaseCount('ticket_events', 1);
        $this->assertDatabaseHas('ticket_events', [
            'company_id' => $company->id,
            'ticket_id' => $ticket->id,
            'type' => 'ticket.sla_breached',
        ]);
    }

    public function test_it_ignores_resolved_closed_and_not_yet_due_tickets(): void
    {
        $company = Company::factory()->create();

        Ticket::factory()->for($company)->create([
            'status' => 'resolved',
            'sla_due_at' => now()->subMinute(),
        ]);

        Ticket::factory()->for($company)->create([
            'status' => 'open',
            'sla_due_at' => now()->addMinute(),
        ]);

        (new ScheduleSlaCheckJob)->handle();

        $this->assertDatabaseCount('ticket_events', 0);
    }
}
