<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard | {{ config('app.name', 'DoxTicket') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-4xl flex-col px-6 py-8">
            <header class="flex items-center justify-between border-b border-[var(--color-border-default)] pb-5">
                <div>
                    <p class="text-lg font-semibold tracking-normal">DoxTicket</p>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">Dashboard</p>
                </div>
                <a class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)]" href="{{ url('/') }}">
                    Inicio
                </a>
            </header>
            <section class="flex flex-1 flex-col justify-center py-16">
                <h1 class="text-3xl font-semibold tracking-normal">App pendiente</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-[var(--color-text-secondary)]">
                    Aqui se construira el dashboard operativo, lista tipo inbox, tickets y configuracion por empresa.
                </p>
            </section>
            <footer class="border-t border-[var(--color-border-default)] py-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>

