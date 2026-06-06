<x-layouts.public-shell :title="'Restablecer contraseña | '.config('app.name', 'DoxTicket')">
    <section class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Acceso interno</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Restablecer contraseña</h1>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 sm:p-6">
            @csrf

            <label for="email" class="grid gap-2">
                <span class="text-sm font-medium">Correo</span>
                <input id="email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" spellcheck="false" autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="email" class="text-sm" />
            </label>

            <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-4 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                Enviar enlace
            </button>

            <a href="{{ route('login') }}" class="text-center text-sm font-medium text-[var(--color-action-primary)] hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Volver a login
            </a>
        </form>
    </section>
</x-layouts.public-shell>
