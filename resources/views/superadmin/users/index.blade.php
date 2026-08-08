@extends('superadmin.layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Pengguna</h1>
        <p class="text-sm" style="color:#64748b;">{{ $users->total() }} akun terdaftar di semua sekolah</p>
    </div>
</div>

{{-- ── Search & Filter ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('superadmin.users.index') }}" class="sa-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}"
           placeholder="Cari username atau email..."
           class="form-input flex-1">
    <select name="school" class="form-input sm:w-56">
        <option value="">Semua Sekolah</option>
        @foreach ($schools as $id => $name)
            <option value="{{ $id }}" {{ $schoolFilter === $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <select name="role" class="form-input sm:w-48">
        <option value="">Semua Role</option>
        @foreach ($roleLabels as $value => $label)
            <option value="{{ $value }}" {{ $roleFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: rgba(99,102,241,0.2);">
        Cari
    </button>
    @if ($search || $schoolFilter || $roleFilter)
    <a href="{{ route('superadmin.users.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color:#64748b;">
        Reset
    </a>
    @endif
</form>

{{-- ── Tabel ────────────────────────────────────────────────────────── --}}
<div class="sa-card overflow-hidden">
    @if ($users->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">
            {{ $search || $schoolFilter || $roleFilter ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada pengguna terdaftar.' }}
        </p>
    </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email / Username</th>
                <th>Sekolah</th>
                <th>Role</th>
                <th>Status</th>
                <th>Login Terakhir</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
            <tr>
                <td>
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                             style="background:linear-gradient(135deg,#4f46e5,#7c3aed);">
                            {{ strtoupper(substr($user->display_name, 0, 1)) }}
                        </div>
                        <p class="text-xs font-semibold text-white leading-tight">{{ $user->display_name }}</p>
                    </div>
                </td>
                <td>
                    <p class="text-xs" style="color:#94a3b8;">{{ $user->email ?? '—' }}</p>
                    <p class="text-xs" style="color:#475569;">{{ $user->username ?? '—' }}</p>
                </td>
                <td>
                    <p class="text-xs" style="color:#94a3b8;">{{ $user->school_name ?? '— (Superadmin)' }}</p>
                </td>
                <td>
                    <span class="badge badge-indigo">{{ $roleLabels[$user->role] ?? $user->role ?? '—' }}</span>
                </td>
                <td>
                    <span class="badge {{ $user->is_active ? 'badge-green' : 'badge-slate' }}">
                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </td>
                <td>
                    <p class="text-xs" style="color:#64748b;">
                        {{ $user->last_login_at?->diffForHumans() ?? 'Belum pernah' }}
                    </p>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="px-5 py-4 border-t" style="border-color: var(--sa-border);">
        {{ $users->links() }}
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
</style>
@endpush
