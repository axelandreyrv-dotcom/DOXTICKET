<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'App | '.config('app.name', 'DoxTicket') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#main-content" class="skip-link">Saltar al contenido</a>
        <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 py-4 sm:px-6">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--color-border-default)] pb-3">
                <div class="min-w-0">
                    <a href="{{ url('/app/dashboard') }}" class="block text-sm font-semibold text-[var(--color-text-primary)]">DoxTicket</a>
                    @isset($subtitle)
                        <p class="mt-0.5 truncate text-xs text-[var(--color-text-muted)]">{{ $subtitle }}</p>
                    @endisset
                </div>

                <nav class="flex items-center gap-1 text-sm">
                    <a href="{{ url('/app/dashboard') }}" class="rounded-md px-3 py-2 text-[var(--color-text-secondary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        Dashboard
                    </a>
                    <a href="{{ url('/app/tickets') }}" class="rounded-md px-3 py-2 text-[var(--color-text-secondary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        Tickets
                    </a>
                    <a href="{{ url('/app/companies') }}" class="rounded-md px-3 py-2 text-[var(--color-text-secondary)] transition hover:bg-[var(--color-bg-surface-alt)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                        Empresa
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
                {{ $slot }}
            </main>

            <footer class="border-t border-[var(--color-border-default)] py-4 text-xs text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </div>
    </body>
</html>
