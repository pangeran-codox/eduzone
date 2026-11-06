@extends('tenant.layouts.app')

@section('title', 'Guru Pengganti')
@section('page-title', 'Penugasan Guru Pengganti')

@section('content')
<div class="space-y-4">
    @if(session('success'))
        <div class="t-card p-4 text-green-700 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="t-card p-4 text-amber-600 text-sm">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="t-card p-4 text-red-600 text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="GET" class="t-card p-4 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
            <input type="date" name="date" value="{{ $date }}" class="border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="badge badge-gold px-4 py-2">Tampilkan</button>
        <div class="ml-auto text-sm text-gray-500">{{ $dayName }} · {{ $rows->count() }} jadwal</div>
    </form>

    <div class="t-card overflow-hidden">
        <table class="t-table">
            <thead>
                <tr>
                    <th>Jam</th>
                    <th>Kelas</th>
                    <th>Mapel</th>
                    <th>Guru Asli</th>
                    <th>Guru Pengganti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>{{ $r['time'] }}</td>
                        <td>{{ $r['class_name'] }}</td>
                        <td>{{ $r['subject'] }}</td>
                        <td>{{ $r['original_teacher'] }}</td>
                        <td>
                            @if($r['substitution'])
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="badge badge-gold">{{ $r['substitution']['teacher'] }}</span>
                                    @if($r['substitution']['reason'])
                                        <span class="text-xs text-gray-500">{{ $r['substitution']['reason'] }}</span>
                                    @endif
                                    <form method="POST"
                                          action="{{ route('kurikulum.pengganti.destroy', $r['substitution']['id']) }}"
                                          onsubmit="return confirm('Batalkan penggantian ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:underline">Batalkan</button>
                                    </form>
                                </div>
                            @else
                                <form method="POST" action="{{ route('kurikulum.pengganti.store') }}"
                                      class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="hidden" name="schedule_id" value="{{ $r['schedule_id'] }}">
                                    <input type="hidden" name="date" value="{{ $date }}">
                                    <select name="substitute_teacher_id" required class="border rounded px-2 py-1 text-sm">
                                        <option value="">Pilih guru pengganti</option>
                                        @foreach($teachers as $t)
                                            @if($t->id !== $r['original_teacher_id'])
                                                <option value="{{ $t->id }}">{{ $t->full_name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <input type="text" name="reason" maxlength="255" placeholder="Alasan (opsional)"
                                           class="border rounded px-2 py-1 text-sm">
                                    <button type="submit" class="text-sm text-indigo-600 hover:underline">Tunjuk</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-6">
                            Tidak ada jadwal aktif pada hari {{ $dayName }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection