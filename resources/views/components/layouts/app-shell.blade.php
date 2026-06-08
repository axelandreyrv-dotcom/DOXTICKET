<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'App | '.config('app.name', 'DoxTicket') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/doxticket.svg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#main-content" class="skip-link">Saltar al contenido</a>
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-4 sm:px-6">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--color-border-default)] pb-3">
                <div class="min-w-0">
                    <a href="{{ route('app.tickets.index') }}" class="flex min-w-0 items-center gap-2 text-sm font-semibold text-[var(--color-text-primary)]">
                        <img src="{{ asset('brand/doxticket.svg') }}" alt="" width="28" height="28" class="size-7 shrink-0" aria-hidden="true">
                        <span>DoxTicket</span>
                    </a>
                    @isset($subtitle)
                        <p class="mt-0.5 truncate text-xs text-[var(--color-text-muted)]">{{ $subtitle }}</p>
                    @endisset
                </div>

                @php
                    $navLinkClass = 'rounded-md px-3 py-2 transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]';
                    $inactiveNavClass = $navLinkClass.' text-[var(--color-text-secondary)]';
                    $activeNavClass = $navLinkClass.' bg-[var(--color-bg-surface-alt)] font-medium text-[var(--color-text-primary)]';
                    $isActive = fn (string $pattern): bool => request()->is($pattern);
                @endphp

                <nav aria-label="Navegación principal" class="flex w-full flex-wrap items-center gap-1 text-sm sm:w-auto">
                    <a href="{{ route('app.tickets.index') }}" @if ($isActive('app/tickets*')) aria-current="page" @endif class="{{ $isActive('app/tickets*') ? $activeNavClass : $inactiveNavClass }}">
                        Tickets
                    </a>
                    <a href="{{ route('app.activity.index') }}" @if ($isActive('app/activity')) aria-current="page" @endif class="{{ $isActive('app/activity') ? $activeNavClass : $inactiveNavClass }}">
                        Actividad
                    </a>
                    <form method="POST" action="{{ url('/logout') }}">
                        @csrf
                        <button type="submit" class="rounded-md px-3 py-2 text-[var(--color-text-secondary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                            Salir
                        </button>
                    </form>
                </nav>
            </header>

            <main id="main-content" class="flex-1">
                @php
                    $flashStatus = session('status');
                    $flashMessage = is_string($flashStatus)
                        ? match (true) {
                            str_starts_with($flashStatus, 'ticket-created-') => 'Ticket creado.',
                            $flashStatus === 'ticket-assigned' => 'Ticket asignado.',
                            $flashStatus === 'note-added' => 'Nota interna agregada.',
                            $flashStatus === 'attachment-added' => 'Adjunto agregado.',
                            $flashStatus === 'reply-sent' => 'Respuesta enviada.',
                            $flashStatus === 'ticket-merged' => 'Ticket fusionado.',
                            $flashStatus === 'status-updated' => 'Estado actualizado.',
                            $flashStatus === 'properties-updated' => 'Propiedades actualizadas.',
                            $flashStatus === 'mail-settings-saved' => 'Configuración guardada.',
                            $flashStatus === 'mail-test-ok' => 'Conexión IMAP/SMTP verificada.',
                            $flashStatus === 'mail-oauth-connected' => 'Cuenta OAuth conectada.',
                            $flashStatus === 'two-factor-started' => '2FA preparado. Confirma el código para activarlo.',
                            $flashStatus === 'two-factor-enabled' => '2FA activado.',
                            $flashStatus === 'two-factor-disabled' => '2FA desactivado.',
                            $flashStatus === 'kb-created' => 'Artículo guardado.',
                            $flashStatus === 'kb-updated' => 'Artículo actualizado.',
                            $flashStatus === 'kb-archived' => 'Artículo archivado.',
                            $flashStatus === 'kb-deleted' => 'Artículo eliminado.',
                            default => 'Cambios guardados.',
                        }
                        : null;
                @endphp

                @if ($flashMessage !== null)
                    <div data-app-flash-status role="status" aria-live="polite" class="mt-4 rounded-md border border-[var(--color-success-border)] bg-[var(--color-success-bg)] px-4 py-3 text-sm text-[var(--color-success)]">
                        {{ $flashMessage }}
                    </div>
                @endif

                {{ $slot }}
            </main>

            <footer class="border-t border-[var(--color-border-default)] py-4 text-xs text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </div>

        <dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message" class="w-[min(92vw,28rem)] rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-0 text-[var(--color-text-primary)] backdrop:bg-slate-950/25">
            <div class="p-5">
                <h2 id="confirm-dialog-title" class="text-base font-semibold">Confirmar acción</h2>
                <p id="confirm-dialog-message" class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]"></p>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    <button type="button" data-confirm-cancel class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-border-strong)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        Cancelar
                    </button>
                    <button type="button" data-confirm-accept class="rounded-md bg-[var(--color-danger)] px-3 py-2 text-sm font-semibold text-white transition hover:bg-red-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                        Confirmar
                    </button>
                </div>
            </div>
        </dialog>
    </body>
</html>
