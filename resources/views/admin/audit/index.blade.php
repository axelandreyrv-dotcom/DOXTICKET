<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Auditoria | {{ config('app.name', 'DoxTicket') }}</title>
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
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Auditoria</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Historial global de eventos administrativos y operativos. Los metadatos sensibles se redactan antes de mostrarse.
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                    Volver al admin
                </a>
            </header>

            <section aria-labelledby="audit-heading" class="py-6">
                <h2 id="audit-heading" class="sr-only">Listado de auditoria</h2>

                <form method="GET" action="{{ route('admin.audit.index') }}" class="mb-4 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4" aria-labelledby="audit-filters-heading">
                    <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 id="audit-filters-heading" class="text-sm font-semibold">Filtros</h2>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('admin.audit.export', request()->query()) }}" class="text-sm font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                Exportar CSV
                            </a>
                            <a href="{{ route('admin.audit.index') }}" class="text-sm font-medium text-[var(--color-text-secondary)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                Limpiar
                            </a>
                        </div>
                    </div>

                    <label class="mb-3 grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                        Buscar
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Accion, empresa, actor o sujeto..." autocomplete="off" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                    </label>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <label class="grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                            Accion
                            <select name="action" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                                <option value="">Todas</option>
                                @foreach ($actions as $action)
                                    <option value="{{ $action }}" @selected($filters['action'] === $action)>{{ $action }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                            Empresa
                            <select name="company_id" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                                <option value="">Todas</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected($filters['company_id'] === (string) $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                            Actor
                            <select name="actor_user_id" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                                <option value="">Todos</option>
                                @foreach ($actors as $actor)
                                    <option value="{{ $actor->id }}" @selected($filters['actor_user_id'] === (string) $actor->id)>{{ $actor->name }} · {{ $actor->email }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                            Desde
                            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                        </label>

                        <label class="grid gap-1 text-sm font-medium text-[var(--color-text-secondary)]">
                            Hasta
                            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm text-[var(--color-text-primary)] focus:border-[var(--color-action-primary)] focus:outline-none focus:ring-2 focus:ring-[var(--color-action-primary)]/20">
                        </label>
                    </div>

                    <button type="submit" class="mt-4 rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Filtrar
                    </button>
                </form>

                <div class="overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                    <div class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.06em] text-[var(--color-text-muted)] sm:grid-cols-[10rem_11rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.2fr)] sm:px-5">
                        <span>Fecha</span>
                        <span>Accion</span>
                        <span>Empresa</span>
                        <span>Actor</span>
                        <span>Detalle</span>
                    </div>

                    @forelse ($logs as $log)
                        <article class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-4 text-sm last:border-b-0 sm:grid-cols-[10rem_11rem_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.2fr)] sm:px-5">
                            <time class="font-mono text-xs text-[var(--color-text-secondary)]" datetime="{{ $log['created_at']?->toIso8601String() }}">
                                {{ $log['created_at']?->format('Y-m-d H:i') ?? 'Sin fecha' }}
                            </time>

                            <span class="font-mono text-xs font-semibold text-[var(--color-text-primary)] break-words">
                                {{ $log['action'] }}
                            </span>

                            <span class="min-w-0 text-[var(--color-text-secondary)] break-words">
                                {{ $log['company'] }}
                            </span>

                            <span class="min-w-0 text-[var(--color-text-secondary)] break-words">
                                {{ $log['actor'] }}
                            </span>

                            <div class="min-w-0">
                                <p class="font-medium text-[var(--color-text-primary)]">{{ $log['subject'] }}</p>
                                @if (count($log['meta']) > 0)
                                    <pre class="mt-2 max-h-40 overflow-auto rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-page)] p-3 font-mono text-xs leading-5 text-[var(--color-text-secondary)]">{{ json_encode($log['meta'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <p class="mt-2 text-xs text-[var(--color-text-muted)]">Sin metadatos.</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="px-4 py-4 text-sm text-[var(--color-text-secondary)] sm:px-5">
                            Todavia no hay eventos de auditoria registrados.
                        </p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </section>

            <footer class="mt-auto border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>
