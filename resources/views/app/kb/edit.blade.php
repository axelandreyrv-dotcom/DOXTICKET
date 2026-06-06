<x-layouts.app-shell :title="'Editar artículo | '.config('app.name', 'DoxTicket')" :subtitle="'Base de conocimiento'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Base de conocimiento</p>
                <h1 class="mt-2 text-2xl font-semibold">Editar artículo</h1>
            </div>
            <a href="{{ route('app.kb.show', $article->slug) }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Ver artículo
            </a>
        </div>

        <form method="POST" action="{{ route('app.kb.update', $article->slug) }}" class="mt-5 max-w-3xl rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
            @csrf
            @method('PATCH')

            <label class="block">
                <span class="text-sm font-medium">Título</span>
                <input id="title" name="title" value="{{ old('title', $article->title) }}" required autocomplete="off" @error('title') aria-invalid="true" aria-describedby="title-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="title" class="text-sm" />
            </label>

            <label class="mt-4 block">
                <span class="text-sm font-medium">Contenido Markdown</span>
                <textarea id="body_markdown" name="body_markdown" rows="12" required autocomplete="off" @error('body_markdown') aria-invalid="true" aria-describedby="body_markdown-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ old('body_markdown', $article->body_markdown) }}</textarea>
                <x-ui.field-error field="body_markdown" class="text-sm" />
            </label>

            <label class="mt-4 block">
                <span class="text-sm font-medium">Estado</span>
                <select id="status" name="status" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <option value="draft" @selected(old('status', $article->status) === 'draft')>Borrador</option>
                    <option value="published" @selected(old('status', $article->status) === 'published')>Publicado</option>
                    <option value="archived" @selected(old('status', $article->status) === 'archived')>Archivado</option>
                </select>
                <x-ui.field-error field="status" class="text-sm" />
            </label>

            <div class="mt-5 flex flex-wrap gap-2">
                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Guardar cambios
                </button>
                <a href="{{ route('app.kb.show', $article->slug) }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Cancelar
                </a>
            </div>
        </form>
    </section>
</x-layouts.app-shell>
