<x-layouts.app-shell :title="'Configuracion | '.config('app.name', 'DoxTicket')" :subtitle="$company?->name ?? 'Empresa activa'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Configuracion</p>
                <h1 class="mt-2 text-2xl font-semibold">Correo de soporte</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                    Una cuenta por empresa para recibir solicitudes y enviar respuestas con marcador visible.
                </p>
            </div>
            @if ($mailAccount)
                <span class="rounded-full bg-[var(--color-success-bg)] px-2 py-1 text-xs font-medium text-[var(--color-success)]">Configurado</span>
            @else
                <span class="rounded-full bg-[var(--color-info-bg)] px-2 py-1 text-xs font-medium text-[var(--color-info)]">Pendiente</span>
            @endif
        </div>

        @if (session('status') === 'mail-settings-saved')
            <div class="mt-4 rounded-md border border-[var(--color-success-border)] bg-[var(--color-success-bg)] px-4 py-3 text-sm text-[var(--color-success)]">
                Configuracion guardada.
            </div>
        @endif

        @if ($mailAccount?->last_error)
            <div class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $mailAccount->last_error }}
            </div>
        @endif

        <form method="POST" action="{{ url('/app/settings/mail') }}" class="mt-6 grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
            @csrf

            <input type="hidden" name="provider" value="imap_smtp">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="from_name" class="block text-sm font-medium text-[var(--color-text-secondary)]">Nombre visible</label>
                    <input id="from_name" name="from_name" value="{{ old('from_name', $mailAccount?->from_name) }}" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('from_name')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="from_email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Correo de soporte</label>
                    <input id="from_email" type="email" name="from_email" value="{{ old('from_email', $mailAccount?->from_email) }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('from_email')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 border-t border-[var(--color-border-default)] pt-5 md:grid-cols-[1fr_8rem_10rem]">
                <div>
                    <label for="host_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Servidor IMAP</label>
                    <input id="host_imap" name="host_imap" value="{{ old('host_imap', $mailAccount?->host_imap) }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('host_imap')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="port_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Puerto</label>
                    <input id="port_imap" type="number" name="port_imap" min="1" max="65535" value="{{ old('port_imap', $mailAccount?->port_imap ?? 993) }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('port_imap')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="security_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Seguridad</label>
                    <select id="security_imap" name="security_imap" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS', 'none' => 'Ninguna'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('security_imap', $mailAccount?->security_imap ?? 'ssl') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('security_imap')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-[1fr_8rem_10rem]">
                <div>
                    <label for="host_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Servidor SMTP</label>
                    <input id="host_smtp" name="host_smtp" value="{{ old('host_smtp', $mailAccount?->host_smtp) }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('host_smtp')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="port_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Puerto</label>
                    <input id="port_smtp" type="number" name="port_smtp" min="1" max="65535" value="{{ old('port_smtp', $mailAccount?->port_smtp ?? 587) }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('port_smtp')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="security_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Seguridad</label>
                    <select id="security_smtp" name="security_smtp" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (['tls' => 'TLS', 'starttls' => 'STARTTLS', 'ssl' => 'SSL', 'none' => 'Ninguna'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('security_smtp', $mailAccount?->security_smtp ?? 'tls') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('security_smtp')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 border-t border-[var(--color-border-default)] pt-5 md:grid-cols-3">
                <div>
                    <label for="username" class="block text-sm font-medium text-[var(--color-text-secondary)]">Usuario</label>
                    <input id="username" name="username" value="{{ old('username', $mailAccount?->username) }}" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('username')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[var(--color-text-secondary)]">Contrasena</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" placeholder="{{ $mailAccount ? 'Mantener actual' : '' }}" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('password')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="folder_in" class="block text-sm font-medium text-[var(--color-text-secondary)]">Carpeta entrada</label>
                    <input id="folder_in" name="folder_in" value="{{ old('folder_in', $mailAccount?->folder_in ?? 'INBOX') }}" required class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    @error('folder_in')<p class="mt-1 text-xs text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-md bg-[var(--color-bg-surface-alt)] p-3 text-sm">
                <input type="checkbox" name="auto_reply_enabled" value="1" @checked(old('auto_reply_enabled', $mailAccount?->auto_reply_enabled ?? true)) class="mt-0.5 size-4 rounded border-[var(--color-border-default)] text-[var(--color-action-primary)] focus-visible:ring-[var(--color-focus)]">
                <span>
                    <span class="block font-medium">Enviar confirmacion automatica de recibido</span>
                    <span class="mt-1 block text-[var(--color-text-secondary)]">Se usara cuando la ingesta de correo cree un ticket nuevo.</span>
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Guardar correo
                </button>
            </div>
        </form>
    </section>
</x-layouts.app-shell>
