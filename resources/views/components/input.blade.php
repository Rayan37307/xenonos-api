@props(['type' => 'text', 'name', 'placeholder' => '', 'value' => null, 'autofocus' => false])

<input
    type="{{ $type }}"
    name="{{ $name }}"
    placeholder="{{ $placeholder }}"
    value="{{ $value ?? old($name) }}"
    {{ $attributes->merge(['class' => 'form-input']) }}
    @if($autofocus) autofocus @endif
/>
