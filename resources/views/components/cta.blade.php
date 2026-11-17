@props([
    'href' => '#',
    'teks' => '',
    'class' => '',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'cta-landing ' . $class]) }}>
    <span>{{ $teks ?: $slot }}</span>
    <span class="panah" aria-hidden="true">↗</span>
</a>
