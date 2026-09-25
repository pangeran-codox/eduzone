@extends('tenant.layouts.app')

@section('title', 'Kredensial Absensi')
@section('page-title', 'Kredensial QR — Siswa & Guru')

@section('content')
<div class="space-y-4">
    @if($errors->any())
        <div class="t-card p-4 text-red-600 text-sm">{{ $errors->first() }}</div>
    @endif
    @if(session('warning'))
        <div class="t-card p-4 text-amber-600 text-sm">{{ session('warning') }}</div>
    @endif

    <div class="t-card p-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Tipe</label>
                <select name="person_type" onchange="this.form.submit()" class="border rounded px-3 py-2 text-sm">
                    <option value="student" {{ $personType === 'student' ? 'selected' : '' }}>Siswa</option>
                    <option value="teacher" {{ $personType === 'teacher' ? 'selected' : '' }}>Guru</option>
                    <option value="staff" {{ $personType === 'staff' ? 'selected' : '' }}>Staff</option>
                </select>
            </div>
            @if($personType === 'student')
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Kelas</label>
                    <select name="class_id" onchange="this.form.submit()" class="border rounded px-3 py-2 text-sm">
                        <option value="">Pilih kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ $selectedClassId == $c->id ? 'selected' : '' }}>
                                {{ $c->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </form>

        @if($personType === 'student' && $selectedClassId)
            <form method="POST" action="{{ route('tu.kredensial.generate-class', $selectedClassId) }}" class="ml-auto">
                @csrf
                <button type="submit" class="badge badge-gold px-3 py-1.5">
                    Generate + Cetak Semua di Kelas Ini
                </button>
            </form>
        @elseif(in_array($personType, ['teacher', 'staff']) && $rows->isNotEmpty())
            <form method="POST" action="{{ route('tu.kredensial.generate-type', $personType) }}" class="ml-auto"
                onsubmit="return confirm('Generate ulang QR untuk SEMUA {{ $personType === 'teacher' ? 'guru' : 'staff' }} yang sudah punya kredensial juga akan menggantikan kredensial lama mereka. Lanjutkan?')">
                @csrf
                <button type="submit" class="badge badge-gold px-3 py-1.5">
                    Generate + Cetak Semua {{ $personType === 'teacher' ? 'Guru' : 'Staff' }}
                </button>
            </form>
        @endif
    </div>

    <div class="t-card overflow-hidden">
        <table class="t-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Status Kredensial</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['full_name'] }}</td>
                        <td>
                            <span class="badge {{ $r['has_credential'] ? 'badge-green' : 'badge-slate' }}">
                                {{ $r['has_credential'] ? 'Sudah punya QR' : 'Belum ada' }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('tu.kredensial.generate', [$r['person_type'], $r['person_id']]) }}">
                                @csrf
                                <button type="submit" class="text-sm text-indigo-600 hover:underline">
                                    {{ $r['has_credential'] ? 'Generate Ulang' : 'Generate QR' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-gray-400 py-6">
                            {{ $personType === 'student' ? 'Pilih kelas dulu.' : 'Tidak ada data.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection