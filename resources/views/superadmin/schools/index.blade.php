@extends('superadmin.layouts.app')

@section('title', 'Kelola Sekolah')
@section('page-title', 'Kelola Sekolah')

@section('content')

@if (session('success'))
<div class="sa-card p-4 mb-6" style="border-color: rgba(16,185,129,0.3);">
    <p class="text-sm" style="color:#6ee7b7;">{{ session('success') }}</p>
</div>
@endif

@if (session('error'))
<div class="sa-card p-4 mb-6" style="border-color: rgba(239,68,68,0.3);">
    <p class="text-sm" style="color:#fca5a5;">{{ session('error') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Kelola Sekolah</h1>
        <p class="text-sm" style="color:#64748b;">{{ $schools->total() }} sekolah terdaftar</p>
    </div>
    <a href="{{ route('superadmin.schools.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
       style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
        + Tambah Sekolah
    </a>
</div>

{{-- ── Search & Filter ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('superadmin.schools.index') }}" class="sa-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}"
           placeholder="Cari nama sekolah, NPSN, atau kota..."
           class="form-input flex-1">
    <select name="plan" class="form-input sm:w-48">
        <option value="">Semua Paket</option>
        @foreach ($plans as $value => $label)
            <option value="{{ $value }}" {{ $planFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: rgba(99,102,241,0.2);">
        Cari
    </button>
    @if ($search || $planFilter)
    <a href="{{ route('superadmin.schools.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color:#64748b;">
        Reset
    </a>
    @endif
</form>

{{-- ── Tabel ────────────────────────────────────────────────────────── --}}
<div class="sa-card overflow-hidden">
    @if ($schools->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">
            {{ $search || $planFilter ? 'Tidak ada sekolah yang cocok dengan pencarian.' : 'Belum ada sekolah terdaftar.' }}
        </p>
    </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Sekolah</th>
                <th>Jenjang</th>
                <th>Kota</th>
                <th>Paket</th>
                <th>Berlaku Sampai</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($schools as $school)
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                            {{ strtoupper(substr($school->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-white leading-tight">{{ $school->name }}</p>
                            <p class="text-xs" style="color:#475569;">{{ $school->npsn ?? '—' }}</p>
                        </div>
                    </div>
                </td>
                <td><p class="text-xs" style="color:#94a3b8;">{{ $school->level ?? '—' }}</p></td>
                <td><p class="text-xs" style="color:#94a3b8;">{{ $school->city ?? '—' }}</p></td>
                <td>
                    <span class="badge {{ $school->subscription_plan === 'pro' ? 'badge-violet' : ($school->subscription_plan === 'basic' ? 'badge-indigo' : 'badge-slate') }}">
                        {{ $plans[$school->subscription_plan] ?? ucfirst($school->subscription_plan) }}
                    </span>
                </td>
                <td>
                    <p class="text-xs" style="color:#94a3b8;">
                        {{ $school->subscription_until?->translatedFormat('d M Y') ?? '—' }}
                    </p>
                </td>
                <td>
                    <span class="badge {{ $school->is_active ? 'badge-green' : 'badge-slate' }}">
                        {{ $school->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('superadmin.schools.edit', $school) }}" class="text-xs font-semibold" style="color:#818cf8;">Edit</a>
                        <form method="POST" action="{{ route('superadmin.schools.destroy', $school) }}"
                              onsubmit="return confirm('Hapus sekolah {{ $school->name }}? Tindakan ini tidak bisa dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold" style="color:#fca5a5;">Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="px-5 py-4 border-t" style="border-color: var(--sa-border);">
        {{ $schools->links() }}
    </div>
    @endif
</div>

@endsection

@push('styles')
<style>
    .form-input {
        padding: 10px 14px;
        border-radius: 10px;
        background: var(--sa-surface-2);
        border: 1px solid var(--sa-border);
        color: #e2e8f0;
        font-size: 13.5px;
    }
    .form-input:focus { outline: none; border-color: #6366f1; }
    .form-input::placeholder { color: #475569; }
    .pagination { color: #94a3b8; }
</style>
@endpush
