<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invitar usuario | {{ config('app.name', 'DoxTicket') }}</title>
        <link rel="icon" type="image/svg+xml" href="{{ asset('brand/doxticket.svg') }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--color-bg-page)] text-[var(--color-text-primary)] antialiased">
        <a href="#admin-main" class="skip-link">Saltar al contenido</a>

        <main id="admin-main" class="mx-auto flex min-h-screen w-full max-w-4xl flex-col px-6 py-8">
            <header class="flex flex-col gap-5 border-b border-[var(--color-border-default)] pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex min-w-0 items-center gap-2">
                        <img src="{{ asset('brand/doxticket.svg') }}" alt="" width="32" height="32" class="size-8 shrink-0" aria-hidden="true">
                        <p class="text-sm font-medium uppercase tracking-[0.08em] text-[var(--color-text-muted)]">Superadmin</p>
                    </div>
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">Invitar usuario</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Crea una membership inicial en una empresa. Si el correo ya existe, se reutiliza la cuenta global.
                    </p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                    Volver a usuarios
                </a>
            </header>

            <section aria-labelledby="invite-form-heading" class="py-6">
                <h2 id="invite-form-heading" class="sr-only">Formulario de invitacion</h2>

                <form method="POST" action="{{ route('admin.users.invite.store') }}" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    @csrf

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-semibold">Nombre</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" @class([
                                'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                'border-[var(--color-border-default)]' => ! $errors->has('name'),
                                'border-[var(--color-danger)]' => $errors->has('name'),
                            ]) @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>
                            @error('name')
                                <p id="name-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-semibold">Correo</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" spellcheck="false" @class([
                                'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                'border-[var(--color-border-default)]' => ! $errors->has('email'),
                                'border-[var(--color-danger)]' => $errors->has('email'),
                            ]) @if ($errors->has('email')) aria-invalid="true" aria-describedby="email-error" @endif>
                            @error('email')
                                <p id="email-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="company_id" class="block text-sm font-semibold">Empresa</label>
                            <select id="company_id" name="company_id" @class([
                                'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                'border-[var(--color-border-default)]' => ! $errors->has('company_id'),
                                'border-[var(--color-danger)]' => $errors->has('company_id'),
                            ]) @if ($errors->has('company_id')) aria-invalid="true" aria-describedby="company-id-error" @endif>
                                <option value="">Selecciona una empresa</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}" @selected((string) old('company_id') === (string) $company->id)>{{ $company->name }}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                                <p id="company-id-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="block text-sm font-semibold">Rol</label>
                            <select id="role" name="role" @class([
                                'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                'border-[var(--color-border-default)]' => ! $errors->has('role'),
                                'border-[var(--color-danger)]' => $errors->has('role'),
                            ]) @if ($errors->has('role')) aria-invalid="true" aria-describedby="role-error" @endif>
                                <option value="agent" @selected(old('role', 'agent') === 'agent')>agent</option>
                                <option value="supervisor" @selected(old('role') === 'supervisor')>supervisor</option>
                                <option value="admin" @selected(old('role') === 'admin')>admin</option>
                            </select>
                            @error('role')
                                <p id="role-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <p class="mt-4 rounded-md border border-[var(--color-border-default)] bg-white px-3 py-2 text-sm leading-6 text-[var(--color-text-secondary)]">
                        En esta fase se registra la invitacion y la membership. El envio real de correo se conectara al SMTP global.
                    </p>

                    <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-[var(--color-border-default)] pt-5">
                        <a href="{{ route('admin.users.index') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Cancelar
                        </a>
                        <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Registrar invitacion
                        </button>
                    </div>
                </form>
            </section>

            <footer class="mt-auto border-t border-[var(--color-border-default)] pt-5 text-sm text-[var(--color-text-muted)]">
                Powered by DoxTicket
            </footer>
        </main>
    </body>
</html>
