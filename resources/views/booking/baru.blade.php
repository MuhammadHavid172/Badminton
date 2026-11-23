@extends('layouts.app')

@section('title', 'Booking Lapangan - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="padding-top: var(--spasi-8); padding-bottom: calc(var(--spasi-16) + 60px);">
    {{-- Header Sederhana --}}
    <div style="margin-bottom: var(--spasi-6);">
        <a href="{{ route('jadwal', ['tanggal' => $startDate->format('Y-m-d')]) }}" class="link-kembali" style="display: inline-flex; align-items: center; gap: var(--spasi-2); font-family: var(--font-mono); font-size: 0.8125rem; text-transform: uppercase; color: var(--teks-redup); text-decoration: none; margin-bottom: var(--spasi-3);">
            &larr; Kembali ke Jadwal
        </a>
        <h1 style="font-family: var(--font-judul); font-size: 2.25rem; font-weight: 700; text-transform: uppercase; letter-spacing: var(--tracking-judul);">
            Booking Lapangan
        </h1>
        <p style="color: var(--teks-redup); margin-top: var(--spasi-1);">
            Tinjau slot pilihanmu dan pilih kategori sewa yang sesuai kebutuhan.
        </p>
    </div>

    @if ($errors->any())
        <div class="alert-box alert-box-error" style="margin-bottom: var(--spasi-6);" role="alert">
            <ul style="margin: 0; padding-left: var(--spasi-4);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Layout Dua Kolom Desktop / Satu Kolom HP --}}
    <div class="booking-layout-grid">
        {{-- Kolom Kiri: Slip Tiket Datar --}}
        <div>
            <div class="slip-tiket" aria-label="Slip Ringkasan Slot">
                <div class="slip-tiket-header">
                    <span class="slip-tiket-sub">{{ config('gor.name') }}</span>
                    <div class="slip-tiket-kode">SLIP BOOKING</div>
                    <span class="slip-tiket-badge">Draf Pilihan</span>
                </div>

                <div class="slip-tiket-body">
                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Lapangan</span>
                        <span class="slip-tiket-val slip-tiket-val-large">{{ $court->name }}</span>
                    </div>

                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Tanggal Mulai</span>
                        <span class="slip-tiket-val">{{ tanggal_indonesia($startDate, true, false) }}</span>
                    </div>

                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Sesi & Jam</span>
                        <span class="slip-tiket-val">
                            {{ $session->label }} ({{ $session->formattedRange() }} WIT)
                        </span>
                    </div>

                    <div class="slip-tiket-row">
                        <span class="slip-tiket-label">Zona Waktu</span>
                        <span class="slip-tiket-val">Asia/Jayapura (WIT)</span>
                    </div>
                </div>

                <div class="slip-tiket-footer">
                    <span class="slip-tiket-label">Perkiraan Biaya</span>
                    <span class="slip-tiket-val slip-tiket-val-large" id="slip-total-price">
                        {{ rupiah($availability['single']['price']) }}
                    </span>
                </div>
            </div>

            <p style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: var(--spasi-3); line-height: 1.5;">
                Slot akan ditahan selama {{ \App\Models\Setting::getInt('hold_minutes', 15) }} menit setelah tombol booking ditekan untuk memberi waktu pembayaran.
            </p>
        </div>

        {{-- Kolom Kanan: Form Pilihan Kategori dan Pendaftaran Tamu --}}
        <div>
            <form action="{{ route('booking.store') }}" method="POST" id="form-booking">
                @csrf
                <input type="hidden" name="lapangan" value="{{ $court->id }}">
                <input type="hidden" name="sesi" value="{{ $session->id }}">
                <input type="hidden" name="tanggal" value="{{ $startDate->format('Y-m-d') }}">

                {{-- Bagian Tamu: Pendaftaran Ringkas Inline --}}
                @guest
                    <div class="booking-section-box" style="margin-bottom: var(--spasi-6);">
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: var(--spasi-3); flex-wrap: wrap; gap: var(--spasi-2);">
                            <h2 style="font-family: var(--font-judul); font-size: 1.125rem; font-weight: 700; text-transform: uppercase;">
                                Data Diri Pemesan
                            </h2>
                            <a href="{{ route('masuk') }}" style="font-size: 0.875rem; color: var(--lime); text-decoration: underline;">
                                Sudah punya akun? Masuk
                            </a>
                        </div>
                        <p style="font-size: 0.875rem; color: var(--teks-redup); margin-bottom: var(--spasi-4);">
                            Akun dibuat otomatis agar kamu bisa memantau status booking dan jadwal main.
                        </p>

                        <div style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                            <div class="form-group">
                                <label for="name" class="form-label">Nama Lengkap</label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    class="form-input @error('name') form-input-error @enderror"
                                    value="{{ old('name') }}"
                                    required
                                    placeholder="Masukkan nama lengkap"
                                >
                                @error('name')
                                    <span class="form-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Nomor HP</label>
                                <input
                                    type="tel"
                                    name="phone"
                                    id="phone"
                                    class="form-input @error('phone') form-input-error @enderror"
                                    value="{{ old('phone') }}"
                                    required
                                    placeholder="08xxxxxxxxxx"
                                >
                                @error('phone')
                                    <span class="form-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password (Minimal 8 karakter)</label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="form-input @error('password') form-input-error @enderror"
                                    required
                                    placeholder="Minimal 8 karakter"
                                >
                                @error('password')
                                    <span class="form-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="team_name" class="form-label">Nama PB / Tim (Opsional)</label>
                                <input
                                    type="text"
                                    name="team_name"
                                    id="team_name"
                                    class="form-input @error('team_name') form-input-error @enderror"
                                    value="{{ old('team_name') }}"
                                    placeholder="Nama PB / tim"
                                >
                                @error('team_name')
                                    <span class="form-error-msg">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email (Opsional)</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    class="form-input @error('email') form-input-error @enderror"
                                    value="{{ old('email') }}"
                                    placeholder="nama@email.com"
                                >
                                @error('email')
                                    <span class="form-error-msg">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                @else
                    <div class="booking-section-box" style="margin-bottom: var(--spasi-6); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <span style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--teks-redup);">
                                Pemesan
                            </span>
                            <div style="font-weight: 700; font-size: 1.125rem; color: var(--putih);">
                                {{ auth()->user()->name }}
                                @if (auth()->user()->team_name)
                                    <span style="font-weight: 400; color: var(--teks-redup);">({{ auth()->user()->team_name }})</span>
                                @endif
                            </div>
                            <span style="font-family: var(--font-mono); font-size: 0.8125rem; color: var(--teks-redup);">
                                {{ auth()->user()->phone }}
                            </span>
                        </div>
                        <a href="{{ route('akun') }}" style="font-size: 0.8125rem; color: var(--lime); text-decoration: underline;">
                            Ubah di profil
                        </a>
                    </div>
                @endguest

                {{-- Bagian Pilihan Kategori Sewa: Baris Radio Diperbesar --}}
                <div style="margin-bottom: var(--spasi-6);">
                    <h2 style="font-family: var(--font-judul); font-size: 1.125rem; font-weight: 700; text-transform: uppercase; margin-bottom: var(--spasi-3);">
                        Pilih Kategori Sewa
                    </h2>

                    <div class="booking-radio-group">
                        {{-- Opsi 1: Harian --}}
                        <label class="booking-radio-row {{ !$availability['single']['available'] ? 'is-disabled' : '' }}">
                            <input
                                type="radio"
                                name="type"
                                value="single"
                                class="booking-radio-input"
                                {{ old('type', 'single') === 'single' ? 'checked' : '' }}
                                {{ !$availability['single']['available'] ? 'disabled' : '' }}
                                data-price="{{ $availability['single']['price'] }}"
                                data-label="Harian"
                            >
                            <div class="booking-radio-content">
                                <div class="booking-radio-top">
                                    <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                                        <span class="badge-kategori badge-kategori-harian">HARIAN</span>
                                        <span class="booking-radio-title">1 Pertemuan</span>
                                    </div>
                                    <span class="booking-radio-price">
                                        {{ rupiah($availability['single']['price']) }}
                                    </span>
                                </div>
                                <div class="booking-radio-desc">
                                    Main 1 sesi pada tanggal {{ tanggal_indonesia($startDate, true, false) }}.
                                </div>
                                @if (!$availability['single']['available'])
                                    <div class="booking-conflict-msg">
                                        Tidak dapat dipilih: {{ $availability['single']['conflict'] ?? 'Slot tidak tersedia' }}
                                    </div>
                                @endif
                            </div>
                        </label>

                        {{-- Opsi 2: Bulanan --}}
                        <label class="booking-radio-row {{ !$availability['package']['available'] ? 'is-disabled' : '' }}">
                            <input
                                type="radio"
                                name="type"
                                value="package"
                                class="booking-radio-input"
                                {{ old('type') === 'package' ? 'checked' : '' }}
                                {{ !$availability['package']['available'] ? 'disabled' : '' }}
                                data-price="{{ $availability['package']['price'] }}"
                                data-label="Bulanan"
                            >
                            <div class="booking-radio-content">
                                <div class="booking-radio-top">
                                    <div style="display: flex; align-items: center; gap: var(--spasi-2);">
                                        <span class="badge-kategori badge-kategori-bulanan">BULANAN</span>
                                        <span class="booking-radio-title">4 Pertemuan Mingguan</span>
                                    </div>
                                    <span class="booking-radio-price">
                                        {{ rupiah($availability['package']['price']) }}
                                    </span>
                                </div>

                                <div class="booking-radio-desc">
                                    Terkunci 4 minggu berturut-turut di hari, jam, dan lapangan yang sama:
                                    <div class="booking-package-dates-list">
                                        @foreach ($availability['package']['dates'] as $pDate)
                                            <div class="package-date-item">
                                                <span class="package-date-bullet">{{ $pDate['index'] }}</span>
                                                <span>{{ $pDate['formatted'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                @if ($availability['package']['saving'] > 0)
                                    <div class="booking-saving-tag">
                                        Hemat {{ rupiah($availability['package']['saving']) }} dibanding 4x sewa Harian
                                    </div>
                                @endif

                                <div class="booking-policy-notice">
                                    Slot lanjutan tidak dikunci. Siapa cepat dia dapat.
                                </div>

                                @if (!$availability['package']['available'])
                                    <div class="booking-conflict-msg">
                                        Tidak dapat dipilih karena bentrok pada tanggal:
                                        <ul style="margin: 4px 0 0 16px; padding: 0;">
                                            @foreach ($availability['package']['conflicts'] as $cDate => $cMsg)
                                                <li>{{ $cMsg }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Catatan Tambahan (Opsional) --}}
                <div class="form-group" style="margin-bottom: var(--spasi-6);">
                    <label for="notes" class="form-label">Catatan Tambahan (Opsional)</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="2"
                        class="form-input"
                        placeholder="Catatan tambahan..."
                    >{{ old('notes') }}</textarea>
                </div>

                {{-- Tombol Submit Form Desktop --}}
                <div class="booking-submit-desktop">
                    <button type="submit" class="tombol tombol-solid btn-booking-submit" id="btn-submit-main" style="width: 100%; min-height: 52px; font-size: 1rem;">
                        Booking Sekarang
                    </button>
                    <p style="font-size: 0.8125rem; color: var(--teks-redup); text-align: center; margin-top: var(--spasi-2);">
                        Dengan menekan tombol, kamu menyetujui jadwal dan waktu tahan pembayaran.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bar Bawah Menempel Khusus Mobile (< 768px) --}}
<div class="booking-sticky-bar" id="mobile-sticky-bar" aria-label="Ringkasan Booking Bawah">
    <div class="sticky-bar-info">
        <div class="sticky-bar-label" id="sticky-category-label">HARIAN</div>
        <div class="sticky-bar-price" id="sticky-total-price">
            {{ rupiah($availability['single']['price']) }}
        </div>
        <div class="sticky-bar-date">{{ tanggal_indonesia($startDate, false, false) }}</div>
    </div>
    <button type="button" class="tombol tombol-solid btn-booking-submit" id="btn-submit-sticky">
        Booking Sekarang
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radioInputs = document.querySelectorAll('.booking-radio-input');
    const slipPrice = document.getElementById('slip-total-price');
    const stickyPrice = document.getElementById('sticky-total-price');
    const stickyLabel = document.getElementById('sticky-category-label');
    const formBooking = document.getElementById('form-booking');
    const btnMain = document.getElementById('btn-submit-main');
    const btnSticky = document.getElementById('btn-submit-sticky');

    function formatRupiah(num) {
        return 'Rp ' + Number(num).toLocaleString('id-ID');
    }

    function updateSelection() {
        const checked = document.querySelector('.booking-radio-input:checked');
        if (!checked) return;

        const price = checked.getAttribute('data-price');
        const label = checked.getAttribute('data-label');
        const formatted = formatRupiah(price);

        if (slipPrice) slipPrice.textContent = formatted;
        if (stickyPrice) stickyPrice.textContent = formatted;
        if (stickyLabel) stickyLabel.textContent = label.toUpperCase();
    }

    radioInputs.forEach(input => {
        input.addEventListener('change', updateSelection);
    });

    updateSelection();

    // Submit handler: Nonaktifkan tombol saat proses submit berjalan
    let isSubmitting = false;

    function handleFormSubmit(e) {
        if (isSubmitting) {
            e.preventDefault();
            return false;
        }

        isSubmitting = true;

        if (btnMain) {
            btnMain.disabled = true;
            btnMain.textContent = 'Memproses...';
            btnMain.classList.add('is-loading');
        }

        if (btnSticky) {
            btnSticky.disabled = true;
            btnSticky.textContent = 'Memproses...';
            btnSticky.classList.add('is-loading');
        }
    }

    if (formBooking) {
        formBooking.addEventListener('submit', handleFormSubmit);
    }

    if (btnSticky) {
        btnSticky.addEventListener('click', function () {
            if (formBooking && !isSubmitting) {
                // Trigger form validation or submit
                if (formBooking.reportValidity()) {
                    formBooking.dispatchEvent(new Event('submit', { cancelable: true }));
                    formBooking.submit();
                }
            }
        });
    }
});
</script>
@endsection
