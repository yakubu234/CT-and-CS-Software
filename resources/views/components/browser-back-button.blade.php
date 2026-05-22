@props([
    'fallback',
    'label' => 'Back',
    'class' => 'btn btn-sm btn-outline-secondary',
])

<a
    href="{{ $fallback }}"
    class="{{ $class }}"
    data-browser-back="true"
    data-fallback-url="{{ $fallback }}"
    {{ $attributes }}
>
    <i class="fas fa-arrow-left mr-1"></i>
    {{ $label }}
</a>
