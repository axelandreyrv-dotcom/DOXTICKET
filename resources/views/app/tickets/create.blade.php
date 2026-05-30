<x-layouts.app-shell :title="'Nuevo ticket | '.config('app.name', 'DoxTicket')" :subtitle="'Creacion manual'">
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
                    <input id="requester_name" name="requester_name" value="{{ old('requester_name') }}" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('requester_name')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="requester_email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Correo</label>
                    <input id="requester_email" type="email" name="requester_email" value="{{ old('requester_email') }}" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('requester_email')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="subject" class="block text-sm font-medium text-[var(--color-text-secondary)]">Asunto</label>
                <input id="subject" name="subject" value="{{ old('subject') }}" placeholder="Sin Asunto" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                @error('subject')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="priority" class="block text-sm font-medium text-[var(--color-text-secondary)]">Prioridad</label>
                    <select id="priority" name="priority" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'urgent' => 'Urgente', 'critical' => 'Critica'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="category_id" class="block text-sm font-medium text-[var(--color-text-secondary)]">Categoria</label>
                    <select id="category_id" name="category_id" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <option value="">Sin categoria</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="assigned_to_membership_id" class="block text-sm font-medium text-[var(--color-text-secondary)]">Agente</label>
                    <select id="assigned_to_membership_id" name="assigned_to_membership_id" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <option value="">Sin asignar</option>
                        @foreach ($memberships as $membership)
                            <option value="{{ $membership->id }}" @selected((string) old('assigned_to_membership_id') === (string) $membership->id)>{{ $membership->user?->name ?? $membership->user?->email }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to_membership_id')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label for="body_text" class="block text-sm font-medium text-[var(--color-text-secondary)]">Detalle</label>
                <textarea id="body_text" name="body_text" rows="7" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm leading-6 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">{{ old('body_text') }}</textarea>
                @error('body_text')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Crear ticket
                </button>
            </div>
        </form>
    </section>
</x-layouts.app-shell>
