<x-layouts.app-shell :title="$ticket->public_key.' | '.config('app.name', 'DoxTicket')" :subtitle="'Tickets / '.$ticket->public_key">
    <section class="py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('app.tickets.index') }}" class="text-sm font-medium text-[var(--color-text-secondary)] transition hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Tickets
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <span class="rounded-md bg-[var(--color-info-bg)] px-2 py-1 font-mono text-xs font-semibold text-[var(--color-info)]">{{ $ticket->public_key }}</span>
                    <span class="rounded-md border border-[var(--color-border-default)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)]">{{ str_replace('_', ' ', $ticket->status) }}</span>
                    <span class="rounded-md border border-[var(--color-border-default)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)]">{{ $ticket->priority }}</span>
                </div>
                <h1 class="mt-3 max-w-4xl break-words text-2xl font-semibold">{{ $ticket->subject }}</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    {{ $ticket->requester_name ?: 'Solicitante sin nombre' }}
                    @if ($ticket->requester_email)
                        <span class="text-[var(--color-text-muted)]">&lt;{{ $ticket->requester_email }}&gt;</span>
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('app.tickets.status.update', $ticket->public_key) }}" class="flex flex-wrap items-center gap-2">
                @csrf
                @method('PATCH')
                <label for="status" class="sr-only">Estado</label>
                <select id="status" name="status" class="h-10 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-3 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                    Actualizar
                </button>
            </form>
        </div>

        @if (session('status') === 'note-added')
            <div class="mt-4 rounded-md border border-[var(--color-success-border)] bg-[var(--color-success-bg)] px-4 py-3 text-sm text-[var(--color-success)]">
                Nota interna agregada.
            </div>
        @endif

        @if (session('status') === 'status-updated')
            <div class="mt-4 rounded-md border border-[var(--color-success-border)] bg-[var(--color-success-bg)] px-4 py-3 text-sm text-[var(--color-success)]">
                Estado actualizado.
            </div>
        @endif

        @error('status')
            <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $message }}
            </div>
        @enderror

        <div class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-5">
                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                    <div class="border-b border-[var(--color-border-default)] px-4 py-3">
                        <h2 class="text-sm font-semibold">Hilo</h2>
                    </div>
                    <div class="divide-y divide-[var(--color-border-default)]">
                        @forelse ($ticket->messages as $message)
                            <article class="px-4 py-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium">{{ $message->authorUser?->name ?? $message->author_name ?? 'Nota interna' }}</p>
                                        <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $message->created_at?->format('Y-m-d H:i') }}</p>
                                    </div>
                                    <span class="rounded-md bg-[var(--color-bg-surface-alt)] px-2 py-1 text-xs text-[var(--color-text-secondary)]">{{ $message->visibility }}</span>
                                </div>
                                <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-[var(--color-text-secondary)]">{{ $message->body_text }}</p>
                            </article>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-[var(--color-text-secondary)]">Este ticket todavia no tiene mensajes.</p>
                        @endforelse
                    </div>
                </section>

                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Agregar Nota Interna</h2>
                    <form method="POST" action="{{ route('app.tickets.messages.store', $ticket->public_key) }}" class="mt-4 grid gap-3">
                        @csrf
                        <label for="body_text" class="sr-only">Nota interna</label>
                        <textarea id="body_text" name="body_text" rows="5" required class="w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm leading-6 outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]" placeholder="Escribe una nota para el equipo…">{{ old('body_text') }}</textarea>
                        @error('body_text') <span class="text-sm text-[var(--color-danger)]">{{ $message }}</span> @enderror
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                Guardar Nota
                            </button>
                        </div>
                    </form>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Detalle</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Agente</dt>
                            <dd class="text-right text-[var(--color-text-secondary)]">{{ $ticket->assignedToMembership?->user?->name ?? 'Sin asignar' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Categoria</dt>
                            <dd class="text-right text-[var(--color-text-secondary)]">{{ $ticket->category?->name ?? 'Sin categoria' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Creado</dt>
                            <dd class="text-right text-[var(--color-text-secondary)]">{{ $ticket->created_at?->format('Y-m-d H:i') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Eventos</h2>
                    <ol class="mt-4 space-y-3">
                        @forelse ($ticket->events->sortByDesc('created_at') as $event)
                            <li class="text-sm">
                                <p class="font-medium text-[var(--color-text-secondary)]">{{ str_replace('_', ' ', $event->type) }}</p>
                                <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $event->created_at?->format('Y-m-d H:i') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-[var(--color-text-secondary)]">Sin eventos todavia.</li>
                        @endforelse
                    </ol>
                </section>
            </aside>
        </div>
    </section>
</x-layouts.app-shell>
