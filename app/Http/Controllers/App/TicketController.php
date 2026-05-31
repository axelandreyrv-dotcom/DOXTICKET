<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketMessageRequest;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketStatusRequest;
use App\Models\Category;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with(['assignedToMembership.user', 'category'])
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('last_activity_at')
            ->paginate(20);

        return view('app.tickets.index', [
            'tickets' => $tickets,
            'status' => $request->string('status')->toString(),
            'activeMembership' => app(TenantContext::class)->membership(),
        ]);
    }

    public function create(): View
    {
        return view('app.tickets.create', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'memberships' => Membership::query()
                ->with('user')
                ->where('company_id', app(TenantContext::class)->company()?->id)
                ->where('status', 'active')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $tenant = app(TenantContext::class);
        $membership = $tenant->membership();
        $data = $request->validated();

        $ticket = DB::transaction(function () use ($data, $membership): Ticket {
            $ticket = Ticket::query()->create([
                'category_id' => $data['category_id'] ?? null,
                'assigned_to_membership_id' => $data['assigned_to_membership_id'] ?? null,
                'created_by_membership_id' => $membership?->id,
                'requester_email' => $data['requester_email'] ?? null,
                'requester_name' => $data['requester_name'] ?? null,
                'subject' => $data['subject'] ?? 'Sin Asunto',
                'status' => 'new',
                'priority' => $data['priority'],
                'source' => 'manual',
            ]);

            TicketMessage::query()->create([
                'ticket_id' => $ticket->id,
                'author_user_id' => $membership?->user_id,
                'author_membership_id' => $membership?->id,
                'visibility' => 'internal',
                'direction' => 'internal',
                'body_text' => $data['body_text'],
            ]);

            TicketEvent::query()->create([
                'ticket_id' => $ticket->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.created_manual',
                'payload' => ['source' => 'manual'],
            ]);

            return $ticket;
        });

        return redirect('/app/tickets')->with('status', 'ticket-created-'.$ticket->public_key);
    }

    public function show(string $ticket): View
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $this->markAsOpened($ticketModel);

        $ticketModel->load([
            'assignedToMembership.user',
            'category',
            'messages.authorUser',
            'events.actorUser',
        ]);

        return view('app.tickets.show', [
            'ticket' => $ticketModel,
            'statusOptions' => $this->statusOptions(),
            'activeMembership' => app(TenantContext::class)->membership(),
        ]);
    }

    public function assignSelf(string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $previousMembershipId = $ticketModel->assigned_to_membership_id;

        DB::transaction(function () use ($ticketModel, $membership, $previousMembershipId): void {
            $ticketModel->forceFill([
                'assigned_to_membership_id' => $membership?->id,
                'last_activity_at' => now(),
            ])->save();

            TicketEvent::query()->create([
                'ticket_id' => $ticketModel->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.assigned_self',
                'payload' => [
                    'from_membership_id' => $previousMembershipId,
                    'to_membership_id' => $membership?->id,
                ],
            ]);
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'ticket-assigned');
    }

    public function storeMessage(StoreTicketMessageRequest $request, string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $data = $request->validated();

        DB::transaction(function () use ($ticketModel, $membership, $data): void {
            TicketMessage::query()->create([
                'ticket_id' => $ticketModel->id,
                'author_user_id' => $membership?->user_id,
                'author_membership_id' => $membership?->id,
                'visibility' => 'internal',
                'direction' => 'internal',
                'body_text' => $data['body_text'],
            ]);

            $ticketModel->forceFill(['last_activity_at' => now()])->save();

            TicketEvent::query()->create([
                'ticket_id' => $ticketModel->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.note_added',
                'payload' => ['visibility' => 'internal'],
            ]);
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'note-added');
    }

    public function updateStatus(UpdateTicketStatusRequest $request, string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $status = $request->validated('status');
        $previousStatus = $ticketModel->status;

        if ($status === 'closed' && $previousStatus !== 'resolved') {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['status' => 'Para cerrar un ticket primero debe estar resuelto.']);
        }

        DB::transaction(function () use ($ticketModel, $membership, $status, $previousStatus): void {
            $changes = [
                'status' => $status,
                'last_activity_at' => now(),
            ];

            if ($status === 'resolved' && $ticketModel->resolved_at === null) {
                $changes['resolved_at'] = now();
            }

            if ($status === 'closed' && $ticketModel->closed_at === null) {
                $changes['closed_at'] = now();
            }

            if ($status === 'reopened') {
                $changes['closed_at'] = null;
            }

            $ticketModel->forceFill($changes)->save();

            TicketEvent::query()->create([
                'ticket_id' => $ticketModel->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.status_changed',
                'payload' => [
                    'from' => $previousStatus,
                    'to' => $status,
                ],
            ]);
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'status-updated');
    }

    private function findTenantTicket(string $ticket): Ticket
    {
        return Ticket::query()
            ->where(function ($query) use ($ticket): void {
                $query->where('public_key', $ticket);

                if (ctype_digit($ticket)) {
                    $query->orWhere('id', (int) $ticket);
                }
            })
            ->firstOrFail();
    }

    private function markAsOpened(Ticket $ticket): void
    {
        if ($ticket->status !== 'new' || $ticket->first_opened_at !== null) {
            return;
        }

        $membership = app(TenantContext::class)->membership();

        DB::transaction(function () use ($ticket, $membership): void {
            $ticket->forceFill([
                'status' => 'open',
                'first_opened_at' => now(),
                'last_activity_at' => now(),
            ])->save();

            TicketEvent::query()->create([
                'ticket_id' => $ticket->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.opened',
                'payload' => ['from' => 'new', 'to' => 'open'],
            ]);
        });
    }

    /**
     * @return array<string, string>
     */
    private function statusOptions(): array
    {
        return [
            'open' => 'Abierto',
            'in_progress' => 'En Progreso',
            'waiting_customer' => 'Espera Cliente',
            'waiting_internal' => 'Espera Interna',
            'resolved' => 'Resuelto',
            'closed' => 'Cerrado',
            'reopened' => 'Reabierto',
        ];
    }
}
