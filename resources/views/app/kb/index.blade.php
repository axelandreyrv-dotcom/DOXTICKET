<x-layouts.app-shell :title="'Base | '.config('app.name', 'DoxTicket')" :subtitle="'Conocimiento interno'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Base de conocimiento</p>
                <h1 class="mt-2 text-2xl font-semibold">Soluciones internas</h1>
                <p class="mt-1 max-w-2xl text-sm text-[var(--color-text-secondary)]">
                    Artículos publicados para resolver solicitudes recurrentes dentro de la empresa activa.
                </p>
            </div>

            @if ($canManage)
                <a href="{{ route('app.kb.create') }}" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Nuevo artículo
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('app.kb.index') }}" class="mt-5 grid gap-3 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-3 sm:grid-cols-[1fr_auto]">
            <label class="block">
                <span class="block text-xs font-medium text-[var(--color-text-muted)]">Buscar</span>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Título o contenido…" autocomplete="off" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
            </label>
            <button type="submit" class="self-end rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Buscar
            </button>
        </form>

        <section class="mt-5 overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
            <div class="divide-y divide-[var(--color-border-default)]">
                @forelse ($articles as $article)
                    <a href="{{ route('app.kb.show', $article->slug) }}" class="block px-4 py-4 transition hover:bg-[var(--color-bg-surface-alt)]">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-semibold">{{ $article->title }}</h2>
                            @if ($article->status !== 'published')
                                <span class="rounded-md bg-[#fbf3db] px-2 py-1 text-xs font-medium text-[var(--color-warning)]">
                                    {{ $article->status === 'archived' ? 'Archivado' : 'Borrador' }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 line-clamp-2 text-sm text-[var(--color-text-secondary)]">{{ Str::limit(strip_tags($article->body_markdown), 160) }}</p>
                        <p class="mt-2 text-xs text-[var(--color-text-muted)]">
                            {{ $article->published_at?->diffForHumans() ?? $article->updated_at->diffForHumans() }}
                        </p>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-[var(--color-text-secondary)]">
                        Todavía no hay artículos publicados.
                    </div>
                @endforelse
            </div>
        </section>

        <div class="mt-4">
            {{ $articles->links() }}
        </div>
    </section>
</x-layouts.app-shell>
