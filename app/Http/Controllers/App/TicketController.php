<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\MergeTicketRequest;
use App\Http\Requests\Tickets\StoreTicketAttachmentRequest;
use App\Http\Requests\Tickets\StoreTicketMessageRequest;
use App\Http\Requests\Tickets\StoreTicketReplyRequest;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\UpdateTicketPropertiesRequest;
use App\Http\Requests\Tickets\UpdateTicketStatusRequest;
use App\Models\Category;
use App\Models\MailAccount;
use App\Models\Membership;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketMessage;
use App\Services\Mail\TicketReplySender;
use App\Services\Tickets\TicketAttachmentService;
use App\Support\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketReplySender $replySender,
        private readonly TicketAttachmentService $attachmentService,
    ) {}

    public function index(Request $request): View
    {
        $tickets = Ticket::query()
            ->with(['assignedToMembership.user', 'category'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = mb_strtolower(trim($request->string('q')->toString()));
                $like = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($like): void {
                    $query
                        ->whereRaw('LOWER(public_key) LIKE ? ESCAPE \'\\\'', [$like])
                        ->orWhereRaw('LOWER(subject) LIKE ? ESCAPE \'\\\'', [$like])
                        ->orWhereRaw('LOWER(requester_email) LIKE ? ESCAPE \'\\\'', [$like]);
                });
            })
            ->when(
                $request->string('status')->isNotEmpty(),
                fn ($query) => $query->where('status', $request->string('status')),
                fn ($query) => $query->whereIn('status', Ticket::ACTIVE_STATUSES)
            )
            ->when($request->string('priority')->isNotEmpty(), fn ($query) => $query->where('priority', $request->string('priority')))
            ->when($request->string('type')->isNotEmpty(), fn ($query) => $query->where('ticket_type', $request->string('type')))
            ->when($request->string('source')->isNotEmpty(), fn ($query) => $query->where('source', $request->string('source')))
            ->when($request->string('sla')->toString() === 'overdue', fn ($query) => $query
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', now())
                ->whereIn('status', Ticket::ACTIVE_STATUSES))
            ->when($request->string('agent')->isNotEmpty(), function ($query) use ($request): void {
                $agent = $request->string('agent')->toString();

                if ($agent === 'me') {
                    $query->where('assigned_to_membership_id', app(TenantContext::class)->membership()?->id);

                    return;
                }

                if ($agent === 'unassigned') {
                    $query->whereNull('assigned_to_membership_id');

                    return;
                }

                if (ctype_digit($agent)) {
                    $query->where('assigned_to_membership_id', (int) $agent);
                }
            })
            ->orderByDesc('last_activity_at')
            ->paginate(20)
            ->withQueryString();

        return view('app.tickets.index', [
            'tickets' => $tickets,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'agent' => $request->string('agent')->toString(),
                'priority' => $request->string('priority')->toString(),
                'type' => $request->string('type')->toString(),
                'source' => $request->string('source')->toString(),
                'sla' => $request->string('sla')->toString(),
            ],
            'activeMembership' => app(TenantContext::class)->membership(),
            'memberships' => $this->activeMemberships(),
            'statusLabels' => Ticket::STATUS_LABELS,
            'priorityLabels' => Ticket::PRIORITY_LABELS,
            'typeLabels' => Ticket::TYPE_LABELS,
            'sourceLabels' => Ticket::SOURCE_LABELS,
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
                'ticket_type' => $data['ticket_type'],
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
            'mergedIntoTicket',
            'attachments',
            'messages.authorUser',
            'events.actorUser',
        ]);

        return view('app.tickets.show', [
            'ticket' => $ticketModel,
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => Ticket::PRIORITY_LABELS,
            'typeOptions' => Ticket::TYPE_LABELS,
            'sourceOptions' => Ticket::SOURCE_LABELS,
            'memberships' => $this->activeMemberships(),
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

    public function storeAttachment(StoreTicketAttachmentRequest $request, string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $file = $request->file('attachment');
        $originalName = $this->attachmentService->safeFilename($file->getClientOriginalName());
        $mimeType = $file->getMimeType() ?: 'application/octet-stream';
        $contents = file_get_contents($file->getRealPath()) ?: '';

        if ($this->attachmentService->isBlocked($originalName, $mimeType)) {
            $this->attachmentService->storeContent($ticketModel, $membership, null, $originalName, $mimeType, $contents);

            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['attachment' => 'Este tipo de archivo no se permite por seguridad.']);
        }

        DB::transaction(function () use ($ticketModel, $membership, $originalName, $mimeType, $contents): void {
            $this->attachmentService->storeContent($ticketModel, $membership, null, $originalName, $mimeType, $contents);
            $ticketModel->forceFill(['last_activity_at' => now()])->save();
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'attachment-added');
    }

    public function storeReply(StoreTicketReplyRequest $request, string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $data = $request->validated();
        $mailAccount = $this->activeMailAccount();
        $attachments = $this->validatedReplyAttachments($request, $ticketModel, $membership);

        if ($attachments === null) {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['attachments' => 'Uno de los adjuntos no se permite por seguridad.']);
        }

        if ($mailAccount === null) {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['mail_account' => 'Configura una cuenta de correo activa antes de responder.']);
        }

        if (blank($ticketModel->requester_email)) {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['requester_email' => 'Este ticket no tiene correo de solicitante.']);
        }

        try {
            $this->replySender->send($mailAccount, $ticketModel, $data['body_text'], $attachments);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['mail_delivery' => $exception->getMessage()]);
        }

        DB::transaction(function () use ($ticketModel, $membership, $mailAccount, $data, $attachments): void {
            $message = TicketMessage::query()->create([
                'ticket_id' => $ticketModel->id,
                'author_user_id' => $membership?->user_id,
                'author_membership_id' => $membership?->id,
                'author_email' => $mailAccount->from_email,
                'author_name' => $mailAccount->from_name,
                'visibility' => 'public',
                'direction' => 'outbound',
                'body_text' => $data['body_text'],
                'delivered_at' => now(),
            ]);

            foreach ($attachments as $attachment) {
                $this->attachmentService->storeContent(
                    $ticketModel,
                    $membership,
                    $message,
                    $attachment['filename'],
                    $attachment['mime_type'],
                    $attachment['contents'],
                );
            }

            $ticketModel->forceFill([
                'first_response_at' => $ticketModel->first_response_at ?? now(),
                'last_activity_at' => now(),
            ])->save();

            TicketEvent::query()->create([
                'ticket_id' => $ticketModel->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.reply_sent',
                'payload' => [
                    'from_email' => $mailAccount->from_email,
                    'to_email' => $ticketModel->requester_email,
                ],
            ]);
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'reply-sent');
    }

    public function merge(MergeTicketRequest $request, string $ticket): RedirectResponse
    {
        $secondary = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $targetKey = strtoupper(trim($request->validated('target_ticket_key')));
        $primary = $this->findTenantTicketForMerge($targetKey);

        if ($primary === null || $primary->id === $secondary->id || $primary->merged) {
            return redirect()
                ->route('app.tickets.show', $secondary->public_key)
                ->withErrors(['target_ticket_key' => 'Selecciona un ticket principal válido de esta empresa.']);
        }

        if ($secondary->merged) {
            return redirect()
                ->route('app.tickets.show', $secondary->public_key)
                ->withErrors(['target_ticket_key' => 'Este ticket ya está fusionado.']);
        }

        DB::transaction(function () use ($secondary, $primary, $membership): void {
            $secondary->forceFill([
                'status' => 'merged',
                'merged' => true,
                'merged_into_ticket_id' => $primary->id,
                'merged_at' => now(),
                'merged_by_membership_id' => $membership?->id,
                'last_activity_at' => now(),
            ])->save();

            $primary->forceFill(['last_activity_at' => now()])->save();

            TicketEvent::query()->create([
                'ticket_id' => $secondary->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.merged',
                'payload' => [
                    'into_ticket_id' => $primary->id,
                    'into_public_key' => $primary->public_key,
                ],
            ]);

            TicketEvent::query()->create([
                'ticket_id' => $primary->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => 'ticket.merge_received',
                'payload' => [
                    'from_ticket_id' => $secondary->id,
                    'from_public_key' => $secondary->public_key,
                ],
            ]);
        });

        return redirect()->route('app.tickets.show', $primary->public_key)->with('status', 'ticket-merged');
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

    public function updateProperties(UpdateTicketPropertiesRequest $request, string $ticket): RedirectResponse
    {
        $ticketModel = $this->findTenantTicket($ticket);
        $membership = app(TenantContext::class)->membership();
        $data = $request->validated();
        $previous = [
            'status' => $ticketModel->status,
            'priority' => $ticketModel->priority,
            'ticket_type' => $ticketModel->ticket_type,
            'assigned_to_membership_id' => $ticketModel->assigned_to_membership_id,
        ];

        if ($data['status'] === 'closed' && $previous['status'] !== 'resolved') {
            return redirect()
                ->route('app.tickets.show', $ticketModel->public_key)
                ->withErrors(['status' => 'Para cerrar un ticket primero debe estar resuelto.']);
        }

        DB::transaction(function () use ($ticketModel, $membership, $data, $previous): void {
            $changes = [
                'status' => $data['status'],
                'priority' => $data['priority'],
                'ticket_type' => $data['ticket_type'],
                'assigned_to_membership_id' => $data['assigned_to_membership_id'] ?? null,
                'last_activity_at' => now(),
            ];

            if ($data['status'] === 'resolved' && $ticketModel->resolved_at === null) {
                $changes['resolved_at'] = now();
            }

            if ($data['status'] === 'closed' && $ticketModel->closed_at === null) {
                $changes['closed_at'] = now();
            }

            if ($data['status'] !== 'closed') {
                $changes['closed_at'] = null;
            }

            $ticketModel->forceFill($changes)->save();

            $this->recordPropertyEvents($ticketModel, $membership, $previous, $changes);
        });

        return redirect()->route('app.tickets.show', $ticketModel->public_key)->with('status', 'properties-updated');
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

    private function findTenantTicketForMerge(string $ticket): ?Ticket
    {
        return Ticket::query()
            ->where(function ($query) use ($ticket): void {
                $query->where('public_key', $ticket);

                if (ctype_digit($ticket)) {
                    $query->orWhere('id', (int) $ticket);
                }
            })
            ->first();
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
        return Ticket::EDITABLE_STATUS_LABELS;
    }

    private function activeMemberships()
    {
        return Membership::query()
            ->with('user')
            ->where('company_id', app(TenantContext::class)->company()?->id)
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
    }

    private function activeMailAccount(): ?MailAccount
    {
        return MailAccount::query()
            ->where('company_id', app(TenantContext::class)->company()?->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return null|list<array{filename: string, mime_type: string, contents: string}>
     */
    private function validatedReplyAttachments(StoreTicketReplyRequest $request, Ticket $ticket, ?Membership $membership): ?array
    {
        $files = $request->file('attachments', []);

        if ($files === null) {
            return [];
        }

        if (! is_array($files)) {
            $files = [$files];
        }

        $attachments = [];

        foreach ($files as $file) {
            $filename = $this->attachmentService->safeFilename($file->getClientOriginalName());
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $contents = file_get_contents($file->getRealPath()) ?: '';
            $sizeBytes = strlen($contents);

            if ($sizeBytes > $this->attachmentService->maxBytes()) {
                $this->attachmentService->recordBlocked($ticket, $membership, $filename, $mimeType, 'file_too_large', [
                    'size_bytes' => $sizeBytes,
                    'max_size_bytes' => $this->attachmentService->maxBytes(),
                ]);

                return null;
            }

            if ($this->attachmentService->isBlocked($filename, $mimeType)) {
                $this->attachmentService->recordBlocked($ticket, $membership, $filename, $mimeType, 'blocked_file_type');

                return null;
            }

            $attachments[] = [
                'filename' => $filename,
                'mime_type' => $mimeType,
                'contents' => $contents,
            ];
        }

        return $attachments;
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $changes
     */
    private function recordPropertyEvents(Ticket $ticket, ?Membership $membership, array $previous, array $changes): void
    {
        $events = [
            'status' => 'ticket.status_changed',
            'priority' => 'ticket.priority_changed',
            'ticket_type' => 'ticket.type_changed',
            'assigned_to_membership_id' => 'ticket.assigned',
        ];

        foreach ($events as $field => $type) {
            if (($previous[$field] ?? null) === ($changes[$field] ?? null)) {
                continue;
            }

            TicketEvent::query()->create([
                'ticket_id' => $ticket->id,
                'actor_user_id' => $membership?->user_id,
                'actor_membership_id' => $membership?->id,
                'type' => $type,
                'payload' => [
                    'from' => $previous[$field] ?? null,
                    'to' => $changes[$field] ?? null,
                ],
            ]);
        }
    }
}
