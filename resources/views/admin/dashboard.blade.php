<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin | {{ config('app.name', 'DoxTicket') }}</title>
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
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Estado del sistema</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Resumen operativo de la instalación self-hosted. Los errores visibles no exponen secretos.
                    </p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-sm text-[var(--color-text-muted)]">Versión instalada</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-[var(--color-text-primary)]">{{ $version }}</p>
                </div>
            </header>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-text-secondary)]">
                    {{ session('status') }}
                </div>
            @endif

            <section aria-labelledby="summary-heading" class="grid gap-3 py-6 sm:grid-cols-2 lg:grid-cols-4">
                <h2 id="summary-heading" class="sr-only">Resumen admin</h2>
                @foreach ($summary as $label => $value)
                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">
                            {{ str_replace('_', ' ', $label) }}
                        </p>
                        <p class="mt-2 font-mono text-2xl font-semibold tabular-nums">{{ $value }}</p>
                    </article>
                @endforeach
            </section>

            <section aria-labelledby="admin-sections-heading" class="pb-8">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Administración</p>
                        <h2 id="admin-sections-heading" class="mt-2 text-lg font-semibold">Empresas</h2>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Revisa tenants, estado operativo, correo activo, miembros y tickets desde el portal admin.
                        </p>
                        <a href="{{ route('admin.companies.index') }}" class="mt-4 inline-flex rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Abrir empresas
                        </a>
                    </article>

                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Acceso</p>
                        <h2 class="mt-2 text-lg font-semibold">Usuarios</h2>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Revisa usuarios globales, superadmins y membresías por empresa sin depender del tenant activo.
                        </p>
                        <a href="{{ route('admin.users.index') }}" class="mt-4 inline-flex rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Abrir usuarios
                        </a>
                    </article>

                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Instalación</p>
                        <h2 class="mt-2 text-lg font-semibold">Configuración</h2>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Revisa versión, repositorio de releases, telemetría y correo global sin exponer secretos.
                        </p>
                        <a href="{{ route('admin.settings.index') }}" class="mt-4 inline-flex rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Abrir configuración
                        </a>
                    </article>

                    <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                        <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Trazabilidad</p>
                        <h2 class="mt-2 text-lg font-semibold">Auditoría</h2>
                        <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                            Revisa eventos operativos y administrativos con metadatos redactados antes de mostrarse.
                        </p>
                        <a href="{{ route('admin.audit.index') }}" class="mt-4 inline-flex rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Abrir auditoría
                        </a>
                    </article>
                </div>
            </section>

            <section aria-labelledby="updates-heading" class="pb-8">
                <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Updates</p>
                            <h2 id="updates-heading" class="mt-2 text-lg font-semibold">
                                @if (($updateStatus['update_available'] ?? false) === true)
                                    Nueva versión estable disponible
                                @elseif (($updateStatus['error'] ?? null) !== null)
                                    No se pudo revisar la versión
                                @else
                                    Versión estable revisada
                                @endif
                            </h2>
                            <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                                @if (($updateStatus['update_available'] ?? false) === true)
                                    Hay una release estable {{ $updateStatus['latest_version'] ?? '' }} para esta instalación. Revisa backups antes de actualizar.
                                @elseif (($updateStatus['error'] ?? null) !== null)
                                    {{ $updateStatus['error'] }}
                                @elseif ($updateStatus)
                                    No hay una versión estable nueva registrada.
                                @else
                                    Aún no hay resultado local del chequeo diario de GitHub Releases.
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 text-left sm:text-right">
                            <form method="POST" action="{{ route('admin.updates.check') }}">
                                @csrf
                                <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                    Revisar actualizaciones
                                </button>
                            </form>
                            @if (($updateStatus['latest_version'] ?? null) !== null)
                                <span class="font-mono text-sm font-semibold">{{ $updateStatus['latest_version'] }}</span>
                            @endif
                            @if (($updateStatus['release_url'] ?? null) !== null)
                                <a href="{{ $updateStatus['release_url'] }}" rel="noreferrer" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                    Ver release
                                </a>
                            @endif
                        </div>
                    </div>
                </article>

            </section>

            <section aria-labelledby="telemetry-heading" class="pb-8">
                <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Privacidad</p>
                            <h2 id="telemetry-heading" class="mt-2 text-lg font-semibold">Telemetría</h2>
                            <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                                Estado: <span class="font-semibold text-[var(--color-text-primary)]">{{ $telemetry['enabled'] ? 'Activa' : 'Apagada' }}</span>.
                                No envía nombres, correos, asuntos, cuerpos, adjuntos ni secretos.
                            </p>
                            <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                                Cuando se active, solo podrá reportar versión instalada, entorno técnico básico y eventos anónimos de salud.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.telemetry.update') }}" class="flex flex-col gap-2 text-left sm:text-right">
                            @csrf
                            <input type="hidden" name="telemetry_enabled" value="{{ $telemetry['enabled'] ? '0' : '1' }}">
                            <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                {{ $telemetry['enabled'] ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>
                    </div>
                </article>
            </section>

            <section aria-labelledby="backups-heading" class="pb-8">
                <article class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Backups</p>
                            <h2 id="backups-heading" class="mt-2 text-lg font-semibold">Último backup</h2>
                            <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                                @if ($backup['latest'])
                                    Backup exitoso en {{ $backup['latest']->destination }} · {{ $backup['latest_size'] }}.
                                @else
                                    Sin backup exitoso registrado. Configura y verifica backups antes de actualizar.
                                @endif
                            </p>
                            <p class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                                El backup local actual guarda base de datos y manifiesto. Para reinstalar, también conserva y restaura manualmente `storage/app/private` y `.env`. V1 no importa backups desde la UI.
                            </p>
                        </div>

                        <div class="flex flex-col gap-2 text-left sm:text-right">
                            <form method="POST" action="{{ route('admin.backups.store') }}">
                                @csrf
                                <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                    Ejecutar backup
                                </button>
                            </form>
                            @if ($backup['latest']?->finished_at)
                                <span class="font-mono text-sm font-semibold">{{ $backup['latest']->finished_at->diffForHumans() }}</span>
                            @endif
                            @if ($backup['rollback_available'])
                                <form method="POST" action="{{ route('admin.rollback.store') }}">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                        Rollback
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled class="cursor-not-allowed rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-muted)] opacity-70">
                                    Rollback no disponible
                                </button>
                            @endif
                        </div>
                    </div>
                </article>

                <div class="mt-4 overflow-hidden rounded-lg border border-[var(--color-border-default)]">
                    <div class="border-b border-[var(--color-border-default)] px-4 py-3 sm:px-5">
                        <h3 class="text-sm font-semibold">Historial de backups</h3>
                    </div>

                    @forelse ($backup['recent'] as $run)
                        <article class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-sm last:border-b-0 sm:grid-cols-[8rem_7rem_7rem_1fr] sm:items-center sm:px-5">
                            <span @class([
                                'w-fit rounded-md px-2 py-1 text-xs font-semibold uppercase tracking-[0.06em]',
                                'bg-[var(--color-success-bg)] text-[var(--color-success)]' => $run->status === 'succeeded',
                                'bg-[#fbf3db] text-[var(--color-warning)]' => $run->status === 'running',
                                'bg-[#fdebec] text-[var(--color-danger)]' => $run->status === 'failed',
                                'bg-[var(--color-bg-surface-alt)] text-[var(--color-text-muted)]' => $run->status === 'pruned',
                            ])>
                                {{ $run->status }}
                            </span>
                            <span class="font-mono text-xs text-[var(--color-text-secondary)]">{{ $run->destination }}</span>
                            <span class="font-mono text-xs text-[var(--color-text-secondary)]">{{ $backupStatus->humanSize($run->size_bytes) }}</span>
                            <div class="min-w-0">
                                <p class="truncate text-[var(--color-text-secondary)]">
                                    @if ($run->status === 'failed' && $run->error)
                                        {{ $run->error }}
                                    @elseif ($run->finished_at)
                                        Finalizado {{ $run->finished_at->diffForHumans() }}
                                    @else
                                        Iniciado {{ $run->started_at?->diffForHumans() ?? $run->created_at->diffForHumans() }}
                                    @endif
                                </p>
                            </div>
                        </article>
                    @empty
                        <p class="px-4 py-4 text-sm text-[var(--color-text-secondary)] sm:px-5">
                            Todavía no hay ejecuciones de backup registradas.
                        </p>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="health-heading" class="pb-8">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 id="health-heading" class="text-lg font-semibold">Health checks</h2>
                    <a href="{{ route('admin.health') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Abrir health
                    </a>
                </div>

                <div class="overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                    @foreach ($checks as $check)
                        <article class="grid gap-3 border-b border-[var(--color-border-default)] p-4 last:border-b-0 sm:grid-cols-[12rem_8rem_1fr] sm:items-center">
                            <h3 class="font-medium">{{ $check->label }}</h3>
                            <span @class([
                                'w-fit rounded-md px-2 py-1 text-xs font-semibold uppercase tracking-[0.06em]',
                                'bg-[var(--color-success-bg)] text-[var(--color-success)]' => $check->status === 'ok',
                                'bg-[#fbf3db] text-[var(--color-warning)]' => $check->status === 'warning',
                                'bg-[#fdebec] text-[var(--color-danger)]' => $check->status === 'failed',
                            ])>
                                {{ $check->status }}
                            </span>
                            <p class="min-w-0 text-sm leading-6 text-[var(--color-text-secondary)] break-words">{{ $check->message }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <footer class="mt-auto border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>
