<x-layouts.app-shell :title="$ticket->public_key.' | '.config('app.name', 'DoxTicket')" :subtitle="'Tickets / '.$ticket->public_key">
    <section class="py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <a href="{{ route('app.tickets.index') }}" class="text-sm font-medium text-[var(--color-text-secondary)] transition hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Tickets
                </a>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span translate="no" class="rounded-md bg-[var(--color-info-bg)] px-2 py-1 font-mono text-xs font-semibold text-[var(--color-info)]">{{ $ticket->public_key }}</span>
                        <button type="button" data-copy-text="{{ $ticket->public_key }}" data-copy-success="Copiado" data-copy-error="No se pudo copiar" aria-label="Copiar clave {{ $ticket->public_key }}" aria-describedby="ticket-key-copy-status" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                            Copiar
                        </button>
                        <span id="ticket-key-copy-status" role="status" aria-live="polite" class="sr-only"></span>
                    </div>
                    <span class="rounded-md border border-[var(--color-border-default)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)]">{{ \App\Models\Ticket::STATUS_LABELS[$ticket->status] ?? $ticket->status }}</span>
                    <span class="rounded-md border border-[var(--color-border-default)] px-2 py-1 text-xs font-medium text-[var(--color-text-secondary)]">{{ $priorityOptions[$ticket->priority] ?? $ticket->priority }}</span>
                </div>
                <h1 class="mt-3 max-w-4xl break-words text-2xl font-semibold">{{ $ticket->subject }}</h1>
                <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                    {{ $ticket->requester_name ?: 'Solicitante sin nombre' }}
                    @if ($ticket->requester_email)
                        <span class="text-[var(--color-text-muted)]">&lt;{{ $ticket->requester_email }}&gt;</span>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="#reply-form" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Responder
                </a>
                <a href="#note-form" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Nota interna
                </a>
                <a href="#ticket-events" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Actividad
                </a>
                <form method="POST" action="{{ route('app.tickets.properties.update', $ticket->public_key) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="resolved">
                    <input type="hidden" name="priority" value="{{ $ticket->priority }}">
                    <input type="hidden" name="ticket_type" value="{{ $ticket->ticket_type }}">
                    <input type="hidden" name="assigned_to_membership_id" value="{{ $ticket->assigned_to_membership_id }}">
                    <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                        Resolver
                    </button>
                </form>
            </div>
        </div>

        @error('mail_account')
            <div role="alert" aria-live="polite" class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $message }}
            </div>
        @enderror

        @error('requester_email')
            <div role="alert" aria-live="polite" class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-[var(--color-danger)]">
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
                            @php
                                $messageTone = match ($message->direction) {
                                    'inbound' => [
                                        'label' => 'Correo entrante',
                                        'class' => 'bg-[var(--color-info-bg)] text-[var(--color-info)]',
                                    ],
                                    'outbound' => [
                                        'label' => 'Respuesta enviada',
                                        'class' => 'bg-[var(--color-success-bg)] text-[var(--color-success)]',
                                    ],
                                    default => [
                                        'label' => 'Nota interna',
                                        'class' => 'bg-[var(--color-bg-surface-alt)] text-[var(--color-text-secondary)]',
                                    ],
                                };
                            @endphp
                            <article class="px-4 py-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="break-words text-sm font-medium">{{ $message->authorUser?->name ?? $message->author_name ?? 'Nota interna' }}</p>
                                        @if ($message->author_email)
                                            <p class="mt-0.5 break-all text-xs text-[var(--color-text-muted)]">{{ $message->author_email }}</p>
                                        @endif
                                        <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $message->created_at?->format('Y-m-d H:i') }}</p>
                                    </div>
                                    <span class="shrink-0 rounded-md px-2 py-1 text-xs font-medium {{ $messageTone['class'] }}">
                                        {{ $messageTone['label'] }}
                                    </span>
                                </div>
                                <p class="mt-3 whitespace-pre-wrap break-words text-sm leading-6 text-[var(--color-text-secondary)]">{{ $message->body_text }}</p>
                                @if ($message->external_images_blocked)
                                    @php($externalImageUrls = $message->external_image_urls ?? [])
                                    <div class="mt-3 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface-alt)] px-3 py-2">
                                        <p class="text-xs font-medium text-[var(--color-text-secondary)]">Imágenes externas bloqueadas por privacidad</p>
                                        @if ($externalImageUrls !== [])
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach ($externalImageUrls as $externalImageUrl)
                                                    <a href="{{ $externalImageUrl }}" target="_blank" rel="noopener noreferrer nofollow" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-2 py-1 text-xs font-medium text-[var(--color-action-primary)] transition hover:border-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                                        Abrir imagen {{ $loop->iteration }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </article>
                        @empty
                            <p class="px-4 py-8 text-center text-sm text-[var(--color-text-secondary)]">Este ticket todavía no tiene mensajes.</p>
                        @endforelse
                    </div>
                </section>

                <section id="reply-form" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">Responder</h2>
                            <p class="mt-1 text-xs text-[var(--color-text-muted)]">
                                Para {{ $ticket->requester_email ?: 'solicitante sin correo' }}
                            </p>
                        </div>
                        <span class="rounded-md bg-[var(--color-info-bg)] px-2 py-1 font-mono text-xs font-semibold text-[var(--color-info)]">{{ $ticket->public_key }}</span>
                    </div>
                    <form method="POST" action="{{ route('app.tickets.replies.store', $ticket->public_key) }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                        @csrf
                        <label for="reply_body_text" class="sr-only">Respuesta</label>
                        <textarea id="reply_body_text" name="body_text" rows="6" required autocomplete="off" @error('body_text') aria-invalid="true" aria-describedby="reply_body_text-error" @enderror class="w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm leading-6 outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]" placeholder="Escribe la respuesta para el solicitante…">{{ old('body_text') }}</textarea>
                        <x-ui.field-error field="body_text" id="reply_body_text-error" />
                        <div>
                            <label for="reply_attachments" class="block text-xs font-medium text-[var(--color-text-muted)]">Adjuntos opcionales</label>
                            <input id="reply_attachments" name="attachments[]" type="file" multiple @error('attachments') aria-invalid="true" aria-describedby="reply_attachments-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm text-[var(--color-text-secondary)] outline-none transition file:mr-3 file:rounded-md file:border-0 file:bg-[var(--color-bg-surface-alt)] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[var(--color-text-secondary)] focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            <x-ui.field-error field="attachments" id="reply_attachments-error" />
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                Enviar respuesta
                            </button>
                        </div>
                    </form>
                </section>

                <section id="note-form" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Agregar Nota Interna</h2>
                    <form method="POST" action="{{ route('app.tickets.messages.store', $ticket->public_key) }}" class="mt-4 grid gap-3">
                        @csrf
                        <label for="body_text" class="sr-only">Nota interna</label>
                        <textarea id="body_text" name="body_text" rows="5" required autocomplete="off" @error('body_text') aria-invalid="true" aria-describedby="body_text-error" @enderror class="w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm leading-6 outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]" placeholder="Escribe una nota para el equipo…">{{ old('body_text') }}</textarea>
                        <x-ui.field-error field="body_text" id="body_text-error" />
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                Guardar Nota
                            </button>
                        </div>
                    </form>
                </section>

                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold">Adjuntos</h2>
                            <p class="mt-1 text-xs text-[var(--color-text-muted)]">Archivos privados del ticket.</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('app.tickets.attachments.store', $ticket->public_key) }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                        @csrf
                        <label for="attachment" class="block text-xs font-medium text-[var(--color-text-muted)]">Archivo</label>
                        <input id="attachment" name="attachment" type="file" required @error('attachment') aria-invalid="true" aria-describedby="attachment-error" @enderror class="w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm text-[var(--color-text-secondary)] outline-none transition file:mr-3 file:rounded-md file:border-0 file:bg-[var(--color-bg-surface-alt)] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[var(--color-text-secondary)] focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="attachment" />
                        <div class="flex justify-end">
                            <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-semibold text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                Subir adjunto
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 divide-y divide-[var(--color-border-default)] border-t border-[var(--color-border-default)]">
                        @forelse ($ticket->attachments as $attachment)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                                <div class="min-w-0">
                                    <a href="{{ route('app.attachments.download', $attachment->uuid) }}" class="block truncate font-medium text-[var(--color-text-primary)] transition hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                        {{ $attachment->filename }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ number_format($attachment->size_bytes / 1024, 1) }} KB</p>
                                </div>
                                <span class="rounded-md bg-[var(--color-bg-surface-alt)] px-2 py-1 text-xs text-[var(--color-text-secondary)]">Privado</span>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-[var(--color-text-secondary)]">Sin adjuntos todavía.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Propiedades</h2>
                    <form method="POST" action="{{ route('app.tickets.properties.update', $ticket->public_key) }}" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="status" class="block text-xs font-medium text-[var(--color-text-muted)]">Estado</label>
                            <select id="status" name="status" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.field-error field="status" />
                        </div>

                        <div>
                            <label for="priority" class="block text-xs font-medium text-[var(--color-text-muted)]">Prioridad</label>
                            <select id="priority" name="priority" @error('priority') aria-invalid="true" aria-describedby="priority-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                @foreach ($priorityOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->priority === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.field-error field="priority" />
                        </div>

                        <div>
                            <label for="ticket_type" class="block text-xs font-medium text-[var(--color-text-muted)]">Tipo</label>
                            <select id="ticket_type" name="ticket_type" @error('ticket_type') aria-invalid="true" aria-describedby="ticket_type-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($ticket->ticket_type === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.field-error field="ticket_type" />
                        </div>

                        <div>
                            <label for="assigned_to_membership_id" class="block text-xs font-medium text-[var(--color-text-muted)]">Agente</label>
                            <select id="assigned_to_membership_id" name="assigned_to_membership_id" @error('assigned_to_membership_id') aria-invalid="true" aria-describedby="assigned_to_membership_id-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                <option value="">Sin asignar</option>
                                @foreach ($memberships as $membership)
                                    <option value="{{ $membership->id }}" @selected($ticket->assigned_to_membership_id === $membership->id)>{{ $membership->user?->name ?? $membership->user?->email }}</option>
                                @endforeach
                            </select>
                            <x-ui.field-error field="assigned_to_membership_id" />
                        </div>

                        <dl class="space-y-3 border-t border-[var(--color-border-default)] pt-4 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--color-text-muted)]">Fuente</dt>
                                <dd class="text-right text-[var(--color-text-secondary)]">{{ $sourceOptions[$ticket->source] ?? $ticket->source }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--color-text-muted)]">Categoría</dt>
                                <dd class="text-right text-[var(--color-text-secondary)]">{{ $ticket->category?->name ?? 'Sin categoría' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-[var(--color-text-muted)]">Creado</dt>
                                <dd class="text-right text-[var(--color-text-secondary)]">{{ $ticket->created_at?->format('Y-m-d H:i') }}</dd>
                            </div>
                        </dl>

                        <button type="submit" class="w-full rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                            Guardar propiedades
                        </button>
                    </form>
                </section>

                <section class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Fusionar ticket</h2>
                    @if ($ticket->merged && $ticket->mergedIntoTicket)
                        <p class="mt-2 text-sm text-[var(--color-text-secondary)]">
                            Este ticket fue fusionado con
                            <a href="{{ route('app.tickets.show', $ticket->mergedIntoTicket->public_key) }}" class="font-mono font-semibold text-[var(--color-info)] transition hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                                {{ $ticket->mergedIntoTicket->public_key }}
                            </a>.
                        </p>
                    @else
                        <form method="POST" action="{{ route('app.tickets.merge.store', $ticket->public_key) }}" data-confirm="Fusionar este ticket lo marcará como fusionado y moverá futuras respuestas al principal. ¿Continuar?" class="mt-4 grid gap-3">
                            @csrf
                            <label for="target_ticket_key" class="block text-xs font-medium text-[var(--color-text-muted)]">Ticket principal</label>
                            <input id="target_ticket_key" name="target_ticket_key" type="text" inputmode="text" autocomplete="off" spellcheck="false" placeholder="DT-123" value="{{ old('target_ticket_key') }}" required @error('target_ticket_key') aria-invalid="true" aria-describedby="target_ticket_key-error" @enderror class="w-full rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 font-mono text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            <x-ui.field-error field="target_ticket_key" />
                            <p class="text-xs leading-5 text-[var(--color-text-muted)]">El ticket actual quedará como fusionado. Las respuestas futuras al marcador de este ticket irán al principal.</p>
                            <div class="flex justify-end">
                                <button type="submit" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-semibold text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                                    Fusionar
                                </button>
                            </div>
                        </form>
                    @endif
                </section>

                <section id="ticket-events" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <h2 class="text-sm font-semibold">Actividad</h2>
                    <ol class="mt-4 space-y-3">
                        @forelse ($ticket->events->sortByDesc('created_at') as $event)
                            <li class="text-sm">
                                <p class="font-medium text-[var(--color-text-secondary)]">{{ $event->label() }}</p>
                                <p class="mt-0.5 text-xs text-[var(--color-text-muted)]">{{ $event->created_at?->format('Y-m-d H:i') }}</p>
                            </li>
                        @empty
                            <li class="text-sm text-[var(--color-text-secondary)]">Sin eventos todavía.</li>
                        @endforelse
                    </ol>
                </section>
            </aside>
        </div>
    </section>
</x-layouts.app-shell>
