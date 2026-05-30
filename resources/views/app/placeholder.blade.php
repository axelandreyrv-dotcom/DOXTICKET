<x-layouts.app-shell :title="'Dashboard | '.config('app.name', 'DoxTicket')" :subtitle="$company?->name ?? 'Empresa activa'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Trabajo pendiente</p>
                <h1 class="mt-2 text-2xl font-semibold">Dashboard</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ url('/app/tickets/create') }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Nuevo ticket
                </a>
                <a href="{{ url('/app/tickets') }}" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Ver tickets
                </a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                <h2 class="text-sm font-semibold">Resumen del dia</h2>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Nuevos</dt>
                        <dd class="mt-1 text-xl font-semibold">{{ $metrics['new'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Activos</dt>
                        <dd class="mt-1 text-xl font-semibold">{{ $metrics['active'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Asignados</dt>
                        <dd class="mt-1 text-xl font-semibold">{{ $metrics['assigned'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Resueltos</dt>
                        <dd class="mt-1 text-xl font-semibold">{{ $metrics['resolved'] ?? 0 }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                <div class="flex items-center justify-between border-b border-[var(--color-border-default)] px-4 py-3">
                    <h2 class="text-sm font-semibold">Inbox</h2>
                    <span class="rounded-full bg-[var(--color-info-bg)] px-2 py-1 text-xs font-medium text-[var(--color-info)]">{{ $recentTickets->count() }} activos</span>
                </div>
                <div class="divide-y divide-[var(--color-border-default)]">
                    @forelse ($recentTickets as $ticket)
                        <a href="{{ url('/app/tickets') }}" class="grid gap-2 px-4 py-3 transition hover:bg-[var(--color-bg-surface-alt)] sm:grid-cols-[8rem_1fr_auto] sm:items-center">
                            <span class="font-mono text-xs font-semibold text-[var(--color-info)]">{{ $ticket->public_key }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium">{{ $ticket->subject }}</span>
                                <span class="mt-0.5 block truncate text-xs text-[var(--color-text-muted)]">{{ $ticket->requester_email ?: 'Sin solicitante' }}</span>
                            </span>
                            <span class="text-xs text-[var(--color-text-muted)]">{{ str_replace('_', ' ', $ticket->status) }}</span>
                        </a>
                    @empty
                        <div class="p-4">
                            <div class="rounded-md border border-dashed border-[var(--color-border-default)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
                                Los tickets activos apareceran aqui.
                            </div>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
</x-layouts.app-shell>
