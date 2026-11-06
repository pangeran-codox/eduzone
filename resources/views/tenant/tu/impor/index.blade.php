@extends('tenant.layouts.app')

@section('title', 'Impor Data')
@section('page-title', 'Impor Data')

@section('content')
@php
    $statusLabels = [
        'validated' => ['Menunggu persetujuan', 'badge-amber'],
        'queued' => ['Antre', 'badge-gold'],
        'running' => ['Berjalan', 'badge-gold'],
        'completed' => ['Selesai', 'badge-green'],
        'stopped' => ['Dihentikan', 'badge-amber'],
        'failed' => ['Gagal', 'badge-red'],
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div role="status" class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <div class="t-card p-5">
        <div class="font-semibold">Isi data sekolah lewat Excel</div>
        <p class="mt-1 text-sm text-gray-600">
            Kerjakan berurutan: <strong>Kelas</strong> lebih dulu, lalu <strong>Siswa</strong> dan <strong>Guru</strong>.
            Setiap file diperiksa dulu tanpa menyimpan apa pun. Anda melihat hasilnya, lalu memutuskan apakah akan mengimpor.
            Baris yang sudah ada dilewati, jadi file yang sudah diperbaiki aman diunggah ulang.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        @foreach($importers as $type => $importer)
            <div class="t-card flex flex-col p-5">
                <div class="font-semibold">{{ $loop->iteration }}. {{ $importer->label() }}</div>
                <p class="mt-1 flex-1 text-sm text-gray-600">{{ $importer->description() }}</p>

                <a href="{{ route('tu.impor.template', $type) }}"
                   class="mt-4 inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34]">
                    Unduh template (.xlsx)
                </a>

                <form method="POST" action="{{ route('tu.impor.store') }}" enctype="multipart/form-data"
                      x-data="{ sending: false }" @submit="sending = true" class="mt-4 space-y-2">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <label for="file-{{ $type }}" class="block text-xs font-medium text-gray-600">File Excel yang sudah diisi</label>
                    <input id="file-{{ $type }}" type="file" name="file" accept=".xlsx" required
                           class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                    <button type="submit" :disabled="sending"
                            class="w-full rounded-md bg-[#1B3A34] px-3 py-2 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="sending ? 'Memeriksa...' : 'Periksa file'">Periksa file</span>
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="t-card overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-3 font-semibold">Riwayat impor</div>
        <div class="overflow-x-auto">
            <table class="t-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Jenis</th>
                        <th>File</th>
                        <th>Status</th>
                        <th>Hasil</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $b)
                        @php [$label, $badge] = $statusLabels[$b->status] ?? [$b->status, 'badge-slate']; @endphp
                        <tr>
                            <td class="whitespace-nowrap">{{ $b->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td>{{ ($importers[$b->type] ?? null)?->label() ?? $b->type }}</td>
                            <td class="max-w-[14rem] truncate" title="{{ $b->original_name }}">{{ $b->original_name }}</td>
                            <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                            <td class="whitespace-nowrap text-xs text-gray-600">
                                @if($b->isFinished() && $b->status !== 'failed')
                                    {{ $b->created_count }} dibuat · {{ $b->skipped_count }} dilewati · {{ $b->failed_count }} gagal
                                @elseif($b->status === 'validated')
                                    {{ $b->valid_rows }} siap · {{ $b->error_rows }} bermasalah
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-right">
                                <a href="{{ route('tu.impor.show', $b) }}" class="text-sm font-medium text-[#1B3A34] hover:underline">Buka &rarr;</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-gray-400">Belum ada impor.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
