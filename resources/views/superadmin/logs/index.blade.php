@extends('superadmin.layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-white mb-1">Audit Log</h1>
        <p class="text-sm" style="color:#64748b;">{{ $logs->total() }} aktivitas tercatat</p>
    </div>
</div>

{{-- ── Filter ───────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('superadmin.logs') }}" class="sa-card p-4 mb-6 flex flex-col sm:flex-row flex-wrap gap-3">
    <input type="text" name="activity" value="{{ $activityFilter }}" placeholder="Cari jenis aktivitas..." class="form-input flex-1 min-w-[180px]">
    <select name="school" class="form-input sm:w-56">
        <option value="">Semua Sekolah</option>
        @foreach ($schools as $id => $name)
            <option value="{{ $id }}" {{ $schoolFilter === $id ? 'selected' : '' }}>{{ $name }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ $from }}" class="form-input" title="Dari tanggal">
    <input type="date" name="to" value="{{ $to }}" class="form-input" title="Sampai tanggal">
    <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: rgba(99,102,241,0.2);">
        Cari
    </button>
    @if ($schoolFilter || $activityFilter || $from || $to)
    <a href="{{ route('superadmin.logs') }}" class="px-5 py-2.5 rounded-xl text-sm" style="color:#64748b;">
        Reset
    </a>
    @endif
</form>

<div class="sa-card overflow-hidden">
    @if ($logs->isEmpty())
    <div class="px-5 py-10 text-center">
        <p class="text-sm" style="color:#475569;">Tidak ada aktivitas yang cocok.</p>
    </div>
    @else
    <div class="divide-y" style="border-color: rgba(255,255,255,0.03);">
        @foreach ($logs as $log)
        <div class="px-5 py-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="badge badge-indigo">{{ $log->activity }}</span>
                        @if ($log->school_name)
                            <span class="badge badge-slate">{{ $log->school_name }}</span>
                        @endif
                        <span class="text-xs" style="color:#475569;">{{ $log->created_at->translatedFormat('d M Y, H:i:s') }}</span>
                    </div>

                    <p class="text-xs mb-1" style="color:#94a3b8;">
                        <span class="font-semibold text-white">{{ $log->user?->username ?? $log->user?->email ?? 'Sistem' }}</span>
                        @if ($log->ip_address)
                            <span style="color:#475569;"> dari {{ $log->ip_address }}</span>
                        @endif
                    </p>

                    @if ($log->description_is_json)
                        <details class="mt-2">
                            <summary class="text-xs cursor-pointer" style="color:#818cf8;">Lihat detail perubahan</summary>
                            <pre class="mt-2 p-3 rounded-lg text-xs overflow-x-auto" style="background: var(--sa-surface-2); border: 1px solid var(--sa-border); color: #94a3b8;">{{ $log->description_pretty }}</pre>
                        </details>
                    @elseif ($log->description)
                        <p class="text-xs mt-1" style="color:#64748b;">{{ $log->description }}</p>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="px-5 py-4 border-t" style="border-color: var(--sa-border);">
        {{ $logs->links() }}
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
    details summary::-webkit-details-marker { color: #818cf8; }
</style>
@endpush
