<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Configuración | {{ config('app.name', 'DoxTicket') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/doxticket.svg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#admin-main" class="skip-link">Saltar al contenido</a>

        <main id="admin-main" class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-8">
            <header class="flex flex-col gap-5 border-b border-[var(--color-border-default)] pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex min-w-0 items-center gap-2">
                        <img src="{{ asset('brand/doxticket.svg') }}" alt="" width="32" height="32" class="size-8 shrink-0" aria-hidden="true">
                        <p class="text-sm font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Superadmin</p>
                    </div>
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Configuración</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Parámetros públicos de la instalación. Esta vista no muestra secretos ni credenciales.
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                    Volver al admin
                </a>
            </header>

            @if (session('status'))
                <div role="status" aria-live="polite" class="mt-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm font-medium text-[var(--color-text-primary)]">
                    {{ session('status') }}
                </div>
            @endif

            <section aria-labelledby="installation-heading" class="grid gap-3 py-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.55fr)]">
                <form method="POST" action="{{ route('admin.settings.update') }}" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    @csrf
                    <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Instalación</p>
                    <h2 id="installation-heading" class="mt-2 text-lg font-semibold">Sistema y proyecto</h2>
                    <div class="mt-5 grid gap-4">
                        <div>
                            <label for="public_url" class="text-sm font-medium text-[var(--color-text-primary)]">URL pública</label>
                            <input
                                id="public_url"
                                name="public_url"
                                type="url"
                                value="{{ old('public_url', $form['public_url']) }}"
                                aria-invalid="{{ $errors->has('public_url') ? 'true' : 'false' }}"
                                @if ($errors->has('public_url')) aria-describedby="public_url-error" @endif
                                class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                placeholder="https://helpdesk.example.com"
                            >
                            @error('public_url')
                                <p id="public_url-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="github_repository" class="text-sm font-medium text-[var(--color-text-primary)]">Repositorio de releases</label>
                            <input
                                id="github_repository"
                                name="github_repository"
                                type="text"
                                value="{{ old('github_repository', $form['github_repository']) }}"
                                aria-invalid="{{ $errors->has('github_repository') ? 'true' : 'false' }}"
                                @if ($errors->has('github_repository')) aria-describedby="github_repository-error" @endif
                                class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                placeholder="doxsuite/doxticket"
                            >
                            @error('github_repository')
                                <p id="github_repository-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="border-t border-[var(--color-border-default)] pt-4">
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Backups</p>
                            <h3 class="mt-2 text-base font-semibold">Política de backups</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <input type="hidden" name="backup_schedule_enabled" value="0">
                                    <label for="backup_schedule_enabled" class="flex items-start gap-3 rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface-alt)] px-3 py-3">
                                        <input
                                            id="backup_schedule_enabled"
                                            name="backup_schedule_enabled"
                                            type="checkbox"
                                            value="1"
                                            @checked((bool) old('backup_schedule_enabled', $form['backup_schedule_enabled']))
                                            class="mt-1 size-4 rounded border-[var(--color-border-strong)] text-[var(--color-action-primary)] focus:ring-[var(--color-action-primary)]"
                                        >
                                        <span>
                                            <span class="block text-sm font-medium text-[var(--color-text-primary)]">Backup automático</span>
                                            <span class="mt-1 block text-sm leading-6 text-[var(--color-text-secondary)]">Ejecuta un backup local diario cuando el scheduler esté activo.</span>
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <label for="backup_schedule_hour" class="text-sm font-medium text-[var(--color-text-primary)]">Hora del backup</label>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input
                                            id="backup_schedule_hour"
                                            name="backup_schedule_hour"
                                            type="number"
                                            min="0"
                                            max="23"
                                            inputmode="numeric"
                                            value="{{ old('backup_schedule_hour', $form['backup_schedule_hour']) }}"
                                            aria-invalid="{{ $errors->has('backup_schedule_hour') ? 'true' : 'false' }}"
                                            @if ($errors->has('backup_schedule_hour')) aria-describedby="backup_schedule_hour-error" @endif
                                            class="w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        >
                                        <span class="shrink-0 text-sm text-[var(--color-text-secondary)]">0-23</span>
                                    </div>
                                    @error('backup_schedule_hour')
                                        <p id="backup_schedule_hour-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="backup_recent_success_hours" class="text-sm font-medium text-[var(--color-text-primary)]">Backup reciente</label>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input
                                            id="backup_recent_success_hours"
                                            name="backup_recent_success_hours"
                                            type="number"
                                            min="1"
                                            max="168"
                                            inputmode="numeric"
                                            value="{{ old('backup_recent_success_hours', $form['backup_recent_success_hours']) }}"
                                            aria-invalid="{{ $errors->has('backup_recent_success_hours') ? 'true' : 'false' }}"
                                            @if ($errors->has('backup_recent_success_hours')) aria-describedby="backup_recent_success_hours-error" @endif
                                            class="w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        >
                                        <span class="shrink-0 text-sm text-[var(--color-text-secondary)]">horas</span>
                                    </div>
                                    @error('backup_recent_success_hours')
                                        <p id="backup_recent_success_hours-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="backup_retention_days" class="text-sm font-medium text-[var(--color-text-primary)]">Retención local</label>
                                    <div class="mt-2 flex items-center gap-2">
                                        <input
                                            id="backup_retention_days"
                                            name="backup_retention_days"
                                            type="number"
                                            min="1"
                                            max="365"
                                            inputmode="numeric"
                                            value="{{ old('backup_retention_days', $form['backup_retention_days']) }}"
                                            aria-invalid="{{ $errors->has('backup_retention_days') ? 'true' : 'false' }}"
                                            @if ($errors->has('backup_retention_days')) aria-describedby="backup_retention_days-error" @endif
                                            class="w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        >
                                        <span class="shrink-0 text-sm text-[var(--color-text-secondary)]">días</span>
                                    </div>
                                    @error('backup_retention_days')
                                        <p id="backup_retention_days-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-[var(--color-border-default)] pt-4">
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Correo global</p>
                            <h3 class="mt-2 text-base font-semibold">SMTP del sistema</h3>
                            <p class="mt-1 text-sm leading-6 text-[var(--color-text-secondary)]">
                                Se usa para invitaciones, enlaces de contraseña, alertas internas y correos del sistema.
                            </p>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label for="mail_mailer" class="text-sm font-medium text-[var(--color-text-primary)]">Mailer</label>
                                    <select
                                        id="mail_mailer"
                                        name="mail_mailer"
                                        aria-invalid="{{ $errors->has('mail_mailer') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_mailer')) aria-describedby="mail_mailer-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                    >
                                        <option value="log" @selected(old('mail_mailer', $form['mail_mailer']) === 'log')>Log</option>
                                        <option value="smtp" @selected(old('mail_mailer', $form['mail_mailer']) === 'smtp')>SMTP</option>
                                    </select>
                                    @error('mail_mailer')
                                        <p id="mail_mailer-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_from_address" class="text-sm font-medium text-[var(--color-text-primary)]">Remitente del sistema</label>
                                    <input
                                        id="mail_from_address"
                                        name="mail_from_address"
                                        type="email"
                                        value="{{ old('mail_from_address', $form['mail_from_address']) }}"
                                        aria-invalid="{{ $errors->has('mail_from_address') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_from_address')) aria-describedby="mail_from_address-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        placeholder="soporte@example.com"
                                    >
                                    @error('mail_from_address')
                                        <p id="mail_from_address-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_from_name" class="text-sm font-medium text-[var(--color-text-primary)]">Nombre del remitente</label>
                                    <input
                                        id="mail_from_name"
                                        name="mail_from_name"
                                        type="text"
                                        value="{{ old('mail_from_name', $form['mail_from_name']) }}"
                                        aria-invalid="{{ $errors->has('mail_from_name') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_from_name')) aria-describedby="mail_from_name-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        placeholder="DoxTicket"
                                    >
                                    @error('mail_from_name')
                                        <p id="mail_from_name-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_host" class="text-sm font-medium text-[var(--color-text-primary)]">Servidor SMTP</label>
                                    <input
                                        id="mail_host"
                                        name="mail_host"
                                        type="text"
                                        value="{{ old('mail_host', $form['mail_host']) }}"
                                        aria-invalid="{{ $errors->has('mail_host') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_host')) aria-describedby="mail_host-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        placeholder="smtp.gmail.com"
                                    >
                                    @error('mail_host')
                                        <p id="mail_host-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_port" class="text-sm font-medium text-[var(--color-text-primary)]">Puerto SMTP</label>
                                    <input
                                        id="mail_port"
                                        name="mail_port"
                                        type="number"
                                        min="1"
                                        max="65535"
                                        inputmode="numeric"
                                        value="{{ old('mail_port', $form['mail_port']) }}"
                                        aria-invalid="{{ $errors->has('mail_port') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_port')) aria-describedby="mail_port-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                    >
                                    @error('mail_port')
                                        <p id="mail_port-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_encryption" class="text-sm font-medium text-[var(--color-text-primary)]">Seguridad SMTP</label>
                                    <select
                                        id="mail_encryption"
                                        name="mail_encryption"
                                        aria-invalid="{{ $errors->has('mail_encryption') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_encryption')) aria-describedby="mail_encryption-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                    >
                                        <option value="tls" @selected(old('mail_encryption', $form['mail_encryption']) === 'tls')>TLS</option>
                                        <option value="ssl" @selected(old('mail_encryption', $form['mail_encryption']) === 'ssl')>SSL</option>
                                        <option value="none" @selected(old('mail_encryption', $form['mail_encryption']) === 'none')>Sin cifrado</option>
                                    </select>
                                    @error('mail_encryption')
                                        <p id="mail_encryption-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_username" class="text-sm font-medium text-[var(--color-text-primary)]">Usuario SMTP</label>
                                    <input
                                        id="mail_username"
                                        name="mail_username"
                                        type="text"
                                        value="{{ old('mail_username', $form['mail_username']) }}"
                                        aria-invalid="{{ $errors->has('mail_username') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_username')) aria-describedby="mail_username-error" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        placeholder="usuario@example.com"
                                    >
                                    @error('mail_username')
                                        <p id="mail_username-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="mail_password" class="text-sm font-medium text-[var(--color-text-primary)]">Contraseña SMTP</label>
                                    <input
                                        id="mail_password"
                                        name="mail_password"
                                        type="password"
                                        value=""
                                        autocomplete="new-password"
                                        aria-invalid="{{ $errors->has('mail_password') ? 'true' : 'false' }}"
                                        @if ($errors->has('mail_password')) aria-describedby="mail_password-error mail_password-help" @else aria-describedby="mail_password-help" @endif
                                        class="mt-2 w-full rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] outline-none transition focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/20"
                                        placeholder="{{ $form['mail_password_configured'] ? 'Dejar vacío para conservarla' : 'Contraseña o app password' }}"
                                    >
                                    <p id="mail_password-help" class="mt-2 text-sm text-[var(--color-text-secondary)]">
                                        {{ $form['mail_password_configured'] ? 'Contraseña guardada. Si deja este campo vacío, no se cambia.' : 'No hay contraseña SMTP guardada.' }}
                                    </p>
                                    @error('mail_password')
                                        <p id="mail_password-error" role="alert" class="mt-2 text-sm text-[var(--color-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t border-[var(--color-border-default)] pt-4">
                            <p class="text-sm text-[var(--color-text-secondary)]">
                                Versión instalada: <span class="font-mono text-[var(--color-text-primary)]">{{ $settings['version'] }}</span>
                            </p>
                            <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                Guardar configuración
                            </button>
                        </div>
                    </div>
                </form>

                <div class="grid gap-3">
                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Privacidad</p>
                        <h2 class="mt-2 text-lg font-semibold">Telemetría</h2>
                        <p class="mt-4 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Estado: <span class="font-semibold text-[var(--color-text-primary)]">{{ $settings['telemetry_enabled'] ? 'Activa' : 'Apagada' }}</span>.
                            No se envían nombres, correos, asuntos, cuerpos, adjuntos ni secretos.
                        </p>
                        <form method="POST" action="{{ route('admin.telemetry.update') }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="telemetry_enabled" value="{{ $settings['telemetry_enabled'] ? '0' : '1' }}">
                            <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                {{ $settings['telemetry_enabled'] ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </article>

                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Backups</p>
                        <h2 class="mt-2 text-lg font-semibold">Ventana segura</h2>
                        <p class="mt-4 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Backup automático: {{ $form['backup_schedule_enabled'] ? 'Activo' : 'Apagado' }} a las {{ str_pad((string) $form['backup_schedule_hour'], 2, '0', STR_PAD_LEFT) }}:00.
                            Health espera un backup exitoso cada {{ $settings['backup_recent_success_hours'] }} horas.
                            Retención local configurada: {{ $settings['backup_retention_days'] }} días.
                        </p>
                    </article>

                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Correo global</p>
                        <h2 class="mt-2 text-lg font-semibold">SMTP del sistema</h2>
                        <dl class="mt-4 grid gap-3 text-sm">
                            <div class="flex flex-col gap-1">
                                <dt class="text-[var(--color-text-muted)]">Mailer</dt>
                                <dd class="font-mono text-[var(--color-text-primary)]">{{ $settings['mail_mailer'] }}</dd>
                            </div>
                            <div class="flex flex-col gap-1">
                                <dt class="text-[var(--color-text-muted)]">Servidor</dt>
                                <dd class="font-mono break-all text-[var(--color-text-primary)]">{{ $settings['mail_host'] }}</dd>
                            </div>
                            <div class="flex flex-col gap-1">
                                <dt class="text-[var(--color-text-muted)]">Remitente del sistema</dt>
                                <dd class="font-mono break-all text-[var(--color-text-primary)]">{{ $settings['mail_from'] }}</dd>
                            </div>
                            <div class="flex flex-col gap-1">
                                <dt class="text-[var(--color-text-muted)]">Contraseña</dt>
                                <dd class="text-[var(--color-text-primary)]">{{ $settings['mail_password_configured'] ? 'Guardada' : 'Sin guardar' }}</dd>
                            </div>
                        </dl>
                    </article>
                </div>
            </section>

            <footer class="mt-auto border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>
