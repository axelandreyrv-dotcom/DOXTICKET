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

        @if (session('status'))
            <div class="mt-4 rounded-md border border-[var(--color-success-border)] bg-[var(--color-success-bg)] px-4 py-3 text-sm text-[var(--color-success)]">
                Ticket creado.
            </div>
        @endif

        <form method="GET" action="{{ route('app.tickets.index') }}" class="mt-5 flex flex-wrap items-center gap-2">
            <label for="status" class="text-sm font-medium text-[var(--color-text-secondary)]">Estado</label>
            <select id="status" name="status" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <option value="">Activos</option>
                @foreach (['new' => 'Nuevo', 'open' => 'Abierto', 'in_progress' => 'En progreso', 'waiting_customer' => 'Espera cliente', 'waiting_internal' => 'Espera interna', 'reopened' => 'Reabierto'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Filtrar
            </button>
        </form>

        <section class="mt-5 overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
            <div class="grid grid-cols-[5rem_minmax(0,1fr)_4rem] gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-xs font-semibold text-[var(--color-text-muted)] sm:grid-cols-[8rem_1fr_10rem_8rem_10rem]">
                <span>Clave</span>
                <span>Asunto</span>
                <span class="hidden sm:block">Prioridad</span>
                <span>Estado</span>
                <span class="hidden sm:block">Agente</span>
            </div>

            <div class="divide-y divide-[var(--color-border-default)]">
                @forelse ($tickets as $ticket)
                    <article class="grid grid-cols-[5rem_minmax(0,1fr)_4rem] gap-3 px-4 py-3 text-sm transition hover:bg-[var(--color-bg-surface-alt)] sm:grid-cols-[8rem_1fr_10rem_8rem_10rem] sm:items-center">
                        <a href="{{ route('app.tickets.show', $ticket->public_key) }}" class="font-mono text-xs font-semibold text-[var(--color-info)] transition hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ $ticket->public_key }}</a>
                        <div class="min-w-0">
                            <a href="{{ route('app.tickets.show', $ticket->public_key) }}" class="block truncate font-medium transition hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ $ticket->subject }}</a>
                            <p class="mt-0.5 truncate text-xs text-[var(--color-text-muted)]">{{ $ticket->requester_email ?: 'Sin solicitante' }}</p>
                            <div class="mt-2 sm:hidden">
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
                        <span class="hidden text-xs text-[var(--color-text-secondary)] sm:block">{{ $ticket->priority }}</span>
                        <span class="text-xs text-[var(--color-text-secondary)]">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        <div class="hidden min-w-0 sm:block">
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
