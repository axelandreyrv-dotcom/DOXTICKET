<x-layouts.public-shell :title="'2FA | '.config('app.name', 'DoxTicket')">
    <section class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-12">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--color-text-muted)]">Verificación</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-normal">Código 2FA</h1>
            <p class="mt-3 text-sm leading-6 text-[var(--color-text-secondary)]">Ingresa el código de tu app autenticadora o un código de recuperación.</p>
        </div>

        <form method="POST" action="{{ route('two-factor.login.store') }}" class="grid gap-5 rounded-lg border border-[var(--color-border-default)] bg-[var(--color-bg-surface)] p-5 sm:p-6">
            @csrf

            <label for="code" class="grid gap-2">
                <span class="text-sm font-medium">Código</span>
                <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" spellcheck="false" @error('code') aria-invalid="true" aria-describedby="code-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="code" class="text-sm" />
            </label>

            <label for="recovery_code" class="grid gap-2">
                <span class="text-sm font-medium">Código de recuperación</span>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="off" spellcheck="false" @error('recovery_code') aria-invalid="true" aria-describedby="recovery_code-error" @enderror class="h-10 rounded-md border border-[var(--color-border-default)] bg-white px-3 text-sm outline-none transition focus-visible:border-[var(--color-action-primary)] focus-visible:ring-2 focus-visible:ring-[var(--color-focus)]">
                <x-ui.field-error field="recovery_code" class="text-sm" />
            </label>

            <button type="submit" class="h-10 rounded-md bg-[var(--color-action-primary)] px-4 text-sm font-medium text-white transition hover:bg-[var(--color-action-primary-hover)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--color-focus)] active:scale-[0.99]">
                Verificar
            </button>
        </form>
    </section>
</x-layouts.public-shell>
