<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\TicketEvent;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const FILTERS = [
        'all' => [],
        'created' => ['ticket.created_manual', 'ticket.created_from_mail'],
        'status' => ['ticket.status_changed', 'ticket.opened'],
        'assignment' => ['ticket.assigned_self'],
        'notes' => ['ticket.note_added'],
        'mail' => ['ticket.created_from_mail', 'ticket.mail_message_added'],
    ];

    public function __invoke(Request $request): View
    {
        $type = $request->string('type')->toString();
        $activeFilter = array_key_exists($type, self::FILTERS) ? $type : 'all';

        $events = TicketEvent::query()
            ->with(['actorUser', 'ticket'])
            ->when($activeFilter !== 'all', fn ($query) => $query->whereIn('type', self::FILTERS[$activeFilter]))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        /** @var LengthAwarePaginator $activity */
        $activity = $events->through(fn (TicketEvent $event): array => $this->formatEvent($event));

        return view('app.activity.index', [
            'activity' => $activity,
            'activeFilter' => $activeFilter,
            'filters' => $this->filters(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function filters(): array
    {
        return [
            'all' => 'Todo',
            'created' => 'Creacion',
            'status' => 'Estados',
            'assignment' => 'Asignacion',
            'notes' => 'Notas',
            'mail' => 'Correo',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatEvent(TicketEvent $event): array
    {
        $actor = $event->actorUser?->name ?: 'Sistema';
        $ticket = $event->ticket;

        return [
            'actor' => $actor,
            'initial' => mb_substr($actor, 0, 1),
            'action' => $this->actionText($event),
            'ticket_key' => $ticket?->public_key,
            'ticket_subject' => $ticket?->subject ?? 'Ticket no disponible',
            'ticket_url' => $ticket ? route('app.tickets.show', $ticket->public_key) : null,
            'created_at' => $event->created_at,
            'type' => $event->type,
        ];
    }

    private function actionText(TicketEvent $event): string
    {
        return match ($event->type) {
            'ticket.created_manual' => 'creo un nuevo ticket',
            'ticket.created_from_mail' => 'creo un ticket desde correo',
            'ticket.opened' => 'abrio',
            'ticket.status_changed' => 'actualizo el estado de',
            'ticket.assigned_self' => 'se asigno',
            'ticket.note_added' => 'agrego una nota interna en',
            'ticket.mail_message_added' => 'registro un correo en',
            default => 'registro actividad en',
        };
    }
}
