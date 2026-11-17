@props([
    'teks' => '',
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'penanda ' . $class]) }}>
    <span>{{ $teks ?: $slot }}</span>
    <span class="penanda-titik" aria-hidden="true"></span>
</div>
