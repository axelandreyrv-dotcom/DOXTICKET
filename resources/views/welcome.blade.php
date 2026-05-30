<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'DoxTicket') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#main-content" class="skip-link">Saltar al contenido</a>
        <main id="main-content" class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-6 py-8">
            <header class="flex items-center justify-between border-b border-[var(--color-border-default)] pb-5">
                <div>
                    <p class="text-lg font-semibold tracking-normal">DoxTicket</p>
                    <p class="mt-1 text-sm text-[var(--color-text-muted)]">Helpdesk IT self-hosted</p>
                </div>
                <nav class="flex items-center gap-2" aria-label="Accesos principales">
                    <a class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] transition hover:border-[var(--color-action-primary)] hover:text-[var(--color-action-primary)]" href="{{ url('/setup') }}">
                        Setup
                    </a>
                    <a class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)]" href="{{ url('/login') }}">
                        Login
                    </a>
                </nav>
            </header>

            <section class="grid flex-1 items-center gap-10 py-16 lg:grid-cols-[1fr_360px]">
                <div>
                    <p class="text-sm font-medium text-[var(--color-action-primary)]">Open source, AGPLv3</p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight tracking-normal text-[var(--color-text-primary)] md:text-5xl">
                        Tickets de TI en tu propia infraestructura.
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-[var(--color-text-secondary)]">
                        DoxTicket conecta el correo de soporte con una cola clara de tickets, trazabilidad y aislamiento multiempresa.
                    </p>
                </div>

                <aside class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5">
                    <h2 class="text-sm font-semibold text-[var(--color-text-primary)]">Estado de la instalacion</h2>
                    <dl class="mt-5 space-y-4 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Setup</dt>
                            <dd class="font-medium text-[var(--color-warning)]">Pendiente</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Base de datos</dt>
                            <dd class="font-medium text-[var(--color-text-secondary)]">PostgreSQL</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-[var(--color-text-muted)]">Colas</dt>
                            <dd class="font-medium text-[var(--color-text-secondary)]">Redis</dd>
                        </div>
                    </dl>
                </aside>
            </section>

            <footer class="border-t border-[var(--color-border-default)] py-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>
