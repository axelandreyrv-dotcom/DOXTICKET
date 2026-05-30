<x-layouts.public-shell :title="'Login | '.config('app.name', 'DoxTicket')">
    <section class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Acceso interno</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Entrar a DoxTicket</h1>
        </div>

        @if (session('status') === 'setup-completed')
            <div class="mb-4 rounded-lg border border-[var(--color-success-border)] bg-[var(--color-success-bg)] p-4 text-sm text-[var(--color-success)]">
                Setup completado.
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" class="grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 sm:p-6">
            @csrf

            <label class="grid gap-2">
                <span class="text-sm font-medium">Correo</span>
                <input name="email" value="{{ old('email') }}" type="email" autocomplete="email" spellcheck="false" autofocus class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                @error('email') <span class="text-sm text-[var(--color-danger)]">{{ $message }}</span> @enderror
            </label>

            <label class="grid gap-2">
                <span class="text-sm font-medium">Contrasena</span>
                <input name="password" type="password" autocomplete="current-password" class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                @error('password') <span class="text-sm text-[var(--color-danger)]">{{ $message }}</span> @enderror
            </label>

            <label class="flex items-center gap-2 text-sm text-[var(--color-text-secondary)]">
                <input type="checkbox" name="remember" value="1" class="size-4 rounded border-[var(--color-border-default)] text-[var(--color-action-primary)] focus-visible:ring-[var(--color-focus)]">
                Mantener sesion
            </label>

            <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-4 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                Entrar
            </button>
        </form>
    </section>
</x-layouts.public-shell>
