<x-layouts.app-shell :title="'Empresa | '.config('app.name', 'DoxTicket')" subtitle="Seleccion de empresa">
    <section class="mx-auto w-full max-w-3xl py-10">
        <div class="mb-6">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Contexto activo</p>
            <h1 class="mt-3 text-2xl font-semibold">Elegir empresa</h1>
        </div>

        <div class="grid gap-3">
            @forelse ($memberships as $membership)
                <form method="POST" action="{{ url('/app/companies/select') }}" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 transition hover:border-[var(--color-border-strong)]">
                    @csrf
                    <input type="hidden" name="membership_id" value="{{ $membership->id }}">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $membership->company->name }}</p>
                            <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ ucfirst($membership->role) }}</p>
                        </div>
                        <button type="submit" class="h-9 rounded-md bg-[var(--color-action-primary)] px-3 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                            Entrar
                        </button>
                    </div>
                </form>
            @empty
                <div class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 text-sm text-[var(--color-text-secondary)]">
                    No tienes empresas activas.
                </div>
            @endforelse
        </div>
    </section>
</x-layouts.app-shell>
