<x-layouts.app-shell :title="'Nuevo ticket | '.config('app.name', 'DoxTicket')" :subtitle="'Creación manual'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Tickets</p>
                <h1 class="mt-2 text-2xl font-semibold">Nuevo ticket</h1>
                <p class="mt-1 max-w-2xl text-sm text-[var(--color-text-secondary)]">
                    Registro manual para solicitudes recibidas por llamada o canal interno.
                </p>
            </div>
            <a href="{{ route('app.tickets.index') }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Volver
            </a>
        </div>

        <form method="POST" action="{{ route('app.tickets.store') }}" class="mt-6 grid gap-4 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
            @csrf

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="requester_name" class="block text-sm font-medium text-[var(--color-text-secondary)]">Solicitante</label>
                    <input id="requester_name" name="requester_name" autocomplete="off" value="{{ old('requester_name') }}" @error('requester_name') aria-invalid="true" aria-describedby="requester_name-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="requester_name" />
                </div>

                <div>
                    <label for="requester_email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Correo</label>
                    <input id="requester_email" type="email" name="requester_email" inputmode="email" autocomplete="off" spellcheck="false" value="{{ old('requester_email') }}" @error('requester_email') aria-invalid="true" aria-describedby="requester_email-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="requester_email" />
                </div>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-[var(--color-text-secondary)]">Asunto</label>
                <input id="subject" name="subject" autocomplete="off" value="{{ old('subject') }}" placeholder="Sin asunto…" @error('subject') aria-invalid="true" aria-describedby="subject-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="subject" />
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="priority" class="block text-sm font-medium text-[var(--color-text-secondary)]">Prioridad</label>
                    <select id="priority" name="priority" @error('priority') aria-invalid="true" aria-describedby="priority-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (\App\Models\Ticket::PRIORITY_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="priority" />
                </div>

                <div>
                    <label for="ticket_type" class="block text-sm font-medium text-[var(--color-text-secondary)]">Tipo</label>
                    <select id="ticket_type" name="ticket_type" @error('ticket_type') aria-invalid="true" aria-describedby="ticket_type-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (\App\Models\Ticket::TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected(old('ticket_type', 'request') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="ticket_type" />
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-[var(--color-text-secondary)]">Categoría</label>
                    <select id="category_id" name="category_id" @error('category_id') aria-invalid="true" aria-describedby="category_id-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <option value="">Sin categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="category_id" />
                </div>

                <div>
                    <label for="assigned_to_membership_id" class="block text-sm font-medium text-[var(--color-text-secondary)]">Agente</label>
                    <select id="assigned_to_membership_id" name="assigned_to_membership_id" @error('assigned_to_membership_id') aria-invalid="true" aria-describedby="assigned_to_membership_id-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <option value="">Sin asignar</option>
                        @foreach ($memberships as $membership)
                            <option value="{{ $membership->id }}" @selected((string) old('assigned_to_membership_id') === (string) $membership->id)>{{ $membership->user?->name ?? $membership->user?->email }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="assigned_to_membership_id" />
                </div>
            </div>

            <div>
                <label for="body_text" class="block text-sm font-medium text-[var(--color-text-secondary)]">Detalle</label>
                <textarea id="body_text" name="body_text" rows="7" required autocomplete="off" @error('body_text') aria-invalid="true" aria-describedby="body_text-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm leading-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ old('body_text') }}</textarea>
                <x-ui.field-error field="body_text" />
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Crear ticket
                </button>
            </div>
        </form>
    </section>
</x-layouts.app-shell>
