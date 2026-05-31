<x-layouts.app-shell :title="'Actividad | '.config('app.name', 'DoxTicket')" subtitle="Panel de informacion">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Actividad</p>
                <h1 class="mt-2 text-2xl font-semibold">Panel de informacion</h1>
                <p class="mt-1 max-w-2xl text-sm text-[var(--color-text-secondary)]">
                    Historial operativo de tickets dentro de la empresa seleccionada.
                </p>
            </div>
        </div>

        <nav class="mt-5 flex flex-wrap gap-2" aria-label="Filtros de actividad">
            @foreach ($filters as $value => $label)
                <a href="{{ route('app.activity.index', $value === 'all' ? [] : ['type' => $value]) }}" class="rounded-md border px-3 py-2 text-sm font-medium transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] {{ $activeFilter === $value ? 'border-[var(--color-action-primary)] bg-[var(--color-info-bg)] text-[var(--color-action-primary)]' : 'border-[var(--color-border-default)] bg-[var(--color-bg-surface)] text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)]' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <section class="mt-5 overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
            @forelse ($activity as $event)
                <article class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-4 last:border-b-0 sm:grid-cols-[2rem_minmax(0,1fr)_8rem]">
                    <div class="flex size-8 items-center justify-center rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface-alt)] text-xs font-semibold text-[var(--color-text-secondary)]">
                        {{ $event['initial'] }}
                    </div>
                    <div class="min-w-0">
                        <p class="break-words text-sm leading-6 text-[var(--color-text-primary)]">
                            <span class="font-semibold">{{ $event['actor'] }}</span>
                            {{ $event['action'] }}
                            @if ($event['ticket_url'])
                                <a href="{{ $event['ticket_url'] }}" class="font-medium text-[var(--color-action-primary)] transition hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                    {{ $event['ticket_subject'] }} ({{ $event['ticket_key'] }})
                                </a>
                            @else
                                <span class="font-medium">{{ $event['ticket_subject'] }}</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-[var(--color-text-muted)]">{{ $event['type'] }}</p>
                    </div>
                    <time datetime="{{ $event['created_at']?->toIso8601String() }}" class="text-xs text-[var(--color-text-muted)] sm:text-right">
                        {{ $event['created_at']?->diffForHumans() }}
                    </time>
                </article>
            @empty
                <div class="px-4 py-12 text-center">
                    <h2 class="text-sm font-semibold">Sin actividad todavia</h2>
                    <p class="mt-2 text-sm text-[var(--color-text-secondary)]">Cuando el equipo cree, asigne o actualice tickets, aparecera aqui.</p>
                </div>
            @endforelse
        </section>

        <div class="mt-4">
            {{ $activity->links() }}
        </div>
    </section>
</x-layouts.app-shell>
