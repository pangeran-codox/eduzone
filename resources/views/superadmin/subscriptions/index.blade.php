@extends('superadmin.layouts.app')

@section('title', 'Langganan')
@section('page-title', 'Langganan')

@section('content')

@if (session('success'))
<div class="sa-card p-4 mb-6" style="border-color: rgba(16,185,129,0.3);">
    <p class="text-sm" style="color:#6ee7b7;">{{ session('success') }}</p>
</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Langganan</h1>
        <p class="text-sm" style="color:#64748b;">{{ $subscriptions->total() }} catatan langganan</p>
    </div>
    <a href="{{ route('superadmin.subscriptions.create') }}"
       class="px-4 py-2 rounded-xl text-sm font-semibold text-white"
       style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
        + Catat Langganan
    </a>
</div>

{{-- ── Search & Filter ─────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('superadmin.subscriptions.index') }}" class="sa-card p-4 mb-6 flex flex-col sm:flex-row gap-3">
    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor invoice..." class="form-input flex-1">
    <select name="school" class="form-input sm:w-56">
        <option value="">Semua Sekolah</option>
        @foreach ($schools as $id => $name)
            <option value="{{ $id }}" {{ $schoolFilter === $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <select name="status" class="form-input sm:w-44">
        <option value="">Semua Status</option>
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" {{ $statusFilter === $value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: rgba(99,102,241,0.2);">
        Cari
    </button>
    @if ($search || $schoolFilter || $statusFilter)
    <a href="{{ route('superadmin.subscriptions.index') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color:#64748b;">
        Reset
    </a>
    @endif
</form>

<div class="sa-card overflow-hidden">
    @if ($subscriptions->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Belum ada riwayat langganan.</p>
    </div>
    @else
    <table class="sa-table">
        <thead>
            <tr>
                <th>Sekolah</th>
                <th>Paket</th>
                <th>Periode</th>
                <th>Jumlah</th>
                <th>Invoice</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($subscriptions as $sub)
            <tr>
                <td><p class="text-xs font-semibold text-white">{{ $sub->school_name }}</p></td>
                <td>
                    <span class="badge {{ $sub->plan === 'pro' ? 'badge-violet' : ($sub->plan === 'basic' ? 'badge-indigo' : 'badge-slate') }}">
                        {{ $plans[$sub->plan] ?? ucfirst($sub->plan) }}
                    </span>
                </td>
                <td>
                    <p class="text-xs" style="color:#94a3b8;">
                        {{ $sub->started_at->translatedFormat('d M Y') }} — {{ $sub->expired_at->translatedFormat('d M Y') }}
                    </p>
                </td>
                <td><p class="text-xs" style="color:#94a3b8;">Rp{{ number_format($sub->amount, 0, ',', '.') }}</p></td>
                <td><p class="text-xs" style="color:#64748b;">{{ $sub->invoice_no ?? '—' }}</p></td>
                <td>
                    <span class="badge {{ $sub->status === 'active' ? 'badge-green' : ($sub->status === 'cancelled' ? 'badge-red' : 'badge-amber') }}">
                        {{ $statuses[$sub->status] ?? ucfirst($sub->status) }}
                    </span>
                </td>
                <td>
                    <div class="flex items-center gap-2 justify-end">
                        <a href="{{ route('superadmin.subscriptions.edit', $sub) }}" class="text-xs font-semibold" style="color:#818cf8;">Edit</a>
                        <form method="POST" action="{{ route('superadmin.subscriptions.destroy', $sub) }}"
                              onsubmit="return confirm('Hapus riwayat langganan ini?');">
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
        {{ $subscriptions->links() }}
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
