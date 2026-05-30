<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Login | {{ config('app.name', 'DoxTicket') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-md flex-col justify-center px-6 py-12">
            <p class="text-lg font-semibold tracking-normal">DoxTicket</p>
            <h1 class="mt-8 text-3xl font-semibold tracking-normal">Login pendiente</h1>
            <p class="mt-4 text-base leading-7 text-[var(--color-text-secondary)]">
                La autenticacion centralizada se implementara en la fase de auth y multiempresa.
            </p>
            <footer class="mt-12 border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>

