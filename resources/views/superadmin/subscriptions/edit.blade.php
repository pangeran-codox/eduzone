@extends('superadmin.layouts.app')

@section('title', 'Edit Langganan')
@section('page-title', 'Edit Langganan — ' . ($school->name ?? '—'))

@section('content')

<div class="sa-card p-6 max-w-2xl">

    @if ($errors->any())
    <div class="mb-5 p-4 rounded-xl" style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.25);">
        <p class="text-sm font-semibold mb-1" style="color:#fca5a5;">Ada input yang perlu diperbaiki:</p>
        <ul class="text-xs list-disc list-inside" style="color:#fca5a5;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="mb-5 p-4 rounded-xl" style="background: rgba(99,102,241,0.06); border: 1px solid var(--sa-border);">
        <p class="text-xs" style="color:#94a3b8;">
            Sekolah: <strong class="text-white">{{ $school->name ?? '—' }}</strong> — kalau status diisi
            <strong class="text-white">Aktif</strong>, paket & tanggal berakhir sekolah ini akan otomatis disinkronkan ulang.
        </p>
    </div>

    <form method="POST" action="{{ route('superadmin.subscriptions.update', $subscription) }}" class="space-y-5">
        @csrf
        @method('PUT')

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="field-label">Paket</label>
                <select name="plan" required class="form-input">
                    @foreach ($plans as $value => $label)
                        <option value="{{ $value }}" {{ old('plan', $subscription->plan) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Status</label>
                <select name="status" required class="form-input">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" {{ old('status', $subscription->status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label">Mulai</label>
                <input type="date" name="started_at" value="{{ old('started_at', $subscription->started_at->format('Y-m-d')) }}" required class="form-input">
            </div>
            <div>
                <label class="field-label">Berakhir</label>
                <input type="date" name="expired_at" value="{{ old('expired_at', $subscription->expired_at->format('Y-m-d')) }}" required class="form-input">
            </div>
            <div>
                <label class="field-label">Jumlah (Rp)</label>
                <input type="number" name="amount" value="{{ old('amount', $subscription->amount) }}" required min="0" step="1000" class="form-input">
            </div>
            <div>
                <label class="field-label">No. Invoice <span class="field-hint">(opsional)</span></label>
                <input type="text" name="invoice_no" value="{{ old('invoice_no', $subscription->invoice_no) }}" class="form-input">
            </div>
        </div>

        <div>
            <label class="field-label">Catatan <span class="field-hint">(opsional)</span></label>
            <textarea name="note" rows="3" class="form-input">{{ old('note', $subscription->note) }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: linear-gradient(135deg,#4f46e5,#7c3aed);">
                Simpan Perubahan
            </button>
            <a href="{{ route('superadmin.subscriptions.index') }}" class="text-sm" style="color:#64748b;">Batal</a>
        </div>
    </form>
</div>

@endsection

@push('styles')
<style>
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border-radius: 10px;
        background: var(--sa-surface-2);
        border: 1px solid var(--sa-border);
        color: #e2e8f0;
        font-size: 13.5px;
    }
    .form-input:focus { outline: none; border-color: #6366f1; }
    textarea.form-input { resize: vertical; }
    .field-label { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; }
    .field-hint { font-size: 11px; font-weight: 400; color: #475569; }
</style>
@endpush
