<x-layouts.app-shell :title="'Configuración | '.config('app.name', 'DoxTicket')" :subtitle="$company?->name ?? 'Empresa activa'">
    <section class="py-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Configuración</p>
                <h1 class="mt-2 text-2xl font-semibold">Configuración</h1>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                    Seguridad de cuenta y correo de soporte de la empresa activa.
                </p>
            </div>
            @if ($mailAccount)
                <span class="rounded-full bg-[var(--color-success-bg)] px-2 py-1 text-xs font-medium text-[var(--color-success)]">Configurado</span>
            @else
                <span class="rounded-full bg-[var(--color-info-bg)] px-2 py-1 text-xs font-medium text-[var(--color-info)]">Pendiente</span>
            @endif
        </div>

        @error('mail_account')
            <div role="alert" aria-live="polite" class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $message }}
            </div>
        @enderror

        @error('mail_test')
            <div role="alert" aria-live="polite" class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $message }}
            </div>
        @enderror

        @error('mail_sync')
            <div role="alert" aria-live="polite" class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $message }}
            </div>
        @enderror

        @if ($mailAccount?->last_error && ! $errors->has('mail_test') && ! $errors->has('mail_sync'))
            <div class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-danger)]">
                {{ $mailAccount->last_error }}
            </div>
        @endif

        <section class="mt-6 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Seguridad</p>
                    <h2 class="mt-2 text-lg font-semibold">Verificación 2FA</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Protege tu cuenta con una app autenticadora. Los códigos de recuperación sirven si pierdes acceso al dispositivo.
                    </p>
                </div>
                <span class="rounded-full px-2 py-1 text-xs font-medium {{ auth()->user()->hasTwoFactorEnabled() ? 'bg-[var(--color-success-bg)] text-[var(--color-success)]' : 'bg-[var(--color-info-bg)] text-[var(--color-info)]' }}">
                    {{ auth()->user()->hasTwoFactorEnabled() ? 'Activo' : 'Inactivo' }}
                </span>
            </div>

            @if ($twoFactorProvisioningUri)
                <div class="mt-4 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface-alt)] p-3 text-sm">
                    <p class="font-medium">Agrega este secreto en tu app autenticadora.</p>
                    <p class="mt-2 break-all font-mono text-xs text-[var(--color-text-secondary)]">{{ auth()->user()->two_factor_secret }}</p>
                    <p class="mt-2 break-all text-xs text-[var(--color-text-muted)]">{{ $twoFactorProvisioningUri }}</p>
                </div>

                <form method="POST" action="{{ route('app.settings.two-factor.confirm') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                    @csrf
                    <div>
                        <label for="two_factor_code" class="block text-sm font-medium text-[var(--color-text-secondary)]">Código de verificación</label>
                        <input id="two_factor_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" spellcheck="false" required @error('code') aria-invalid="true" aria-describedby="code-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="code" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] sm:w-auto">
                            Activar 2FA
                        </button>
                    </div>
                </form>
            @elseif (auth()->user()->hasTwoFactorEnabled())
                <div class="mt-4 rounded-md bg-[var(--color-bg-surface-alt)] p-3 text-sm text-[var(--color-text-secondary)]">
                    2FA está activo. Guarda tus códigos de recuperación en un lugar seguro.
                </div>

                @if (is_array(auth()->user()->two_factor_recovery_codes) && count(auth()->user()->two_factor_recovery_codes) > 0)
                    <div class="mt-4 grid gap-2 rounded-md border border-[var(--color-border-default)] p-3">
                        <p class="text-sm font-medium">Códigos de recuperación</p>
                        <div class="grid gap-1 sm:grid-cols-2">
                            @foreach (auth()->user()->two_factor_recovery_codes as $recoveryCode)
                                <code class="rounded bg-[var(--color-bg-surface-alt)] px-2 py-1 text-xs">{{ $recoveryCode }}</code>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('app.settings.two-factor.destroy') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="disable_two_factor_password" class="block text-sm font-medium text-[var(--color-text-secondary)]">Contraseña actual</label>
                        <input id="disable_two_factor_password" type="password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="current_password" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" data-confirm="Desactivar 2FA reducirá la protección de tu cuenta." class="w-full rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-[var(--color-danger)] transition hover:bg-red-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] sm:w-auto">
                            Desactivar 2FA
                        </button>
                    </div>
                </form>
            @else
                <form method="POST" action="{{ route('app.settings.two-factor.start') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto]">
                    @csrf
                    <div>
                        <label for="enable_two_factor_password" class="block text-sm font-medium text-[var(--color-text-secondary)]">Contraseña actual</label>
                        <input id="enable_two_factor_password" type="password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" aria-describedby="current_password-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        <x-ui.field-error field="current_password" />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-2 text-sm font-semibold text-[var(--color-text-primary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] sm:w-auto">
                            Preparar 2FA
                        </button>
                    </div>
                </form>
            @endif
        </section>

        <div class="mt-8">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-[var(--color-text-muted)]">Correo</p>
                    <h2 class="mt-2 text-lg font-semibold">Correo de soporte</h2>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Una cuenta por empresa para recibir solicitudes y enviar respuestas con marcador visible.
                    </p>
                </div>
                @if ($mailAccount)
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" form="mail-test-form" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-semibold text-[var(--color-text-primary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            Probar conexión
                        </button>
                        <button type="submit" form="mail-sync-form" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-semibold text-[var(--color-text-primary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            Revisar correo ahora
                        </button>
                    </div>
                @endif
            </div>

            @if ($mailAccount)
                <dl class="mt-4 grid gap-3 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-[var(--color-text-muted)]">Cuenta activa</dt>
                        <dd class="mt-1 break-all font-medium text-[var(--color-text-primary)]">{{ $mailAccount->from_email }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-text-muted)]">Última sincronización</dt>
                        <dd class="mt-1 font-medium text-[var(--color-text-primary)]">{{ $mailAccount->last_sync_at?->diffForHumans() ?? 'Nunca' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[var(--color-text-muted)]">Último UID</dt>
                        <dd class="mt-1 font-medium text-[var(--color-text-primary)]">{{ $mailAccount->last_uid ? 'UID '.$mailAccount->last_uid : 'Sin UID registrado' }}</dd>
                    </div>
                </dl>
            @endif
        </div>

        <form method="POST" action="{{ url('/app/settings/mail') }}" class="mt-6 grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
            @csrf

            <input type="hidden" name="provider" value="imap_smtp">

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="from_name" class="block text-sm font-medium text-[var(--color-text-secondary)]">Nombre visible</label>
                    <input id="from_name" name="from_name" autocomplete="off" value="{{ old('from_name', $mailAccount?->from_name) }}" @error('from_name') aria-invalid="true" aria-describedby="from_name-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="from_name" />
                </div>

                <div>
                    <label for="from_email" class="block text-sm font-medium text-[var(--color-text-secondary)]">Correo de soporte</label>
                    <input id="from_email" type="email" name="from_email" inputmode="email" autocomplete="off" spellcheck="false" value="{{ old('from_email', $mailAccount?->from_email) }}" required @error('from_email') aria-invalid="true" aria-describedby="from_email-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="from_email" />
                </div>
            </div>

            <div class="grid gap-4 border-t border-[var(--color-border-default)] pt-5 md:grid-cols-[1fr_8rem_10rem]">
                <div>
                    <label for="host_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Servidor IMAP</label>
                    <input id="host_imap" name="host_imap" inputmode="url" autocomplete="off" spellcheck="false" value="{{ old('host_imap', $mailAccount?->host_imap) }}" required @error('host_imap') aria-invalid="true" aria-describedby="host_imap-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="host_imap" />
                </div>

                <div>
                    <label for="port_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Puerto</label>
                    <input id="port_imap" type="number" name="port_imap" inputmode="numeric" min="1" max="65535" value="{{ old('port_imap', $mailAccount?->port_imap ?? 993) }}" required @error('port_imap') aria-invalid="true" aria-describedby="port_imap-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="port_imap" />
                </div>

                <div>
                    <label for="security_imap" class="block text-sm font-medium text-[var(--color-text-secondary)]">Seguridad</label>
                    <select id="security_imap" name="security_imap" required @error('security_imap') aria-invalid="true" aria-describedby="security_imap-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (['ssl' => 'SSL', 'tls' => 'TLS', 'starttls' => 'STARTTLS', 'none' => 'Ninguna'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('security_imap', $mailAccount?->security_imap ?? 'ssl') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="security_imap" />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-[1fr_8rem_10rem]">
                <div>
                    <label for="host_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Servidor SMTP</label>
                    <input id="host_smtp" name="host_smtp" inputmode="url" autocomplete="off" spellcheck="false" value="{{ old('host_smtp', $mailAccount?->host_smtp) }}" required @error('host_smtp') aria-invalid="true" aria-describedby="host_smtp-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="host_smtp" />
                </div>

                <div>
                    <label for="port_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Puerto</label>
                    <input id="port_smtp" type="number" name="port_smtp" inputmode="numeric" min="1" max="65535" value="{{ old('port_smtp', $mailAccount?->port_smtp ?? 587) }}" required @error('port_smtp') aria-invalid="true" aria-describedby="port_smtp-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="port_smtp" />
                </div>

                <div>
                    <label for="security_smtp" class="block text-sm font-medium text-[var(--color-text-secondary)]">Seguridad</label>
                    <select id="security_smtp" name="security_smtp" required @error('security_smtp') aria-invalid="true" aria-describedby="security_smtp-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        @foreach (['tls' => 'TLS', 'starttls' => 'STARTTLS', 'ssl' => 'SSL', 'none' => 'Ninguna'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('security_smtp', $mailAccount?->security_smtp ?? 'tls') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-ui.field-error field="security_smtp" />
                </div>
            </div>

            <div class="grid gap-4 border-t border-[var(--color-border-default)] pt-5 md:grid-cols-3">
                <div>
                    <label for="username" class="block text-sm font-medium text-[var(--color-text-secondary)]">Usuario</label>
                    <input id="username" name="username" autocomplete="off" spellcheck="false" value="{{ old('username', $mailAccount?->username) }}" @error('username') aria-invalid="true" aria-describedby="username-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="username" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-[var(--color-text-secondary)]">Contraseña</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" placeholder="{{ $mailAccount ? 'Mantener actual' : '' }}" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="password" />
                </div>

                <div>
                    <label for="folder_in" class="block text-sm font-medium text-[var(--color-text-secondary)]">Carpeta entrada</label>
                    <input id="folder_in" name="folder_in" autocomplete="off" spellcheck="false" value="{{ old('folder_in', $mailAccount?->folder_in ?? 'INBOX') }}" required @error('folder_in') aria-invalid="true" aria-describedby="folder_in-error" @enderror class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    <x-ui.field-error field="folder_in" />
                </div>
            </div>

            <label class="flex items-start gap-3 rounded-md bg-[var(--color-bg-surface-alt)] p-3 text-sm">
                <input type="checkbox" name="auto_reply_enabled" value="1" @checked(old('auto_reply_enabled', $mailAccount?->auto_reply_enabled ?? true)) class="mt-0.5 size-4 rounded border-[var(--color-border-default)] text-[var(--color-action-primary)] focus-visible:ring-[var(--color-focus)]">
                <span>
                    <span class="block font-medium">Enviar confirmación automática de recibido</span>
                    <span class="mt-1 block text-[var(--color-text-secondary)]">Se usará cuando la ingesta de correo cree un ticket nuevo.</span>
                </span>
            </label>

            <div class="flex flex-wrap justify-end gap-3">
                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                    Guardar correo
                </button>
            </div>
        </form>

        @if ($mailAccount)
            <form id="mail-test-form" method="POST" action="{{ route('app.settings.mail.test') }}" class="hidden">
                @csrf
            </form>
            <form id="mail-sync-form" method="POST" action="{{ route('app.settings.mail.sync') }}" class="hidden">
                @csrf
            </form>
        @endif
    </section>
</x-layouts.app-shell>
