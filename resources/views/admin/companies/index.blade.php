<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Empresas | {{ config('app.name', 'DoxTicket') }}</title>
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
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Empresas</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Vista global de tenants de la instalación. Los conteos se calculan sin depender de la empresa activa del usuario.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Volver al admin
                    </a>
                    <a href="{{ route('admin.companies.create') }}" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Nueva empresa
                    </a>
                </div>
            </header>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-text-secondary)]" role="status" aria-live="polite">
                    {{ session('status') }}
                </div>
            @endif

            <section aria-labelledby="companies-heading" class="py-6">
                <h2 id="companies-heading" class="sr-only">Listado de empresas</h2>

                <div class="overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                    <div class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.06em] text-[var(--color-text-muted)] sm:grid-cols-[minmax(0,1fr)_7rem_7rem_8rem_minmax(0,1fr)_15rem] sm:px-5">
                        <span>Empresa</span>
                        <span>Estado</span>
                        <span>Miembros</span>
                        <span>Tickets</span>
                        <span>Correo</span>
                        <span>Acciones</span>
                    </div>

                    @forelse ($companies as $company)
                        @php
                            $mailAccount = $mailAccounts->get($company->id);
                            $statusLabel = match ($company->status) {
                                'active' => 'Activa',
                                'disabled' => 'Desactivada',
                                'archived' => 'Archivada',
                                default => ucfirst((string) $company->status),
                            };
                        @endphp

                        <article class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-4 text-sm last:border-b-0 sm:grid-cols-[minmax(0,1fr)_7rem_7rem_8rem_minmax(0,1fr)_15rem] sm:items-center sm:px-5">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold">{{ $company->name }}</h3>
                                <p class="mt-1 truncate font-mono text-xs text-[var(--color-text-muted)]">{{ $company->slug }}</p>
                            </div>

                            <span @class([
                                'w-fit rounded-md px-2 py-1 text-xs font-semibold uppercase tracking-[0.06em]',
                                'bg-[var(--color-success-bg)] text-[var(--color-success)]' => $company->status === 'active',
                                'bg-[#fbf3db] text-[var(--color-warning)]' => $company->status === 'disabled',
                                'bg-[#fdebec] text-[var(--color-danger)]' => $company->status === 'archived',
                            ])>
                                {{ $statusLabel }}
                            </span>

                            <span class="font-mono tabular-nums text-[var(--color-text-secondary)]">
                                {{ $membershipCounts->get($company->id, 0) }} miembros
                            </span>

                            <span class="font-mono tabular-nums text-[var(--color-text-secondary)]">
                                {{ $ticketCounts->get($company->id, 0) }} tickets
                            </span>

                            <div class="min-w-0">
                                @if ($mailAccount)
                                    <p class="truncate text-[var(--color-text-primary)]">{{ $mailAccount->from_email }}</p>
                                    <p class="mt-1 truncate text-xs text-[var(--color-text-muted)]">{{ $mailAccount->provider }}</p>
                                @else
                                    <p class="text-[var(--color-text-muted)]">Sin correo activo</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.companies.edit', $company) }}" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                    Editar
                                </a>

                                @if ($company->status === 'active')
                                    <form method="POST" action="{{ route('admin.companies.status', $company) }}" data-confirm="Cambiar el estado de esta empresa puede afectar el acceso operativo. ¿Continuar?">
                                        @csrf
                                        <input type="hidden" name="status" value="disabled">
                                        <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                            Desactivar
                                        </button>
                                    </form>
                                @elseif ($company->status === 'disabled')
                                    <form method="POST" action="{{ route('admin.companies.status', $company) }}" data-confirm="Cambiar el estado de esta empresa puede afectar el acceso operativo. ¿Continuar?">
                                        @csrf
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                            Activar
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" data-confirm="Eliminar esta empresa oculta el tenant y bloquea su acceso operativo. Los datos se conservan para auditoria. ¿Continuar?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-[#f3c7c7] px-2.5 py-1.5 text-xs font-medium text-[var(--color-danger)] hover:border-[var(--color-danger)] hover:bg-[#fdebec] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="px-4 py-4 text-sm text-[var(--color-text-secondary)] sm:px-5">
                            Todavía no hay empresas registradas.
                        </p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $companies->links() }}
                </div>
            </section>

            <footer class="mt-auto border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>

        <dialog id="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message" class="w-[min(92vw,28rem)] rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-0 text-[var(--color-text-primary)] backdrop:bg-slate-950/25">
            <div class="p-5">
                <h2 id="confirm-dialog-title" class="text-base font-semibold">Confirmar acción</h2>
                <p id="confirm-dialog-message" class="mt-2 text-sm leading-6 text-[var(--color-text-secondary)]"></p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" data-confirm-cancel class="rounded-md border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Cancelar
                    </button>
                    <button type="button" data-confirm-accept class="rounded-md bg-[var(--color-danger)] px-3 py-2 text-sm font-semibold text-white hover:bg-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Continuar
                    </button>
                </div>
            </div>
        </dialog>
    </body>
</html>
