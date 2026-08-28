@extends('tenant.layouts.app')

@section('title', 'Device Absensi')
@section('page-title', 'Device Absensi')

@section('content')

    @if (session('raw_api_key'))
        <div class="t-card p-5 mb-6" style="background: var(--t-amber-bg); border-color: var(--t-amber-tx); border-width: 1px;">
            <p class="text-sm font-bold mb-2" style="color: var(--t-amber-tx);">
                API Key untuk "{{ session('raw_api_key_device') }}" — catat sekarang!
            </p>
            <p class="text-xs mb-3" style="color: var(--t-amber-tx);">
                Key ini cuma ditampilkan SEKALI. Setelah tinggalkan halaman ini, tidak bisa dilihat ulang —
                harus regenerate kalau hilang. Salin ke konfigurasi device sekarang juga.
            </p>
            <code class="block px-4 py-3 rounded-lg text-sm font-mono break-all" style="background: #fff; border: 1px solid var(--t-amber-tx); color: var(--t-dark);">{{ session('raw_api_key') }}</code>
        </div>
    @endif

    @if (session('success'))
        <div class="t-card p-4 mb-4" style="background: var(--t-green-bg); border-color: var(--t-green-tx); border-width: 1px;">
            <p class="text-sm font-semibold" style="color: var(--t-green-tx);">{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="t-card p-4 mb-4" style="background: var(--t-red-bg); border-color: var(--t-red-tx); border-width: 1px;">
            <p class="text-sm font-semibold" style="color: var(--t-red-tx);">{{ session('error') }}</p>
        </div>
    @endif

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif-brand text-xl" style="color: var(--t-dark);">Device Absensi</h1>
            <p class="text-sm mt-1" style="color: var(--t-muted);">Kelola terminal absensi fisik di sekolah Anda.</p>
        </div>
        <a href="{{ route('absensi.devices.create') }}" class="px-4 py-2.5 rounded-xl text-sm font-bold" style="background: var(--t-dark); color: #F6F3EC;">
            + Tambah Device
        </a>
    </div>

    <div class="t-card">
        <table class="t-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Kode</th>
                    <th>Metode</th>
                    <th>Lokasi</th>
                    <th>Terakhir Aktif</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr>
                        <td class="font-medium" style="color: var(--t-dark);">{{ $device->name }}</td>
                        <td class="font-mono text-xs">{{ $device->device_code }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse (($device->capabilities ?? []) as $cap)
                                    <span class="badge badge-slate">{{ \App\Models\Absensi\Device::CAPABILITIES[$cap] ?? $cap }}</span>
                                @empty
                                    <span class="text-xs" style="color: var(--t-muted);">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td>{{ $device->location ?? '—' }}</td>
                        <td>{{ $device->last_seen_at?->diffForHumans() ?? 'Belum pernah' }}</td>
                        <td>
                            @if ($device->is_active)
                                <span class="badge badge-green">Aktif</span>
                            @else
                                <span class="badge badge-slate">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center gap-3 justify-end">
                                <a href="{{ route('absensi.devices.edit', $device) }}" class="text-xs font-semibold" style="color: var(--t-dark);">Edit</a>

                                <form method="POST" action="{{ route('absensi.devices.regenerate-key', $device) }}" onsubmit="return confirm('Regenerate API key untuk {{ $device->name }}? Key lama otomatis tidak berlaku.');">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold" style="color: var(--t-amber-tx);">Regenerate Key</button>
                                </form>

                                <form method="POST" action="{{ route('absensi.devices.destroy', $device) }}" onsubmit="return confirm('Hapus device {{ $device->name }}? Tindakan ini permanen.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold" style="color: var(--t-red-tx);">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8" style="color: var(--t-muted);">
                            Belum ada device terdaftar. Klik "+ Tambah Device" untuk mulai.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $devices->links() }}
    </div>

@endsection