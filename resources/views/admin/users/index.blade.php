<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Usuarios | {{ config('app.name', 'DoxTicket') }}</title>
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
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Usuarios</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Vista global de identidades y membresías. La pertenencia a empresas se mantiene separada del permiso superadmin.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Volver al admin
                    </a>
                    <a href="{{ route('admin.users.invite') }}" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                        Invitar usuario
                    </a>
                </div>
            </header>

            @if (session('status'))
                <div class="mt-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] px-4 py-3 text-sm text-[var(--color-text-secondary)]" role="status" aria-live="polite">
                    {{ session('status') }}
                </div>
            @endif

            <section aria-labelledby="users-heading" class="py-6">
                <h2 id="users-heading" class="sr-only">Listado de usuarios</h2>

                <div class="overflow-hidden rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)]">
                    <div class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-3 text-xs font-semibold uppercase tracking-[0.06em] text-[var(--color-text-muted)] sm:grid-cols-[minmax(0,1fr)_8rem_minmax(0,1.15fr)_9rem] sm:px-5">
                        <span>Usuario</span>
                        <span>Estado</span>
                        <span>Acceso</span>
                        <span>Acciones</span>
                    </div>

                    @forelse ($users as $user)
                        <article class="grid gap-3 border-b border-[var(--color-border-default)] px-4 py-4 text-sm last:border-b-0 sm:grid-cols-[minmax(0,1fr)_8rem_minmax(0,1.15fr)_9rem] sm:items-start sm:px-5">
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold">{{ $user->name }}</h3>
                                <p class="mt-1 truncate text-xs text-[var(--color-text-muted)]">{{ $user->email }}</p>
                                @if ($user->is_superadmin)
                                    <span class="mt-2 inline-flex rounded-md bg-[#e1f3fe] px-2 py-1 text-xs font-semibold uppercase tracking-[0.06em] text-[#1f6c9f]">
                                        Superadmin
                                    </span>
                                @endif
                            </div>

                            <span @class([
                                'w-fit rounded-md px-2 py-1 text-xs font-semibold uppercase tracking-[0.06em]',
                                'bg-[var(--color-success-bg)] text-[var(--color-success)]' => $user->is_active,
                                'bg-[#fbf3db] text-[var(--color-warning)]' => ! $user->is_active,
                            ])>
                                {{ $user->is_active ? 'Activa' : 'Desactivada' }}
                            </span>

                            <div class="grid gap-2">
                                @forelse ($user->memberships as $membership)
                                    <div class="min-w-0 rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2">
                                        <p class="truncate font-medium">{{ $membership->company?->name ?? 'Empresa no disponible' }}</p>
                                        <form method="POST" action="{{ route('admin.memberships.update', $membership) }}" data-confirm="Cambiar esta membresía puede afectar el acceso del usuario a la empresa. ¿Continuar?">
                                            @csrf
                                            @method('PUT')
                                            <div class="mt-2 grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
                                                <div class="min-w-0">
                                                    <label for="membership-{{ $membership->id }}-role" class="block text-xs font-medium text-[var(--color-text-muted)]">Rol</label>
                                                    <select id="membership-{{ $membership->id }}-role" name="role" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-white px-2 py-1.5 text-xs text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                                        <option value="admin" @selected($membership->role === 'admin')>Administrador</option>
                                                        <option value="supervisor" @selected($membership->role === 'supervisor')>Supervisor</option>
                                                        <option value="agent" @selected($membership->role === 'agent')>Agente</option>
                                                    </select>
                                                </div>

                                                <div class="min-w-0">
                                                    <label for="membership-{{ $membership->id }}-status" class="block text-xs font-medium text-[var(--color-text-muted)]">Estado</label>
                                                    <select id="membership-{{ $membership->id }}-status" name="status" class="mt-1 w-full rounded-md border border-[var(--color-border-default)] bg-white px-2 py-1.5 text-xs text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                                        <option value="active" @selected($membership->status === 'active')>Activo</option>
                                                        <option value="disabled" @selected($membership->status === 'disabled')>Desactivado</option>
                                                    </select>
                                                </div>

                                                <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                                    Guardar
                                                </button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('admin.memberships.destroy', $membership) }}" data-confirm="Eliminar este acceso quita al usuario de la empresa. ¿Continuar?" class="mt-2">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-danger)] hover:border-[var(--color-danger)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                                Eliminar acceso
                                            </button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="text-[var(--color-text-muted)]">Sin membresías</p>
                                @endforelse
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.users.password-reset', $user) }}" data-confirm="Enviar un enlace para definir o restablecer la contraseña de este usuario. ¿Continuar?">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                        Enviar enlace
                                    </button>
                                </form>

                                @if ($user->is_active)
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" data-confirm="Cambiar el estado global de este usuario puede afectar su acceso. ¿Continuar?">
                                        @csrf
                                        <input type="hidden" name="is_active" value="0">
                                        <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                            Desactivar
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}" data-confirm="Cambiar el estado global de este usuario puede afectar su acceso. ¿Continuar?">
                                        @csrf
                                        <input type="hidden" name="is_active" value="1">
                                        <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                            Activar
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Eliminar este usuario desactiva su identidad global y elimina sus accesos a empresas. ¿Continuar?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-md border border-[var(--color-border-default)] px-2.5 py-1.5 text-xs font-medium text-[var(--color-danger)] hover:border-[var(--color-danger)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <p class="px-4 py-4 text-sm text-[var(--color-text-secondary)] sm:px-5">
                            Todavía no hay usuarios registrados.
                        </p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
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
