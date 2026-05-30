<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
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
}
