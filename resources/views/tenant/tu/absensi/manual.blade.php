{{-- resources/views/tenant/tu/absensi/manual.blade.php --}}
@extends('tenant.layouts.app')

@section('title', 'Input Manual Absensi')
@section('page-title', 'Input Manual Izin/Sakit/Alpa')

@section('content')
<div
    x-data="{
        date: @js($date),
        search: '',
        personType: 'student',
        people: @js($people),
        selectedId: '',
        selectedType: '',
        status: 'Sakit',
        notes: '',

        get filtered() {
            const q = this.search.toLowerCase();
            return this.people
                .filter(p => p.person_type === this.personType)
                .filter(p => !q || p.full_name.toLowerCase().includes(q));
        },

        select(p) {
            this.selectedId = p.person_id;
            this.selectedType = p.person_type;
            this.search = p.full_name;
        },
    }"
    class="max-w-2xl space-y-5"
>

    @if (session('success'))
        <div class="t-card p-4" style="background: var(--t-green-bg); border-color: var(--t-green-tx); border-width: 1px;">
            <p class="text-sm font-semibold" style="color: var(--t-green-tx);">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="t-card p-4" style="background: var(--t-red-bg); border-color: var(--t-red-tx); border-width: 1px;">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-semibold" style="color: var(--t-red-tx);">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="t-card p-6">
        <p class="text-sm mb-5" style="color: var(--t-muted);">
            Gunakan untuk mencatat siswa/guru yang tidak masuk karena Sakit, Izin, atau Alpa —
            status yang tidak bisa terdeteksi dari perangkat absensi.
        </p>

        <form method="POST" action="{{ route('tu.absensi.manual.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="person_id" :value="selectedId">
            <input type="hidden" name="person_type" :value="selectedType">

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Tanggal</label>
                    <input type="date" name="date" x-model="date"
                           class="w-full border rounded-lg px-3 py-2 text-sm"
                           style="border-color:var(--t-border);">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Tipe</label>
                    <select x-model="personType" @change="selectedId = ''; selectedType = ''; search = ''"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            style="border-color:var(--t-border);">
                        <option value="student">Siswa</option>
                        <option value="teacher">Guru</option>
                    </select>
                </div>
            </div>

            <div class="relative">
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Cari Nama</label>
                <input type="text" x-model="search" @input="selectedId = ''"
                       placeholder="Ketik nama siswa/guru…"
                       class="w-full border rounded-lg px-3 py-2 text-sm"
                       style="border-color:var(--t-border);">

                <div x-show="search && !selectedId && filtered.length > 0"
                     class="absolute z-10 w-full mt-1 t-card max-h-56 overflow-y-auto">
                    <template x-for="p in filtered.slice(0, 10)" :key="p.person_type + p.person_id">
                        <button type="button" @click="select(p)"
                                class="w-full text-left px-4 py-2.5 text-sm hover:opacity-80 border-b"
                                style="border-color:var(--t-border);">
                            <span x-text="p.full_name"></span>
                            <span x-show="p.grade" class="ml-1 text-xs" style="color:var(--t-muted);" x-text="p.grade"></span>
                        </button>
                    </template>
                </div>

                <p x-show="search && !selectedId && filtered.length === 0" class="text-xs mt-1.5" style="color:var(--t-muted);">
                    Tidak ditemukan.
                </p>

                <p x-show="selectedId" class="text-xs mt-1.5" style="color:var(--t-green-tx);">
                    ✓ Terpilih
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Status</label>
                <div class="flex gap-2">
                    <template x-for="s in ['Sakit', 'Izin', 'Alpa']" :key="s">
                        <button type="button" @click="status = s"
                                :class="status === s ? 'font-bold' : ''"
                                class="flex-1 px-4 py-2 rounded-lg text-sm border transition-colors"
                                :style="status === s
                                    ? 'background: var(--t-gold); border-color: var(--t-gold); color: var(--t-dark);'
                                    : 'border-color: var(--t-border); color: var(--t-muted);'"
                                x-text="s"></button>
                    </template>
                </div>
                <input type="hidden" name="status" :value="status">
            </div>

            <div>
                <label class="block text-xs font-semibold mb-1.5" style="color:var(--t-muted);">Catatan (opsional)</label>
                <textarea name="notes" x-model="notes" rows="2"
                          placeholder="Mis. Sakit demam, ada surat keterangan dokter."
                          class="w-full border rounded-lg px-3 py-2 text-sm"
                          style="border-color:var(--t-border);"></textarea>
            </div>

            <button type="submit" :disabled="!selectedId"
                    :style="selectedId
                        ? 'background: var(--t-dark); color: #F6F3EC; cursor: pointer;'
                        : 'background: var(--t-slate-bg); color: var(--t-muted); cursor: not-allowed;'"
                    class="w-full py-3 rounded-xl font-bold text-sm transition-colors">
                Simpan
            </button>
        </form>
    </div>

    {{-- Daftar yang sudah diinput manual hari ini --}}
    <div class="t-card overflow-hidden">
        <div class="px-5 py-3.5 border-b" style="border-color:var(--t-border);">
            <span class="font-semibold text-sm" style="color:var(--t-dark);">Sudah Dicatat — {{ $date }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="t-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($existing as $row)
                        <tr>
                            <td class="font-medium" style="color:var(--t-dark);">{{ $row['full_name'] }}</td>
                            <td style="color:var(--t-muted);">{{ $row['person_type'] === 'student' ? 'Siswa' : 'Guru' }}</td>
                            <td>
                                <span class="badge {{ $row['status'] === 'Sakit' ? 'badge-slate' : ($row['status'] === 'Izin' ? '' : 'badge-red') }}">
                                    {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="text-sm" style="color:var(--t-muted);">{{ $row['notes'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-sm" style="color:var(--t-muted);">
                                Belum ada input manual untuk tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection