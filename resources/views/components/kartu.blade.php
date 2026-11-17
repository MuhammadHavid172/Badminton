@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'kartu ' . $class]) }}>
    {{ $slot }}
</div>
