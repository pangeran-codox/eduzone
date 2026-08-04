<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi — EduZone</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/areas/tenant.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui; }
        .voice { font-family: 'Fraunces', serif; }
    </style>
</head>
<body class="antialiased" style="background:#F6F3EC; color:#1C1C1A;">

    <div x-data="{
            statusFilter: 'semua',
            search: '',
            get filteredRecords() {
                return {{ Illuminate\Support\Js::from($records ?? []) }}.filter(r => {
                    const matchStatus = this.statusFilter === 'semua' || r.status === this.statusFilter;
                    const matchSearch = this.search === '' || r.nama.toLowerCase().includes(this.search.toLowerCase());
                    return matchStatus && matchSearch;
                });
            }
         }"
         class="min-h-screen flex flex-col">

        {{-- Topbar --}}
        <header class="sticky top-0 z-30 border-b" style="background:#FFFFFF; border-color:#E4E0D4;">
            <div class="max-w-6xl mx-auto px-5 h-16 flex items-center justify-between">
                <span class="voice text-lg font-semibold" style="color:#1B3A34;">EduZone</span>
                <div class="flex items-center gap-3">
                    <span class="text-sm" style="color:#6B6B63;">{{ auth()->user()->name ?? 'Wali Kelas' }}</span>
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold"
                         style="background:#1B3A34; color:#C9A227;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'WK', 0, 2)) }}
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-6xl mx-auto w-full px-5 py-8 flex-1">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-7">
                <div>
                    <h1 class="voice text-2xl font-semibold" style="color:#1B3A34;">Rekap Absensi</h1>
                    <p class="text-sm mt-0.5" style="color:#6B6B63;">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>

                @if (isset($classes) && count($classes))
                    <select class="h-10 rounded-md px-3 text-sm border bg-white" style="border-color:#D8D4C6; color:#1C1C1A;">
                        @foreach ($classes as $class)
                            <option value="{{ $class['id'] }}">{{ $class['nama'] }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Stat cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
                @php
                    $statCards = [
                        ['label' => 'Hadir', 'key' => 'hadir', 'color' => '#3B6D11', 'bg' => '#EAF3DE'],
                        ['label' => 'Terlambat', 'key' => 'terlambat', 'color' => '#854F0B', 'bg' => '#FAEEDA'],
                        ['label' => 'Izin/Sakit', 'key' => 'izin', 'color' => '#185FA5', 'bg' => '#E6F1FB'],
                        ['label' => 'Alpa', 'key' => 'alpa', 'color' => '#A32D2D', 'bg' => '#FCEBEB'],
                        ['label' => 'Belum absen', 'key' => 'belum', 'color' => '#6B6B63', 'bg' => '#F1EFE8'],
                    ];
                @endphp
                @foreach ($statCards as $card)
                    <div class="rounded-xl p-4" style="background: {{ $card['bg'] }};">
                        <p class="text-xs font-medium mb-1" style="color: {{ $card['color'] }};">{{ $card['label'] }}</p>
                        <p class="text-2xl font-semibold" style="color:#1C1C1A;">
                            {{ $stats[$card['key']] ?? 0 }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- Filter row --}}
            <div class="flex flex-col sm:flex-row gap-3 mb-4">
                <div class="relative flex-1 max-w-xs">
                    <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="#8A8A80" stroke-width="2">
                        <circle cx="11" cy="11" r="7" stroke-linecap="round"/>
                        <path d="M21 21l-4.3-4.3" stroke-linecap="round"/>
                    </svg>
                    <input type="text" x-model="search" placeholder="Cari nama..."
                           class="w-full h-10 rounded-md pl-9 pr-3 text-sm border bg-white"
                           style="border-color:#D8D4C6; color:#1C1C1A;">
                </div>

                <div class="flex gap-1.5 flex-wrap">
                    @foreach (['semua' => 'Semua', 'hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin/Sakit', 'alpa' => 'Alpa', 'belum' => 'Belum absen'] as $key => $label)
                        <button
                            @click="statusFilter = '{{ $key }}'"
                            class="h-10 px-3.5 rounded-md text-xs font-medium border transition-colors"
                            :style="statusFilter === '{{ $key }}' ? 'background:#1B3A34; color:#F6F3EC; border-color:#1B3A34;' : 'background:#FFFFFF; color:#4A4A44; border-color:#D8D4C6;'"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Table --}}
            <div class="rounded-xl border overflow-hidden bg-white" style="border-color:#EDEAE0;">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="background:#F6F3EC;">
                            <th class="text-left font-medium px-4 py-3" style="color:#6B6B63;">Nama</th>
                            <th class="text-left font-medium px-4 py-3" style="color:#6B6B63;">Identitas</th>
                            <th class="text-left font-medium px-4 py-3" style="color:#6B6B63;">Waktu</th>
                            <th class="text-left font-medium px-4 py-3" style="color:#6B6B63;">Metode</th>
                            <th class="text-left font-medium px-4 py-3" style="color:#6B6B63;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="filteredRecords.length === 0">
                            <tr>
                                <td colspan="5" class="text-center px-4 py-10 text-sm" style="color:#9A9A8E;">
                                    Tidak ada data yang cocok dengan filter ini.
                                </td>
                            </tr>
                        </template>

                        <template x-for="record in filteredRecords" :key="record.identitas">
                            <tr class="border-t" style="border-color:#EDEAE0;">
                                <td class="px-4 py-3 font-medium" style="color:#1C1C1A;" x-text="record.nama"></td>
                                <td class="px-4 py-3" style="color:#6B6B63;" x-text="record.identitas"></td>
                                <td class="px-4 py-3" style="color:#6B6B63;" x-text="record.waktu ?? '—'"></td>
                                <td class="px-4 py-3" style="color:#6B6B63;" x-text="record.metode ?? '—'"></td>
                                <td class="px-4 py-3">
                                    <template x-if="record.status === 'hadir'">
                                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full" style="background:#EAF3DE; color:#3B6D11;">Hadir</span>
                                    </template>
                                    <template x-if="record.status === 'terlambat'">
                                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full" style="background:#FAEEDA; color:#854F0B;">Terlambat</span>
                                    </template>
                                    <template x-if="record.status === 'izin'">
                                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full" style="background:#E6F1FB; color:#185FA5;">Izin/Sakit</span>
                                    </template>
                                    <template x-if="record.status === 'alpa'">
                                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full" style="background:#FCEBEB; color:#A32D2D;">Alpa</span>
                                    </template>
                                    <template x-if="record.status === 'belum'">
                                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full" style="background:#F1EFE8; color:#6B6B63;">Belum absen</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

</body>
</html>