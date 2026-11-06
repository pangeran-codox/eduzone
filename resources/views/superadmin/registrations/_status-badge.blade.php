{{-- Variabel: $status (string), $statuses (peta status => label dari controller) --}}
@php
    $styles = [
        'pending_approval' => 'bg-amber-500/15 text-amber-300 ring-amber-400/30',
        'pending_email' => 'bg-slate-500/20 text-slate-300 ring-slate-400/30',
        'approved' => 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30',
        'rejected' => 'bg-red-500/15 text-red-300 ring-red-400/30',
        'expired' => 'bg-slate-500/15 text-slate-400 ring-slate-500/30',
    ];
@endphp
<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $styles[$status] ?? $styles['expired'] }}">
    {{ $statuses[$status] ?? $status }}
</span>
