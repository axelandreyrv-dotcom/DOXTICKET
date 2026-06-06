<x-layouts.public-shell :title="'Setup | '.config('app.name', 'DoxTicket')">
    <section class="grid flex-1 gap-8 py-10 lg:grid-cols-[0.95fr_1.05fr] lg:items-start lg:py-16">
        <div class="max-w-xl">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Instalacion inicial</p>
            <h1 class="mt-4 text-3xl font-semibold tracking-normal text-[var(--color-text-primary)] sm:text-4xl">Preparar DoxTicket</h1>
            <p class="mt-4 max-w-prose text-sm leading-6 text-[var(--color-text-secondary)]">
                Crea la primera empresa y el superadmin de esta instalación.
            </p>

            <div class="mt-8 grid gap-3 text-sm">
                <div class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <p class="font-medium">Idioma por defecto</p>
                    <p class="mt-1 text-[var(--color-text-secondary)]">Español primero, inglés disponible.</p>
                </div>
                <div class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                    <p class="font-medium">Aislamiento base</p>
                    <p class="mt-1 text-[var(--color-text-secondary)]">La empresa inicial queda lista con una membresia admin.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ url('/setup') }}" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 sm:p-6">
            @csrf

            <div class="grid gap-5">
                <label for="locale" class="grid gap-2">
                    <span class="text-sm font-medium">Idioma</span>
                    <select id="locale" name="locale" @error('locale') aria-invalid="true" aria-describedby="locale-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <option value="es" @selected(old('locale', 'es') === 'es')>Español</option>
                        <option value="en" @selected(old('locale') === 'en')>English</option>
                    </select>
                    <x-ui.field-error field="locale" class="text-sm" />
                </label>

                <label for="company_name" class="grid gap-2">
                    <span class="text-sm font-medium">Empresa inicial</span>
                    <input id="company_name" name="company_name" value="{{ old('company_name') }}" autocomplete="organization" @error('company_name') aria-invalid="true" aria-describedby="company_name-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="company_name" class="text-sm" />
                </label>

                <div class="grid gap-4 border-t border-[var(--color-border-default)] pt-5 sm:grid-cols-2">
                    <label for="admin_name" class="grid gap-2 sm:col-span-2">
                        <span class="text-sm font-medium">Nombre del superadmin</span>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" autocomplete="name" @error('admin_name') aria-invalid="true" aria-describedby="admin_name-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="admin_name" class="text-sm" />
                    </label>

                    <label for="admin_email" class="grid gap-2 sm:col-span-2">
                        <span class="text-sm font-medium">Correo</span>
                        <input id="admin_email" name="admin_email" value="{{ old('admin_email') }}" type="email" autocomplete="email" spellcheck="false" @error('admin_email') aria-invalid="true" aria-describedby="admin_email-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="admin_email" class="text-sm" />
                    </label>

                    <label for="admin_password" class="grid gap-2">
                        <span class="text-sm font-medium">Contraseña</span>
                        <input id="admin_password" name="admin_password" type="password" autocomplete="new-password" @error('admin_password') aria-invalid="true" aria-describedby="admin_password-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="admin_password" class="text-sm" />
                    </label>

                    <label for="admin_password_confirmation" class="grid gap-2">
                        <span class="text-sm font-medium">Confirmar</span>
                        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" autocomplete="new-password" class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    </label>
                </div>

                <label class="flex items-start gap-3 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface-alt)] p-4 text-sm">
                    <input type="checkbox" name="telemetry_enabled" value="1" @checked(old('telemetry_enabled')) class="mt-0.5 size-4 rounded border-[var(--color-border-default)] text-[var(--color-action-primary)] focus-visible:ring-[var(--color-focus)]">
                    <span>
                        <span class="block font-medium">Activar telemetria opcional</span>
                        <span class="mt-1 block text-[var(--color-text-secondary)]">No envia correos, nombres, tickets, adjuntos ni secretos.</span>
                    </span>
                </label>

                <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-4 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                    Finalizar setup
                </button>
            </div>
        </form>
    </section>
</x-layouts.public-shell>
