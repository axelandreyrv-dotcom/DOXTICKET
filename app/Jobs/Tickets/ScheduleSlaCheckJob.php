<?php

namespace App\Jobs\Tickets;

use App\Models\Ticket;
use App\Models\TicketEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScheduleSlaCheckJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Ticket::withoutTenant()
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->orderBy('id')
            ->each(function (Ticket $ticket): void {
                $alreadyRecorded = TicketEvent::query()
                    ->where('ticket_id', $ticket->id)
                    ->where('type', 'ticket.sla_breached')
                    ->exists();

                if ($alreadyRecorded) {
                    return;
                }

                TicketEvent::query()->create([
                    'company_id' => $ticket->company_id,
                    'ticket_id' => $ticket->id,
                    'type' => 'ticket.sla_breached',
                    'payload' => [
                        'sla_due_at' => $ticket->sla_due_at?->toISOString(),
                        'priority' => $ticket->priority,
                    ],
                ]);
            });
    }
}
