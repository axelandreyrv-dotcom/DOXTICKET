<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin | {{ config('app.name', 'DoxTicket') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-3xl flex-col justify-center px-6 py-12">
            <p class="text-sm font-medium text-[var(--color-text-muted)]">DoxTicket admin</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Panel superadmin pendiente</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-[var(--color-text-secondary)]">
                Aqui viviran health, backups, version instalada, update checks, rollback, auditoria y telemetria.
            </p>
            <footer class="mt-12 border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>

