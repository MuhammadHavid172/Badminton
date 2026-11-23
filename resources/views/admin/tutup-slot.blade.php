@extends('layouts.admin')

@section('title', 'Tutup & Buka Slot - Admin - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 900px; padding-top: var(--spasi-4);">

    {{-- Breadcrumb & Navigasi --}}
    <div style="margin-bottom: var(--spasi-4);">
        <a href="{{ route('admin.dashboard') }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
            &larr; Kembali ke Hari Ini
        </a>
    </div>

    {{-- Banner Notifikasi --}}
    @if (session('status'))
        <div class="alert-box alert-box-sukses" style="margin-bottom: var(--spasi-4);" role="status">
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-4);" role="alert">
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-4);" role="alert">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $err)
                    <li>{!! $err !!}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form Tutup Slot Baru --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-5);">
        <div style="margin-bottom: var(--spasi-4); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
            <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                Pemeliharaan &amp; Acara
            </p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0 0 4px 0; line-height: 1.2;">
                Tutup Slot Lapangan
            </h1>
            <p style="color: var(--teks-redup); margin: 0; font-size: 0.875rem;">
                Tutup slot untuk perbaikan karpet, lampu, turnamen, atau acara internal. Slot yang ditutup tidak dapat dipesan di jadwal publik. Penutupan ditolak jika sudah ada booking aktif pada jadwal tersebut.
            </p>
        </div>

        <form action="{{ route('admin.tutup-slot.store') }}" method="POST" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
                <div>
                    <label for="close_court_id" class="form-label" style="display: block; margin-bottom: 4px;">
                        Pilih Lapangan <span style="color: var(--merah);">*</span>
                    </label>
                    <select id="close_court_id" name="court_id" required class="form-input" style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                        <option value="">-- Pilih Lapangan --</option>
                        @foreach ($courts as $c)
                            <option value="{{ $c->id }}" {{ old('court_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="close_play_date" class="form-label" style="display: block; margin-bottom: 4px;">
                        Tanggal Tutup <span style="color: var(--merah);">*</span>
                    </label>
                    <input type="date"
                           id="close_play_date"
                           name="play_date"
                           required
                           class="form-input"
                           style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                           value="{{ old('play_date', \Carbon\Carbon::now('Asia/Jayapura')->format('Y-m-d')) }}">
                </div>

                <div>
                    <label for="close_play_session_id" class="form-label" style="display: block; margin-bottom: 4px;">
                        Pilih Sesi
                    </label>
                    <select id="close_play_session_id" name="play_session_id" class="form-input" style="width: 100%; min-height: 48px; background-color: #1A1A17;">
                        <option value="">-- Semua Sesi Sepanjang Hari --</option>
                        @foreach ($sessions as $s)
                            <option value="{{ $s->id }}" {{ old('play_session_id') == $s->id ? 'selected' : '' }}>
                                {{ $s->label }} ({{ $s->formattedRange() }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="close_reason" class="form-label" style="display: block; margin-bottom: 4px;">
                    Alasan Penutupan Slot <span style="color: var(--merah);">*</span>
                </label>
                <input type="text"
                       id="close_reason"
                       name="reason"
                       required
                       placeholder="Alasan penutupan..."
                       class="form-input"
                       style="width: 100%; min-height: 48px; background-color: #1A1A17;"
                       value="{{ old('reason') }}">
            </div>

            <div>
                <button type="submit" class="tombol tombol-primer" style="min-height: 48px; padding: 0 24px; font-weight: 700;">
                    Simpan &amp; Tutup Slot Ini
                </button>
            </div>
        </form>
    </div>

    {{-- Daftar Slot yang Sedang Ditutup --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4);">
        <h2 style="font-size: 1.125rem; margin: 0 0 var(--spasi-3) 0; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
            Daftar Slot Ditutup (Aktif &amp; Mendatang)
        </h2>

        @if ($activeBlocks->isEmpty())
            <p style="color: var(--teks-redup); margin: 0;">
                Tidak ada slot yang sedang ditutup saat ini.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                @foreach ($activeBlocks as $block)
                    <div style="background-color: #1C1C18; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--spasi-2);">
                        <div>
                            <div style="display: flex; align-items: center; gap: var(--spasi-2); margin-bottom: 2px;">
                                <span style="font-family: var(--font-mono); font-size: 0.6875rem; background-color: #5A5A52; color: #FFF; padding: 1px 6px; border-radius: 3px; font-weight: 700; text-transform: uppercase;">
                                    DITUTUP
                                </span>
                                <span style="font-weight: 700; color: var(--putih);">
                                    {{ $block->court->name }}
                                </span>
                                <span style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--lime);">
                                    {{ tanggal_indonesia($block->play_date, false, true) }}
                                </span>
                            </div>

                            <div style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                Sesi: <strong style="color: var(--putih);">{{ $block->session ? $block->session->label . ' (' . $block->session->formattedRange() . ')' : 'Semua Sesi Sepanjang Hari' }}</strong>
                            </div>

                            <div style="font-size: 0.875rem; color: var(--putih); margin-top: 4px;">
                                Alasan: {{ $block->reason }}
                            </div>

                            <div style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup); margin-top: 2px;">
                                Ditutup oleh: {{ $block->creator?->name ?? 'Admin' }}
                            </div>
                        </div>

                        <div>
                            <form action="{{ route('admin.tutup-slot.destroy', $block->id) }}" method="POST" style="margin: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tombol tombol-sekunder" style="min-height: 48px; padding: 0 16px; font-size: 0.8125rem;" onclick="return confirm('Buka kembali slot ini agar dapat dipesan?');">
                                    Buka Kembali Slot
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>
@endsection
