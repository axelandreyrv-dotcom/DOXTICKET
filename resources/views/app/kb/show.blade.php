<x-layouts.app-shell :title="$article->title.' | '.config('app.name', 'DoxTicket')" :subtitle="'Base de conocimiento'">
    <article class="py-6">
        <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('app.kb.index') }}" class="text-sm font-medium text-[var(--color-info)] transition hover:text-[var(--color-action-primary-hover)]">
                    Volver a la base
                </a>

                @if ($canManage)
                    <a href="{{ route('app.kb.edit', $article->slug) }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        Editar
                    </a>
                    @if ($article->status !== 'archived')
                        <form method="POST" action="{{ route('app.kb.archive', $article->slug) }}" data-confirm="Archivar este artículo lo ocultará para agentes. ¿Continuar?">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                Archivar
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('app.kb.destroy', $article->slug) }}" data-confirm="Borrar este artículo lo enviará a la papelera interna. ¿Continuar?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md border border-[var(--color-danger)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-danger)] transition hover:bg-[var(--color-danger-bg)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            Borrar
                        </button>
                    </form>
                @endif
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-2">
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Artículo interno</p>
                @if ($article->status !== 'published')
                    <span class="rounded-md bg-[#fbf3db] px-2 py-1 text-xs font-medium text-[var(--color-warning)]">
                        {{ $article->status === 'archived' ? 'Archivado' : 'Borrador' }}
                    </span>
                @endif
            </div>

            <h1 class="mt-2 text-3xl font-semibold">{{ $article->title }}</h1>
            <p class="mt-2 text-sm text-[var(--color-text-muted)]">
                Actualizado {{ $article->updated_at->diffForHumans() }}
            </p>

            <div class="mt-6 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5">
                <div class="prose max-w-none text-[var(--color-text-primary)] prose-headings:text-[var(--color-text-primary)] prose-a:text-[var(--color-info)]">
                    {!! $article->body_html_cached !!}
                </div>
            </div>
        </div>
    </article>
</x-layouts.app-shell>
