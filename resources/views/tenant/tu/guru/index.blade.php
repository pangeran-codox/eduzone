@extends('tenant.layouts.app')

@section('title', 'Data Guru')
@section('page-title', 'Data Guru')

@section('content')

@if (session('success'))
<div class="t-card p-4 mb-6" style="border-color: var(--t-green-tx);">
    <p class="text-sm" style="color: var(--t-green-tx);">{{ session('success') }}</p>
</div>
@endif

@if (session('error'))
<div class="t-card p-4 mb-6" style="border-color: var(--t-red-tx);">
    <p class="text-sm" style="color: var(--t-red-tx);">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold mb-1" style="color: var(--t-dark);">Data Guru</h1>
        <p class="text-sm" style="color: var(--t-muted);">{{ $teachers->total() }} guru terdaftar</p>
    </div>
    <a href="{{ route('tu.guru.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
       style="background: var(--t-dark);">
        + Tambah Guru
    </a>
</div>

<form method="GET" action="{{ route('tu.guru.index') }}" class="t-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama guru..." class="form-input flex-1">
    <select name="active" class="form-input sm:w-48">
        <option value="">Semua Status</option>
        <option value="1" {{ $activeFilter === '1' ? 'selected' : '' }}>Aktif</option>
        <option value="0" {{ $activeFilter === '0' ? 'selected' : '' }}>Nonaktif</option>
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold" style="background: var(--t-slate-bg); color: var(--t-dark);">Cari</button>
    @if ($search || $activeFilter !== '')
    <a href="{{ route('tu.guru.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color: var(--t-muted);">Reset</a>
    @endif
</form>

<div class="t-card overflow-hidden">
    @if ($teachers->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color: var(--t-muted);">
            {{ $search || $activeFilter !== '' ? 'Tidak ada guru yang cocok dengan pencarian.' : 'Belum ada data guru.' }}
        </p>
    </div>
    @else
    <table class="t-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Mapel/Jurusan</th>
                <th>Status Kepegawaian</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teachers as $teacher)
            <tr>
                <td>
                    <p class="font-semibold" style="color: var(--t-text);">
                        {{ $teacher->full_name }}
                        @if ($teacher->is_homeroom)
                            <span class="badge badge-gold ml-1">Wali Kelas</span>
                        @endif
                    </p>
                    <p class="text-xs" style="color: var(--t-muted);">{{ $teacher->email ?? '—' }}</p>
                </td>
                <td>{{ $teacher->major->name ?? '—' }}</td>
                <td>{{ $teacher->employment_status ?? '—' }}</td>
                <td>
                    <span class="badge {{ $teacher->is_active ? 'badge-green' : 'badge-slate' }}">
                        {{ $teacher->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-3 justify-end">
                        <a href="{{ route('tu.guru.edit', $teacher) }}" class="text-xs font-semibold" style="color: var(--t-dark);">Edit</a>
                        <form method="POST" action="{{ route('tu.guru.destroy', $teacher) }}"
                              onsubmit="return confirm('Hapus data guru {{ $teacher->full_name }}? Tindakan ini tidak bisa dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold" style="color: var(--t-red-tx);">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-5 py-4 border-t" style="border-color: var(--t-border);">
        {{ $teachers->links() }}
    </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .form-input {
        padding: 10px 14px; border-radius: 10px;
        background: #fff; border: 1px solid var(--t-border);
        color: var(--t-text); font-size: 13.5px;
    }
    .form-input:focus { outline: none; border-color: var(--t-dark); }
</style>
@endpush