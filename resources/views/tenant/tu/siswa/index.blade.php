@extends('tenant.layouts.app')

@section('title', 'Data Siswa')
@section('page-title', 'Data Siswa')

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
        <h1 class="text-xl font-bold mb-1" style="color: var(--t-dark);">Data Siswa</h1>
        <p class="text-sm" style="color: var(--t-muted);">{{ $students->total() }} siswa terdaftar</p>
    </div>
    <a href="{{ route('tu.siswa.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
       style="background: var(--t-dark);">
        + Tambah Siswa
    </a>
</div>

<form method="GET" action="{{ route('tu.siswa.index') }}" class="t-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama siswa..." class="form-input flex-1">
    <select name="status" class="form-input sm:w-48">
        <option value="">Semua Status</option>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold" style="background: var(--t-slate-bg); color: var(--t-dark);">Cari</button>
    @if ($search || $statusFilter)
    <a href="{{ route('tu.siswa.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color: var(--t-muted);">Reset</a>
    @endif
</form>

<div class="t-card overflow-hidden">
    @if ($students->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color: var(--t-muted);">
            {{ $search || $statusFilter ? 'Tidak ada siswa yang cocok dengan pencarian.' : 'Belum ada data siswa.' }}
        </p>
    </div>
    @else
    <table class="t-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Tingkat</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($students as $student)
            <tr>
                <td>
                    <p class="font-semibold" style="color: var(--t-text);">{{ $student->full_name }}</p>
                    <p class="text-xs" style="color: var(--t-muted);">{{ $student->email ?? '—' }}</p>
                </td>
                <td>{{ $student->class->nama_kelas ?? '—' }}</td>
                <td>{{ $student->grade ?? '—' }}</td>
                <td>
                    <span class="badge {{ $student->status === 'aktif' ? 'badge-green' : 'badge-slate' }}">
                        {{ $statuses[$student->status] ?? ucfirst($student->status) }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-3 justify-end">
                        <a href="{{ route('tu.siswa.edit', $student) }}" class="text-xs font-semibold" style="color: var(--t-dark);">Edit</a>
                        <form method="POST" action="{{ route('tu.siswa.destroy', $student) }}"
                              onsubmit="return confirm('Hapus data siswa {{ $student->full_name }}? Tindakan ini tidak bisa dibatalkan.');">
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
        {{ $students->links() }}
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