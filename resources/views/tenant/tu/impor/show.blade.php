@extends('tenant.layouts.app')

@section('title', 'Impor ' . $importer->label())
@section('page-title', 'Impor ' . $importer->label())

@section('content')
@php
    $errorList = $batch->errors ?? [];
    $failureList = $batch->failures ?? [];
    $statusLabels = [
        'validated' => ['Menunggu persetujuan Anda', 'badge-amber'],
        'queued' => ['Antre', 'badge-gold'],
        'running' => ['Sedang diimpor', 'badge-gold'],
        'completed' => ['Selesai', 'badge-green'],
        'stopped' => ['Dihentikan', 'badge-amber'],
        'failed' => ['Gagal', 'badge-red'],
    ];
    [$statusLabel, $statusBadge] = $statusLabels[$batch->status] ?? [$batch->status, 'badge-slate'];
@endphp

<div class="space-y-5">
    <a href="{{ route('tu.impor.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Kembali ke Impor Data</a>

    @if(session('success'))
        <div role="status" class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="t-card flex flex-wrap items-center justify-between gap-3 p-5">
        <div>
            <div class="font-semibold">{{ $batch->original_name }}</div>
            <div class="text-xs text-gray-500">{{ $importer->label() }} · diunggah {{ $batch->created_at?->translatedFormat('d M Y H:i') }}</div>
        </div>
        <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
    </div>

    {{-- ===================== GAGAL TOTAL ===================== --}}
    @if($batch->status === 'failed')
        <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $batch->message }}</div>
        <a href="{{ route('tu.impor.index') }}" class="inline-block rounded-md bg-[#1B3A34] px-4 py-2 text-sm font-medium text-white hover:opacity-90">Unggah file lain</a>

    {{-- ===================== HASIL PEMERIKSAAN (PRATINJAU) ===================== --}}
    @elseif($batch->status === 'validated')
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="t-card p-4"><div class="text-xs text-gray-500">Baris data</div><div class="mt-1 text-2xl font-semibold">{{ $batch->total_rows }}</div></div>
            <div class="t-card p-4"><div class="text-xs text-gray-500">Siap diimpor</div><div class="mt-1 text-2xl font-semibold text-green-700">{{ $batch->valid_rows }}</div></div>
            <div class="t-card p-4"><div class="text-xs text-gray-500">Bermasalah (tidak diimpor)</div><div class="mt-1 text-2xl font-semibold {{ $batch->error_rows > 0 ? 'text-red-700' : '' }}">{{ $batch->error_rows }}</div></div>
        </div>

        @if(! empty($batch->notes))
            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach($batch->notes as $note)
                        <li>{{ $note }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($errorList !== [])
            <div class="t-card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                    <div class="font-semibold">Baris bermasalah</div>
                    <a href="{{ route('tu.impor.errors', $batch) }}" class="text-sm font-medium text-[#1B3A34] hover:underline">Unduh daftar lengkap (.xlsx)</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="t-table">
                        <thead><tr><th class="w-24">Baris Excel</th><th class="w-44">Kolom</th><th>Masalah</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($errorList, 0, 30) as $error)
                                <tr>
                                    <td>{{ $error['line'] }}</td>
                                    <td>{{ $error['column'] }}</td>
                                    <td>{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(count($errorList) > 30)
                    <div class="border-t border-gray-100 px-5 py-3 text-xs text-gray-500">Menampilkan 30 dari {{ count($errorList) }} masalah. Daftar lengkap ada di file unduhan.</div>
                @endif
            </div>
        @endif

        <div class="t-card p-5">
            @if($batch->valid_rows > 0)
                <p class="text-sm text-gray-700">
                    Yang diimpor hanya <strong>{{ $batch->valid_rows }}</strong> baris yang siap.
                    @if($batch->error_rows > 0)
                        Baris bermasalah dilewati: perbaiki di Excel, lalu unggah ulang file yang sama. Baris yang sudah masuk otomatis dilewati.
                    @endif
                    @if($importer->createsAccounts())
                        Akun login dibuat otomatis untuk setiap baris; daftar username dan kata sandi awal tersedia setelah impor selesai.
                    @endif
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('tu.impor.confirm', $batch) }}" x-data="{ busy: false }"
                          @submit="if (! confirm('Impor {{ $batch->valid_rows }} baris sekarang?')) { $event.preventDefault(); return; } busy = true">
                        @csrf
                        <button type="submit" :disabled="busy"
                                class="rounded-md bg-[#1B3A34] px-5 py-2.5 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-text="busy ? 'Memulai...' : 'Impor {{ $batch->valid_rows }} baris'">Impor {{ $batch->valid_rows }} baris</span>
                        </button>
                    </form>
                    <a href="{{ route('tu.impor.index') }}" class="text-sm text-gray-600 hover:underline">Batal, unggah file lain</a>
                </div>
            @else
                <p class="text-sm text-gray-700">Tidak ada baris baru yang bisa diimpor dari file ini. Perbaiki masalah di atas atau unggah file lain.</p>
                <a href="{{ route('tu.impor.index') }}" class="mt-4 inline-block rounded-md bg-[#1B3A34] px-4 py-2 text-sm font-medium text-white hover:opacity-90">Unggah file lain</a>
            @endif
        </div>

    {{-- ===================== SEDANG BERJALAN ===================== --}}
    @elseif($batch->isActive())
        <div class="t-card p-5" id="progress-card">
            <div class="flex items-center justify-between text-sm">
                <span id="progress-text">Memproses {{ $batch->processed_rows }} dari {{ $batch->total_rows }} baris...</span>
                <span id="progress-percent" class="font-semibold">{{ $batch->progressPercent() }}%</span>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $batch->progressPercent() }}">
                <div id="progress-bar" class="h-full bg-[#1B3A34] transition-all motion-reduce:transition-none" style="width: {{ $batch->progressPercent() }}%"></div>
            </div>
            <div class="mt-3 text-xs text-gray-500" id="progress-counts">
                {{ $batch->created_count }} dibuat · {{ $batch->skipped_count }} dilewati · {{ $batch->failed_count }} gagal
            </div>
            <p class="mt-3 text-xs text-gray-500">Halaman ini memperbarui diri. Anda boleh menutupnya; impor tetap berjalan di latar belakang.</p>
        </div>

        <script>
            (function () {
                var url = @json(route('tu.impor.progress', $batch));

                function tick() {
                    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                        .then(function (r) { return r.json(); })
                        .then(function (d) {
                            document.getElementById('progress-bar').style.width = d.percent + '%';
                            document.getElementById('progress-percent').textContent = d.percent + '%';
                            document.getElementById('progress-text').textContent = 'Memproses ' + d.processed + ' dari ' + d.total + ' baris...';
                            document.getElementById('progress-counts').textContent = d.created + ' dibuat · ' + d.skipped + ' dilewati · ' + d.failed + ' gagal';

                            if (d.finished) { window.location.reload(); return; }
                            setTimeout(tick, 3000);
                        })
                        .catch(function () { setTimeout(tick, 5000); });
                }

                setTimeout(tick, 2000);
            })();
        </script>

    {{-- ===================== SELESAI / DIHENTIKAN ===================== --}}
    @else
        @if($batch->message)
            <div role="alert" class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $batch->message }}</div>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="t-card p-4"><div class="text-xs text-gray-500">Berhasil dibuat</div><div class="mt-1 text-2xl font-semibold text-green-700">{{ $batch->created_count }}</div></div>
            <div class="t-card p-4"><div class="text-xs text-gray-500">Dilewati (sudah ada)</div><div class="mt-1 text-2xl font-semibold">{{ $batch->skipped_count }}</div></div>
            <div class="t-card p-4"><div class="text-xs text-gray-500">Gagal disimpan</div><div class="mt-1 text-2xl font-semibold {{ $batch->failed_count > 0 ? 'text-red-700' : '' }}">{{ $batch->failed_count }}</div></div>
        </div>

        @if($batch->error_rows > 0)
            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $batch->error_rows }} baris tidak diimpor karena bermasalah pada pemeriksaan.
                <a href="{{ route('tu.impor.errors', $batch) }}" class="font-medium underline">Unduh daftar masalah</a>,
                perbaiki di Excel, lalu unggah ulang file yang sama.
            </div>
        @endif

        @if($failureList !== [])
            <div class="t-card overflow-hidden">
                <div class="border-b border-gray-100 px-5 py-3 font-semibold">Baris yang gagal disimpan</div>
                <div class="overflow-x-auto">
                    <table class="t-table">
                        <thead><tr><th class="w-24">Baris Excel</th><th>Alasan</th></tr></thead>
                        <tbody>
                            @foreach(array_slice($failureList, 0, 30) as $failure)
                                <tr><td>{{ $failure['line'] }}</td><td>{{ $failure['message'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if($credentialsAvailable)
            <div class="t-card p-5">
                <div class="font-semibold">Daftar akun login</div>
                <p class="mt-1 text-sm text-gray-600">
                    Berisi username dan <strong>kata sandi awal</strong> setiap akun yang baru dibuat (kata sandi awal = tanggal lahir DDMMYYYY).
                    Bagikan kepada pemiliknya secara pribadi dan jangan disebar. File ini otomatis dihapus pada
                    {{ $batch->expires_at?->translatedFormat('d M Y H:i') }}.
                </p>
                <a href="{{ route('tu.impor.credentials', $batch) }}"
                   class="mt-4 inline-block rounded-md bg-[#1B3A34] px-4 py-2 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2">
                    Unduh daftar akun (.xlsx)
                </a>
            </div>
        @elseif($importer->createsAccounts() && $batch->created_count > 0)
            <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">Daftar akun sudah kedaluwarsa dan dihapus. Kata sandi awal akun = tanggal lahir DDMMYYYY.</div>
        @endif

        <div class="flex flex-wrap items-center gap-4">
            @if($next && $batch->created_count + $batch->skipped_count > 0)
                <a href="{{ route('tu.impor.index') }}" class="rounded-md bg-[#1B3A34] px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                    Langkah berikutnya: impor {{ ucfirst($next) }}
                </a>
            @endif
            <a href="{{ route('tu.impor.index') }}" class="text-sm text-gray-600 hover:underline">Kembali ke Impor Data</a>
        </div>
    @endif
</div>
@endsection
