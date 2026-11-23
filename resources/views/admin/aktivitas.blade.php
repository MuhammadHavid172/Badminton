@extends('layouts.admin')

@section('title', 'Log Aktivitas Sistem - ' . config('gor.name'))

@section('content')
<div class="kontainer" style="max-width: 1000px; padding-top: var(--spasi-4);">

    {{-- Header Halaman --}}
    <div style="margin-bottom: var(--spasi-4);">
        <p style="font-family: var(--font-mono); font-size: 0.75rem; text-transform: uppercase; color: var(--lime); letter-spacing: 0.05em; margin: 0 0 4px 0;">
            Audit Jejak &amp; Keamanan
        </p>
        <h1 style="font-size: clamp(1.5rem, 4vw, 2rem); margin: 0 0 4px 0; line-height: 1.2;">
            Log Aktivitas Sistem
        </h1>
        <p style="color: var(--teks-redup); margin: 0; font-size: 0.875rem;">
            Catatan kronologis seluruh mutasi operasional lapangan, pemesanan, presensi, dan konfigurasi.
        </p>
    </div>

    {{-- Filter Log --}}
    <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-4); margin-bottom: var(--spasi-4);">
        @php
            $filterAction = $modePantau ? route('owner.aktivitas') : route('admin.aktivitas');
        @endphp
        <form method="GET" action="{{ $filterAction }}" style="display: flex; flex-direction: column; gap: var(--spasi-3);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: var(--spasi-2);">
                <div>
                    <label for="filter_action" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Jenis Aksi
                    </label>
                    <select id="filter_action" name="action" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Aksi</option>
                        @foreach ($distinctActions as $act)
                            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                                {{ $act }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_role" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Peran Pelaku
                    </label>
                    <select id="filter_role" name="role" class="form-input" style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                        <option value="">Semua Peran</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                        <option value="customer" {{ request('role') === 'customer' ? 'selected' : '' }}>Customer</option>
                    </select>
                </div>

                <div>
                    <label for="filter_dari" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Dari Tanggal
                    </label>
                    <input type="date"
                           id="filter_dari"
                           name="dari"
                           value="{{ request('dari') }}"
                           class="form-input"
                           style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                </div>

                <div>
                    <label for="filter_sampai" class="form-label" style="display: block; margin-bottom: 2px; font-size: 0.75rem;">
                        Sampai Tanggal
                    </label>
                    <input type="date"
                           id="filter_sampai"
                           name="sampai"
                           value="{{ request('sampai') }}"
                           class="form-input"
                           style="width: 100%; min-height: 44px; background-color: #1A1A17; font-size: 0.8125rem;">
                </div>
            </div>

            <div style="display: flex; gap: var(--spasi-2); align-items: center;">
                <button type="submit" class="tombol tombol-primer" style="min-height: 44px; padding: 0 var(--spasi-3); font-size: 0.875rem;">
                    Terapkan Filter
                </button>
                @if (request()->hasAny(['action', 'role', 'dari', 'sampai']))
                    <a href="{{ $filterAction }}" class="tombol tombol-sekunder" style="min-height: 44px; padding: 0 var(--spasi-3); font-size: 0.875rem; color: var(--teks-redup);">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabel Log Aktivitas --}}
    @if ($logs->isEmpty())
        <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); padding: var(--spasi-6); text-align: center;">
            <p style="color: var(--teks-redup); margin: 0; font-size: 1rem;">
                Belum ada data log aktivitas yang cocok dengan filter.
            </p>
        </div>
    @else
        {{-- Versi Desktop --}}
        <div class="admin-table-desktop" style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-md); overflow: hidden; margin-bottom: var(--spasi-4);">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--abu-garis); background-color: #1C1C18; font-family: var(--font-mono); font-size: 0.6875rem; text-transform: uppercase; color: var(--teks-redup);">
                        <th style="padding: 12px 16px;">Waktu (WIT)</th>
                        <th style="padding: 12px 16px;">Pelaku</th>
                        <th style="padding: 12px 16px;">Peran</th>
                        <th style="padding: 12px 16px;">Aksi</th>
                        <th style="padding: 12px 16px;">Deskripsi / Rincian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr style="border-bottom: 1px solid var(--abu-garis);">
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-size: 0.8125rem; color: var(--lime); white-space: nowrap;">
                                {{ $log->created_at->timezone('Asia/Jayapura')->format('d/m/Y H:i:s') }}
                            </td>
                            <td style="padding: 12px 16px;">
                                @if ($log->user)
                                    <strong style="color: var(--putih);">{{ $log->user->name }}</strong>
                                @else
                                    <span style="color: var(--teks-redup); font-style: italic;">Sistem / Tamu</span>
                                @endif
                            </td>
                            <td style="padding: 12px 16px;">
                                <span style="font-family: var(--font-mono); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; padding: 2px 6px; border-radius: 3px;
                                    background-color: {{ $log->role === 'admin' ? 'rgba(218, 255, 4, 0.15)' : ($log->role === 'owner' ? 'rgba(255, 176, 32, 0.15)' : '#2E2E28') }};
                                    color: {{ $log->role === 'admin' ? 'var(--lime)' : ($log->role === 'owner' ? '#FFB020' : 'var(--putih)') }};">
                                    {{ $log->role ?: '-' }}
                                </span>
                            </td>
                            <td style="padding: 12px 16px; font-family: var(--font-mono); font-size: 0.75rem; color: var(--putih);">
                                {{ $log->action }}
                            </td>
                            <td style="padding: 12px 16px;">
                                <div style="color: var(--putih); font-size: 0.875rem;">
                                    {{ $log->description }}
                                </div>
                                @if ($log->meta)
                                    <div style="font-family: var(--font-mono); font-size: 0.6875rem; color: var(--teks-redup); margin-top: 4px; background-color: #121210; padding: 4px 8px; border-radius: 3px; max-width: 450px; overflow-x: auto;">
                                        {{ json_encode($log->meta, JSON_UNESCAPED_SLASHES) }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Versi Mobile --}}
        <div class="admin-list-mobile" style="display: flex; flex-direction: column; gap: var(--spasi-3); margin-bottom: var(--spasi-4);">
            @foreach ($logs as $log)
                <div style="background-color: #171714; border: 1px solid var(--abu-garis); border-radius: var(--radius-sm); padding: var(--spasi-3); display: flex; flex-direction: column; gap: var(--spasi-2);">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--abu-garis); padding-bottom: var(--spasi-2);">
                        <span style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--lime);">
                            {{ $log->created_at->timezone('Asia/Jayapura')->format('d/m/Y H:i:s') }} WIT
                        </span>
                        <span style="font-family: var(--font-mono); font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;">
                            {{ $log->role ?: 'Sistem' }}
                        </span>
                    </div>

                    <div style="font-size: 0.875rem; color: var(--putih);">
                        <strong style="font-family: var(--font-mono); color: var(--lime);">[{{ $log->action }}]</strong>
                        {{ $log->description }}
                    </div>

                    <div style="font-family: var(--font-mono); font-size: 0.75rem; color: var(--teks-redup);">
                        Pelaku: {{ $log->user?->name ?? 'Sistem' }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Paginasi --}}
        <div>
            {{ $logs->links() }}
        </div>
    @endif

</div>
@endsection
