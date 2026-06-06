@props(['field', 'id' => null])

@error($field)
    <p id="{{ $id ?? $field.'-error' }}" role="alert" aria-live="polite" {{ $attributes->merge(['class' => 'mt-1 text-xs text-[var(--color-danger)]']) }}>
        {{ $message }}
    </p>
@enderror
