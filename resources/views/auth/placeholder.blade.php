<x-layouts.public-shell :title="'Login | '.config('app.name', 'DoxTicket')">
    <section class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Acceso interno</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Entrar a DoxTicket</h1>
        </div>

        @if (in_array(session('status'), [
            'setup-completed',
            'Contraseña actualizada. Ya puedes entrar.',
            'Si el correo existe, enviaremos un enlace para restablecer la contraseña.',
        ], true))
            <div class="mb-4 rounded-lg border border-[var(--color-success-border)] bg-[var(--color-success-bg)] p-4 text-sm text-[var(--color-success)]" role="status" aria-live="polite">
                {{ session('status') === 'setup-completed' ? 'Setup completado.' : session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 sm:p-6">
            @csrf

            <label for="email" class="grid gap-2">
                <span class="text-sm font-medium">Correo</span>
                <input id="email" name="email" value="{{ old('email') }}" type="email" autocomplete="email" spellcheck="false" autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="email" class="text-sm" />
            </label>

            <label for="password" class="grid gap-2">
                <span class="text-sm font-medium">Contraseña</span>
                <input id="password" name="password" type="password" autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="password" class="text-sm" />
            </label>

            <label class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-[var(--color-border-default)] text-[var(--color-action-primary)] focus-visible:ring-[var(--color-focus)]">
                Mantener sesión
            </label>

            <a href="{{ route('password.request') }}" class="-mt-2 text-sm font-medium text-[var(--color-action-primary)] hover:text-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                Olvidé mi contraseña
            </a>

            <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-4 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                Entrar
            </button>
        </form>
    </section>
</x-layouts.public-shell>
