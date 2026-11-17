@props([
    'tipe' => 'harian', // 'bulanan' atau 'harian'
    'class' => '',
])

@php
    $tipeLower = strtolower($tipe);
    $kelasTipe = $tipeLower === 'bulanan' ? 'bulanan' : 'harian';
    $label = $tipeLower === 'bulanan' ? 'BULANAN' : 'HARIAN';
@endphp

<span {{ $attributes->merge(['class' => "badge-kategori {$kelasTipe} " . $class]) }}>
    {{ $slot->isEmpty() ? $label : $slot }}
</span>
