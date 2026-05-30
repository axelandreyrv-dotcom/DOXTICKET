<x-layouts.app-shell :title="'Dashboard | '.config('app.name', 'DoxTicket')" :subtitle="$company?->name ?? 'Empresa activa'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Trabajo pendiente</p>
                <h1 class="mt-2 text-2xl font-semibold">Dashboard</h1>
            </div>
            <a href="{{ url('/app/tickets') }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Ver tickets
            </a>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
            <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                <h2 class="text-sm font-semibold">Resumen del dia</h2>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Nuevos</dt>
                        <dd class="mt-1 text-xl font-semibold">0</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Activos</dt>
                        <dd class="mt-1 text-xl font-semibold">0</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Asignados</dt>
                        <dd class="mt-1 text-xl font-semibold">0</dd>
                    </div>
                    <div class="rounded-md bg-[var(--color-bg-surface-alt)] p-3">
                        <dt class="text-xs text-[var(--color-text-muted)]">Resueltos</dt>
                        <dd class="mt-1 text-xl font-semibold">0</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                <div class="flex items-center justify-between border-b border-[var(--color-border-default)] px-4 py-3">
                    <h2 class="text-sm font-semibold">Inbox</h2>
                    <span class="rounded-full bg-[var(--color-info-bg)] px-2 py-1 text-xs font-medium text-[var(--color-info)]">Sin tickets</span>
                </div>
                <div class="p-4">
                    <div class="rounded-md border border-dashed border-[var(--color-border-default)] p-6 text-center text-sm text-[var(--color-text-secondary)]">
                        Los tickets activos apareceran aqui.
                    </div>
                </div>
            </section>
        </div>
    </section>
</x-layouts.app-shell>
