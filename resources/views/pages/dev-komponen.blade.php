@extends('layouts.app')

@section('title', 'Katalog Komponen - Dev Mode')

@section('content')
<section style="padding: 120px 0 var(--spasi-10);">
    <div class="kontainer">
        <x-penanda teks="LINGKUNGAN PENGEMBANGAN (LOCAL ONLY)" style="margin-bottom: var(--spasi-4);" />
        <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); margin-bottom: var(--spasi-6);">
            Sistem Desain &amp; Komponen
        </h1>
        <p style="margin-bottom: var(--spasi-8); max-width: 640px;">
            Halaman ini digunakan untuk verifikasi visual palet warna, tipografi, tombol, CTA, penanda, kartu, badge kategori, dan fallback foto.
        </p>

        <!-- 1. Palet Warna -->
        <div style="margin-bottom: var(--spasi-8);">
            <h2 style="font-size: 1.5rem; margin-bottom: var(--spasi-4);">1. Palet Warna</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--spasi-3);">
                <div style="padding: var(--spasi-3); background-color: var(--hitam); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--hitam); border: 1px solid var(--garis); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600;">--hitam</div>
                    <div style="font-size: 0.75rem; color: var(--teks-redup);">#0D0D0B</div>
                </div>
                <div style="padding: var(--spasi-3); background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600;">--hitam-2</div>
                    <div style="font-size: 0.75rem; color: var(--teks-redup);">#161613</div>
                </div>
                <div style="padding: var(--spasi-3); background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--navy); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600;">--navy</div>
                    <div style="font-size: 0.75rem; color: var(--teks-redup);">#122B41</div>
                </div>
                <div style="padding: var(--spasi-3); background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--lime); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: var(--hitam);">--lime (Aksen)</div>
                    <div style="font-size: 0.75rem; color: var(--hitam);">#DAFF04</div>
                </div>
                <div style="padding: var(--spasi-3); background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--putih); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: var(--hitam);">--putih</div>
                    <div style="font-size: 0.75rem; color: var(--teks-redup);">#FFFFFF</div>
                </div>
                <div style="padding: var(--spasi-3); background-color: var(--hitam-2); border: 1px solid var(--garis); border-radius: 12px;">
                    <div style="height: 48px; background-color: var(--merah); border-radius: 6px; margin-bottom: 8px;"></div>
                    <div style="font-size: 0.875rem; font-weight: 600;">--merah (Error)</div>
                    <div style="font-size: 0.75rem; color: var(--teks-redup);">#FF5A4D</div>
                </div>
            </div>
        </div>

        <hr class="pemisah" style="margin: var(--spasi-8) 0;">

        <!-- 2. Tipografi -->
        <div style="margin-bottom: var(--spasi-8);">
            <h2 style="font-size: 1.5rem; margin-bottom: var(--spasi-4);">2. Tipografi</h2>
            <div style="display: flex; flex-direction: column; gap: var(--spasi-4);">
                <div>
                    <span style="font-size: 0.75rem; color: var(--lime); text-transform: uppercase;">Judul Raksasa (clamp)</span>
                    <div style="font-family: var(--font-judul); font-size: clamp(2rem, 6vw, 5rem); line-height: 0.95; font-weight: 600; letter-spacing: -0.045em;">
                        Lihat. <span class="teks-lime">Booking.</span> Main.
                    </div>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--lime); text-transform: uppercase;">Subjudul / Headings</span>
                    <div style="font-family: var(--font-judul); font-size: 2rem; font-weight: 600; letter-spacing: -0.045em;">
                        Pilih slot mainmu dengan mudah.
                    </div>
                </div>
                <div>
                    <span style="font-size: 0.75rem; color: var(--lime); text-transform: uppercase;">Paragraf Isi (Inter 400 &amp; 500)</span>
                    <p style="max-width: 600px;">
                        Jadwal semua lapangan terbuka. Slot kosong langsung kelihatan, kamu bisa memilih jadwal favoritmu kapan saja.
                    </p>
                </div>
            </div>
        </div>

        <hr class="pemisah" style="margin: var(--spasi-8) 0;">

        <!-- 3. Tombol & CTA -->
        <div style="margin-bottom: var(--spasi-8);">
            <h2 style="font-size: 1.5rem; margin-bottom: var(--spasi-4);">3. Tombol &amp; CTA</h2>
            <div style="display: flex; gap: var(--spasi-3); flex-wrap: wrap; align-items: center;">
                <button type="button" class="tombol tombol-solid">Tombol Solid</button>
                <button type="button" class="tombol tombol-sekunder">Tombol Sekunder</button>
                <button type="button" class="tombol tombol-ghost">Tombol Ghost</button>
                <x-cta href="#" teks="LIHAT JADWAL" />
            </div>
        </div>

        <hr class="pemisah" style="margin: var(--spasi-8) 0;">

        <!-- 4. Penanda & Badge -->
        <div style="margin-bottom: var(--spasi-8);">
            <h2 style="font-size: 1.5rem; margin-bottom: var(--spasi-4);">4. Penanda Section &amp; Badge Kategori</h2>
            <div style="display: flex; gap: var(--spasi-3); flex-wrap: wrap; align-items: center;">
                <x-penanda teks="PILIH LAPANGANMU" />
                <x-penanda teks="CARA BOOKING" />
                <x-badge-kategori tipe="bulanan" />
                <x-badge-kategori tipe="harian" />
                <span class="tag-contoh">Contoh</span>
            </div>
        </div>

        <hr class="pemisah" style="margin: var(--spasi-8) 0;">

        <!-- 5. Kartu & Fallback Foto -->
        <div style="margin-bottom: var(--spasi-8);">
            <h2 style="font-size: 1.5rem; margin-bottom: var(--spasi-4);">5. Kartu &amp; Fallback Foto</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: var(--spasi-4);">
                <x-kartu style="padding: var(--spasi-4);">
                    <div style="font-size: 0.75rem; color: var(--lime); text-transform: uppercase; margin-bottom: 8px;">Kartu Standar</div>
                    <h3 style="font-size: 1.25rem; margin-bottom: 8px;">Latar --hitam-2</h3>
                    <p style="font-size: 0.875rem;">Radius 24px, garis tipis 1px --garis, tanpa efek bayangan berwarna.</p>
                </x-kartu>

                <x-kartu style="overflow: hidden;">
                    <x-foto nama="foto-tidak-ada.jpg" alt="Contoh Fallback Gambar" :width="400" :height="200">
                        <div style="text-align: center; padding: var(--spasi-4);">
                            <span style="font-size: 0.75rem; color: var(--lime); text-transform: uppercase;">Pratinjau Fallback</span>
                            <div style="font-size: 0.875rem; color: var(--teks-redup); margin-top: 4px;">Gradien Navy-Hitam + Pola Titik</div>
                        </div>
                    </x-foto>
                    <div style="padding: var(--spasi-3);">
                        <h3 style="font-size: 1rem;">Fallback Terintegrasi</h3>
                    </div>
                </x-kartu>
            </div>
        </div>
    </div>
</section>
@endsection
