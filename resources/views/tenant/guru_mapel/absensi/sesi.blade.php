@extends('tenant.layouts.app')

@section('title', $subject_name . ' — Isi Absensi')
@section('page-title', 'Isi Absensi: ' . $subject_name)

@section('content')
@php
    // Gaya tombol status saat terpilih. Class ditulis utuh supaya terbaca oleh Tailwind.
    $statusStyles = [
        'Hadir' => 'peer-checked:border-[#1B3A34] peer-checked:bg-[#1B3A34] peer-checked:text-white',
        'Sakit' => 'peer-checked:border-amber-500 peer-checked:bg-amber-500 peer-checked:text-white',
        'Izin' => 'peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white',
        'Alpa' => 'peer-checked:border-red-600 peer-checked:bg-red-600 peer-checked:text-white',
    ];
@endphp

<div class="space-y-4" x-data="{ sending: false }">
    <a href="{{ route('guru_mapel.absensi.detail', $schedule_id) }}" class="text-sm text-gray-500 hover:underline">
        &larr; Kembali ke detail jadwal
    </a>

    <div class="t-card p-5">
        <div class="font-semibold">
            {{ $subject_name }}
            @if($class_name)<span class="font-normal text-gray-500">— {{ $class_name }}</span>@endif
        </div>
        <div class="text-xs text-gray-500">
            {{ $start_time }} – {{ $end_time }} · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d M Y') }}
        </div>
        @if($session_exists)
            <div class="mt-2 text-xs text-gray-500">
                Absensi jam ini sudah tersimpan. Menyimpan lagi akan memperbarui datanya.
            </div>
        @endif
    </div>

    @if(session('success'))
        <div role="status" class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div role="alert" class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800">
            @if($errors->has('general'))
                {{ $errors->first('general') }}
            @else
                {{ $errors->first() }}
            @endif
        </div>
    @endif

    @if($daily_unavailable)
        <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Absensi harian tidak dapat dibaca saat ini, jadi semua murid diisi Hadir. Periksa dan sesuaikan sebelum menyimpan.
        </div>
    @endif

    @if($rows->isEmpty())
        <div class="t-card p-6 text-center text-gray-500">Belum ada murid aktif di kelas ini.</div>
    @else
        <form method="POST" action="{{ route('guru_mapel.absensi.sesi.store', $schedule_id) }}" @submit="sending = true" class="space-y-4">
            @csrf

            <div class="t-card p-5 space-y-4">
                <div>
                    <label for="topic" class="block text-sm font-medium text-gray-700">Topik pembelajaran <span class="font-normal text-gray-400">(opsional)</span></label>
                    <textarea id="topic" name="topic" rows="2" maxlength="500"
                              class="mt-1.5 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B3A34]">{{ old('topic', $topic) }}</textarea>
                    @error('topic')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="session_notes" class="block text-sm font-medium text-gray-700">Catatan sesi <span class="font-normal text-gray-400">(opsional)</span></label>
                    <input id="session_notes" type="text" name="notes" maxlength="1000" value="{{ old('notes', $session_notes) }}"
                           class="mt-1.5 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#1B3A34]">
                    @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="t-card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-3">
                    <div class="text-sm text-gray-600">{{ $rows->count() }} murid</div>
                    <button type="button"
                            @click="$root.querySelectorAll('input[type=radio][value=Hadir]').forEach(r => r.checked = true)"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Tandai semua Hadir
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="t-table">
                        <thead>
                            <tr>
                                <th class="w-10">No</th>
                                <th>Nama</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $r)
                                @php
                                    $current = old('students.' . $r['id'] . '.status', $r['status']);
                                @endphp
                                <tr>
                                    <td class="text-gray-400">{{ $i + 1 }}</td>
                                    <td>
                                        {{ $r['nama'] }}
                                        @if($r['source'] === 'harian')
                                            <div class="text-xs text-gray-400">dari absensi harian</div>
                                        @endif
                                    </td>
                                    <td>
                                        <fieldset>
                                            <legend class="sr-only">Status {{ $r['nama'] }}</legend>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach($statuses as $status)
                                                    {{-- relative: input sr-only (absolute) harus berposisi relatif ke label ini. Tanpa itu, fokus
                                                         ke radio tersembunyi bisa menggulir wadah overflow di layout dan menggeser halaman. --}}
                                                    <label class="relative cursor-pointer">
                                                        <input type="radio" class="peer sr-only"
                                                               name="students[{{ $r['id'] }}][status]"
                                                               value="{{ $status }}"
                                                               @checked($current === $status)>
                                                        <span class="inline-block rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-600 peer-focus-visible:ring-2 peer-focus-visible:ring-[#1B3A34] peer-focus-visible:ring-offset-1 {{ $statusStyles[$status] }}">{{ $status }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                    </td>
                                    <td>
                                        <input type="text" name="students[{{ $r['id'] }}][notes]" maxlength="500"
                                               value="{{ old('students.' . $r['id'] . '.notes', $r['notes']) }}"
                                               aria-label="Catatan untuk {{ $r['nama'] }}"
                                               class="w-full min-w-[8rem] rounded-md border border-gray-200 px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-[#1B3A34]">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="sending"
                        class="rounded-md bg-[#1B3A34] px-6 py-2.5 text-sm font-medium text-white hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="sending ? 'Menyimpan...' : 'Simpan absensi'">Simpan absensi</span>
                </button>
            </div>
        </form>
    @endif
</div>
@endsection