@extends('layouts.admin')

@section('title', 'Booking Langsung - Admin - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 800px; padding-top: var(--spasi-4);">

    {{-- Breadcrumb & Navigasi --}}
    <div style="margin-bottom: var(--spasi-4);">
        <a href="{{ route('admin.dashboard') }}" class="tombol tombol-sekunder" style="min-height: 40px; font-size: 0.8125rem;">
            &larr; Kembali ke Hari Ini
        </a>
    </div>

    {{-- Banner Status Notifikasi --}}
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
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-5); margin-bottom: var(--spasi-5);">
        <div style="margin-bottom: var(--spasi-4); border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-3);">
            <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
                Operasional Kasir &amp; Lapangan
            </p>
            <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0 0 4px 0; line-height: 1.2;">
                Booking Langsung di Tempat
            </h1>
            <p style="color: var(--teks-redup); margin: 0; font-size: 0.875rem;">
                Buat pesanan untuk pelanggan walk-in atau via telepon/pesan langsung oleh admin.
            </p>
        </div>

        <form action="{{ route('admin.booking-langsung.store') }}" method="POST" style="display: flex; flex-direction: column; gap: var(--spasi-4);">
            @csrf

            {{-- 1. Pilihan Jadwal Slot --}}
            <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                    1. Rincian Slot Bermain
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
                    <div>
                        <label for="booking_lapangan" class="form-label" style="display: block; margin-bottom: 4px;">
                            Pilih Lapangan <span style="color: var(--merah);">*</span>
                        </label>
                        <select id="booking_lapangan" name="lapangan" required class="form-input" style="width: 100%; min-height: 48px; background-color: #151512;">
                            <option value="">-- Pilih Lapangan --</option>
                            @foreach ($courts as $c)
                                <option value="{{ $c->id }}" {{ old('lapangan', $selectedCourtId) == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('lapangan')
                            <span class="pesan-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="booking_tanggal" class="form-label" style="display: block; margin-bottom: 4px;">
                            Tanggal Main <span style="color: var(--merah);">*</span>
                        </label>
                        <input type="date"
                               id="booking_tanggal"
                               name="tanggal"
                               required
                               class="form-input"
                               style="width: 100%; min-height: 48px; background-color: #151512;"
                               value="{{ old('tanggal', $selectedDate) }}">
                        @error('tanggal')
                            <span class="pesan-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="booking_sesi" class="form-label" style="display: block; margin-bottom: 4px;">
                            Sesi Waktu <span style="color: var(--merah);">*</span>
                        </label>
                        <select id="booking_sesi" name="sesi" required class="form-input" style="width: 100%; min-height: 48px; background-color: #151512;">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach ($sessions as $s)
                                <option value="{{ $s->id }}" {{ old('sesi', $selectedSessionId) == $s->id ? 'selected' : '' }}>
                                    {{ $s->label }} ({{ $s->formattedRange() }})
                                </option>
                            @endforeach
                        </select>
                        @error('sesi')
                            <span class="pesan-error">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 2. Kategori Sewa --}}
            <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                    2. Kategori Sewa
                </h2>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spasi-3);">
                    <label style="border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; align-items: flex-start; gap: var(--spasi-2); cursor: pointer; background-color: #151512;">
                        <input type="radio" name="type" value="single" style="margin-top: 4px;" {{ old('type', 'single') === 'single' ? 'checked' : '' }} required>
                        <div>
                            <div style="font-weight: 700; color: var(--putih);">
                                Harian
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: 2px;">
                                1 slot pertemuan saja (Rp 200.000)
                            </div>
                        </div>
                    </label>

                    <label style="border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; align-items: flex-start; gap: var(--spasi-2); cursor: pointer; background-color: #151512;">
                        <input type="radio" name="type" value="package" style="margin-top: 4px;" {{ old('type') === 'package' ? 'checked' : '' }}>
                        <div>
                            <div style="font-weight: 700; color: var(--putih);">
                                Bulanan
                            </div>
                            <div style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: 2px;">
                                4 pertemuan mingguan berturut-turut (Rp 600.000)
                            </div>
                        </div>
                    </label>
                </div>
                @error('type')
                    <span class="pesan-error">{{ $message }}</span>
                @enderror
            </div>

            {{-- 3. Data Pelanggan --}}
            <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                    3. Data Pelanggan
                </h2>

                <div style="display: flex; gap: var(--spasi-4); margin-bottom: var(--spasi-3);">
                    <label style="display: flex; align-items: center; gap: var(--spasi-2); cursor: pointer; font-size: 0.9375rem;">
                        <input type="radio" name="tipe_pelanggan" value="baru" id="tipe_baru" {{ old('tipe_pelanggan', 'baru') === 'baru' ? 'checked' : '' }}>
                        <span>Pelanggan Baru (Buat Akun Cepat)</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: var(--spasi-2); cursor: pointer; font-size: 0.9375rem;">
                        <input type="radio" name="tipe_pelanggan" value="lama" id="tipe_lama" {{ old('tipe_pelanggan') === 'lama' ? 'checked' : '' }}>
                        <span>Pelanggan Terdaftar</span>
                    </label>
                </div>

                {{-- Form Pelanggan Baru --}}
                <div id="box_pelanggan_baru" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spasi-3);">
                        <div>
                            <label for="nama_baru" class="form-label" style="display: block; margin-bottom: 4px;">
                                Nama Lengkap <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="text"
                                   id="nama_baru"
                                   name="nama_baru"
                                   placeholder="Masukkan nama lengkap"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512;"
                                   value="{{ old('nama_baru') }}">
                            @error('nama_baru')
                                <span class="pesan-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="hp_baru" class="form-label" style="display: block; margin-bottom: 4px;">
                                Nomor HP / WhatsApp <span style="color: var(--merah);">*</span>
                            </label>
                            <input type="text"
                                   id="hp_baru"
                                   name="hp_baru"
                                   placeholder="08xxxxxxxxxx"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512;"
                                   value="{{ old('hp_baru') }}">
                            @error('hp_baru')
                                <span class="pesan-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label for="team_name_baru" class="form-label" style="display: block; margin-bottom: 4px;">
                                Nama PB / Tim (Opsional)
                            </label>
                            <input type="text"
                                   id="team_name_baru"
                                   name="team_name_baru"
                                   placeholder="Nama PB atau tim (opsional)"
                                   class="form-input"
                                   style="width: 100%; min-height: 48px; background-color: #151512;"
                                   value="{{ old('team_name_baru') }}">
                            @error('team_name_baru')
                                <span class="pesan-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Form Pelanggan Lama --}}
                <div id="box_pelanggan_lama" style="display: none;">
                    <label for="user_id" class="form-label" style="display: block; margin-bottom: 4px;">
                        Pilih Pelanggan Terdaftar <span style="color: var(--merah);">*</span>
                    </label>
                    <select id="user_id" name="user_id" class="form-input" style="width: 100%; min-height: 48px; background-color: #151512;">
                        <option value="">-- Pilih Akun Pelanggan --</option>
                        @foreach ($customers as $cust)
                            <option value="{{ $cust->id }}" {{ old('user_id') == $cust->id ? 'selected' : '' }}>
                                {{ $cust->name }} &middot; {{ $cust->phone }} {{ $cust->team_name ? '('.$cust->team_name.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="pesan-error">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- 4. Metode Pembayaran & Catatan --}}
            <div style="background-color: #1F1F1B; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-4);">
                <h2 style="font-size: 1rem; margin: 0 0 var(--spasi-3) 0; color: var(--putih); border-bottom: 1px dashed var(--abu-garis); padding-bottom: var(--spasi-2);">
                    4. Pembayaran &amp; Catatan
                </h2>

                <div style="margin-bottom: var(--spasi-3);">
                    <label class="form-label" style="display: block; margin-bottom: var(--spasi-2);">
                        Status Pembayaran <span style="color: var(--merah);">*</span>
                    </label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: var(--spasi-3);">
                        <label style="border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; align-items: flex-start; gap: var(--spasi-2); cursor: pointer; background-color: #151512;">
                            <input type="radio" name="metode_bayar" value="tunai" style="margin-top: 4px;" {{ old('metode_bayar', 'tunai') === 'tunai' ? 'checked' : '' }} required>
                            <div>
                                <div style="font-weight: 700; color: var(--lime);">
                                    Tunai, sudah dibayar
                                </div>
                                <div style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: 2px;">
                                    Booking langsung tercatat LUNAS di kasir.
                                </div>
                            </div>
                        </label>

                        <label style="border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; align-items: flex-start; gap: var(--spasi-2); cursor: pointer; background-color: #151512;">
                            <input type="radio" name="metode_bayar" value="tahan" style="margin-top: 4px;" {{ old('metode_bayar') === 'tahan' ? 'checked' : '' }}>
                            <div>
                                <div style="font-weight: 700; color: #FFB020;">
                                    Tahan dulu
                                </div>
                                <div style="font-size: 0.8125rem; color: var(--teks-redup); margin-top: 2px;">
                                    Menunggu bayar (slot ditahan sementara).
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="booking_notes" class="form-label" style="display: block; margin-bottom: 4px;">
                        Catatan Khusus (Opsional)
                    </label>
                    <input type="text"
                           id="booking_notes"
                           name="notes"
                           placeholder="Catatan tambahan booking (opsional)"
                           class="form-input"
                           style="width: 100%; min-height: 48px; background-color: #151512;"
                           value="{{ old('notes') }}">
                </div>
            </div>

            {{-- Tombol Submit Besar --}}
            <div>
                <button type="submit" class="tombol tombol-primer" style="width: 100%; min-height: 52px; font-size: 1rem; font-weight: 700;">
                    Simpan Booking Langsung
                </button>
            </div>
        </form>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radioBaru = document.getElementById('tipe_baru');
    const radioLama = document.getElementById('tipe_lama');
    const boxBaru = document.getElementById('box_pelanggan_baru');
    const boxLama = document.getElementById('box_pelanggan_lama');

    function toggleCustomerForm() {
        if (radioBaru.checked) {
            boxBaru.style.display = 'flex';
            boxLama.style.display = 'none';
        } else {
            boxBaru.style.display = 'none';
            boxLama.style.display = 'block';
        }
    }

    radioBaru.addEventListener('change', toggleCustomerForm);
    radioLama.addEventListener('change', toggleCustomerForm);
    toggleCustomerForm();
});
</script>
@endsection
