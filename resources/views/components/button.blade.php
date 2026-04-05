@props(['type' => 'button'])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'form-button']) }}
>
    {{ $slot }}
</button>
