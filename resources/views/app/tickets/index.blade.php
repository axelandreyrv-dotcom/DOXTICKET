<x-layouts.app-shell :title="'Tickets | '.config('app.name', 'DoxTicket')" :subtitle="'Inbox de trabajo'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Tickets</p>
                <h1 class="mt-2 text-2xl font-semibold">Trabajo pendiente</h1>
                <p class="mt-1 max-w-2xl text-sm text-[var(--color-text-secondary)]">
                    Lista activa del equipo. Los tickets se muestran dentro de la empresa seleccionada.
                </p>
            </div>
            <a href="{{ route('app.tickets.create') }}" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Nuevo ticket
            </a>
        </div>

        <form method="GET" action="{{ route('app.tickets.index') }}" class="mt-5 grid gap-3 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-3 sm:grid-cols-[minmax(0,1fr)_8.75rem]">
            <div>
                <label for="q" class="block text-xs font-medium text-[var(--color-text-muted)]">Buscar</label>
                <input id="q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Clave, asunto o correo…" autocomplete="off" spellcheck="false" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
            </div>

            <button type="submit" class="self-end rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Filtrar
            </button>
        </form>

        <section class="mt-5 overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
            <span id="ticket-list-copy-status" role="status" aria-live="polite" class="sr-only"></span>
            <div class="grid grid-cols-[6.5rem_minmax(0,1fr)_4rem] gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] lg:grid-cols-[9rem_1fr_8rem_8rem_8rem_10rem]">
                <span>Clave</span>
                <span>Asunto</span>
                <span class="hidden lg:block">Prioridad</span>
                <span>Estado</span>
                <span class="hidden lg:block">Tipo</span>
                <span class="hidden lg:block">Agente</span>
            </div>

            <div class="divide-y divide-[var(--color-border-default)]">
                @forelse ($tickets as $ticket)
                    <article class="grid grid-cols-[6.5rem_minmax(0,1fr)_4rem] gap-3 px-4 py-3 text-sm transition hover:bg-[var(--color-bg-surface-alt)] lg:grid-cols-[9rem_1fr_8rem_8rem_8rem_10rem] lg:items-center">
                        <div class="flex min-w-0 flex-col items-start gap-1">
                            <a href="{{ route('app.tickets.show', $ticket->public_key) }}" translate="no" class="font-mono text-xs font-semibold text-[var(--color-info)] transition hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ $ticket->public_key }}</a>
                            <button type="button" data-copy-text="{{ $ticket->public_key }}" data-copy-success="Copiado" data-copy-error="No se pudo copiar" aria-label="Copiar clave {{ $ticket->public_key }}" aria-describedby="ticket-list-copy-status" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                Copiar
                            </button>
                        </div>
                        <div class="min-w-0">
                            <a href="{{ route('app.tickets.show', $ticket->public_key) }}" class="block truncate font-medium transition hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ $ticket->subject }}</a>
                            <p class="mt-0.5 truncate text-xs text-[var(--color-text-muted)]">
                                {{ $ticket->requester_email ?: 'Sin solicitante' }} · {{ $sourceLabels[$ticket->source] ?? $ticket->source }}
                            </p>
                            @if ($ticket->sla_due_at !== null && $ticket->sla_due_at->isPast() && in_array($ticket->status, \App\Models\Ticket::ACTIVE_STATUSES, true))
                                <p class="mt-1 text-xs font-medium text-[var(--color-danger)]">SLA vencido</p>
                            @endif
                            <div class="mt-2 lg:hidden">
                                @if ($ticket->assigned_to_membership_id === $activeMembership?->id)
                                    <span class="text-xs font-medium text-[var(--color-success)]">Asignado a ti</span>
                                @elseif ($ticket->assigned_to_membership_id === null)
                                    <form method="POST" action="{{ route('app.tickets.assign-self', $ticket->public_key) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                            Asignarme
                                        </button>
                                    </form>
                                @else
                                    <span class="block truncate text-xs text-[var(--color-text-muted)]">{{ $ticket->assignedToMembership?->user?->name ?? 'Sin asignar' }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="hidden text-xs text-[var(--color-text-secondary)] lg:block">{{ $priorityLabels[$ticket->priority] ?? $ticket->priority }}</span>
                        <span class="text-xs text-[var(--color-text-secondary)]">{{ $statusLabels[$ticket->status] ?? $ticket->status }}</span>
                        <span class="hidden text-xs text-[var(--color-text-secondary)] lg:block">{{ $typeLabels[$ticket->ticket_type] ?? $ticket->ticket_type }}</span>
                        <div class="hidden min-w-0 lg:block">
                            @if ($ticket->assigned_to_membership_id === $activeMembership?->id)
                                <span class="block truncate text-xs font-medium text-[var(--color-success)]">Asignado a ti</span>
                            @elseif ($ticket->assigned_to_membership_id === null)
                                <form method="POST" action="{{ route('app.tickets.assign-self', $ticket->public_key) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                        Asignarme
                                    </button>
                                </form>
                            @else
                                <span class="block truncate text-xs text-[var(--color-text-muted)]">{{ $ticket->assignedToMembership?->user?->name ?? 'Sin asignar' }}</span>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="p-8 text-center text-sm text-[var(--color-text-secondary)]">
                        No hay tickets activos en esta empresa.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="mt-4">
            {{ $tickets->links() }}
        </div>
    </section>
</x-layouts.app-shell>
