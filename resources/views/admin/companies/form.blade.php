@php
    $isEdit = $mode === 'edit';
    $title = $isEdit ? 'Editar empresa' : 'Nueva empresa';
    $action = $isEdit ? route('admin.companies.update', $company) : route('admin.companies.store');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} | {{ config('app.name', 'DoxTicket') }}</title>
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
                    <h1 class="mt-2 text-3xl font-semibold tracking-normal text-pretty">{{ $title }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--color-text-secondary)]">
                        Gestiona datos base del tenant. Usuarios, correo y tickets se administran en sus secciones propias.
                    </p>
                </div>
                <a href="{{ route('admin.companies.index') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                    Volver a empresas
                </a>
            </header>

            <section aria-labelledby="company-form-heading" class="py-6">
                <h2 id="company-form-heading" class="sr-only">{{ $title }}</h2>

                <form method="POST" action="{{ $action }}" class="rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-4 sm:p-5">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-semibold">Nombre</label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name', $company->name) }}"
                                autocomplete="organization"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('name'),
                                    'border-[var(--color-danger)]' => $errors->has('name'),
                                ])
                                @if ($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif
                            >
                            @error('name')
                                <p id="name-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-semibold">Slug</label>
                            <input
                                id="slug"
                                name="slug"
                                type="text"
                                value="{{ old('slug', $company->slug) }}"
                                autocomplete="off"
                                spellcheck="false"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 font-mono text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('slug'),
                                    'border-[var(--color-danger)]' => $errors->has('slug'),
                                ])
                                @if ($errors->has('slug')) aria-invalid="true" aria-describedby="slug-error" @endif
                            >
                            @error('slug')
                                <p id="slug-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-semibold">Estado</label>
                            <select
                                id="status"
                                name="status"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('status'),
                                    'border-[var(--color-danger)]' => $errors->has('status'),
                                ])
                                @if ($errors->has('status')) aria-invalid="true" aria-describedby="status-error" @endif
                            >
                                <option value="active" @selected(old('status', $company->status) === 'active')>Activa</option>
                                <option value="disabled" @selected(old('status', $company->status) === 'disabled')>Desactivada</option>
                                <option value="archived" @selected(old('status', $company->status) === 'archived')>Archivada</option>
                            </select>
                            @error('status')
                                <p id="status-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="country" class="block text-sm font-semibold">País</label>
                            <input
                                id="country"
                                name="country"
                                type="text"
                                maxlength="120"
                                value="{{ old('country', $company->country) }}"
                                autocomplete="country"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('country'),
                                    'border-[var(--color-danger)]' => $errors->has('country'),
                                ])
                                @if ($errors->has('country')) aria-invalid="true" aria-describedby="country-error" @endif
                            >
                            @error('country')
                                <p id="country-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="locale_default" class="block text-sm font-semibold">Idioma por defecto</label>
                            <select
                                id="locale_default"
                                name="locale_default"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('locale_default'),
                                    'border-[var(--color-danger)]' => $errors->has('locale_default'),
                                ])
                                @if ($errors->has('locale_default')) aria-invalid="true" aria-describedby="locale-default-error" @endif
                            >
                                <option value="es" @selected(old('locale_default', $company->locale_default) === 'es')>Español</option>
                                <option value="en" @selected(old('locale_default', $company->locale_default) === 'en')>English</option>
                            </select>
                            @error('locale_default')
                                <p id="locale-default-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="phone" class="block text-sm font-semibold">Telefono</label>
                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                value="{{ old('phone', $company->phone) }}"
                                autocomplete="tel"
                                @class([
                                    'mt-2 w-full rounded-md border bg-white px-3 py-2 text-sm outline-none focus:border-[var(--color-action-primary)] focus:ring-2 focus:ring-[var(--color-action-primary)]/15',
                                    'border-[var(--color-border-default)]' => ! $errors->has('phone'),
                                    'border-[var(--color-danger)]' => $errors->has('phone'),
                                ])
                                @if ($errors->has('phone')) aria-invalid="true" aria-describedby="phone-error" @endif
                            >
                            @error('phone')
                                <p id="phone-error" class="mt-2 text-sm text-[var(--color-danger)]" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-[var(--color-border-default)] pt-5">
                        <a href="{{ route('admin.companies.index') }}" class="rounded-md border border-[var(--color-border-default)] px-3 py-2 text-sm font-medium text-[var(--color-text-secondary)] hover:border-[var(--color-border-strong)] hover:text-[var(--color-text-primary)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            Cancelar
                        </a>
                        <button type="submit" class="rounded-md bg-[var(--color-action-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--color-action-primary-hover)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--color-action-primary)]">
                            {{ $isEdit ? 'Guardar cambios' : 'Crear empresa' }}
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
