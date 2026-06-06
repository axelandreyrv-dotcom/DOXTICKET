<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'DoxTicket') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/doxticket.svg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#main-content" class="skip-link">Saltar al contenido</a>
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-5 py-5 sm:px-8">
            <header class="flex items-center justify-between border-b border-[var(--color-border-default)] pb-4">
                <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2 text-sm font-semibold tracking-normal text-[var(--color-text-primary)]">
                    <img src="{{ asset('brand/doxticket.svg') }}" alt="" width="28" height="28" class="size-7 shrink-0" aria-hidden="true">
                    <span>DoxTicket</span>
                </a>
            </header>

            <main id="main-content" class="flex flex-1 flex-col">
                {{ $slot }}
            </main>

            <footer class="border-t border-[var(--color-border-default)] py-5 text-xs text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </div>
    </body>
</html>
