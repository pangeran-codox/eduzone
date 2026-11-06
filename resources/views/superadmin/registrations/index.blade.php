@extends('superadmin.layouts.app')

@section('title', 'Pendaftaran Sekolah')
@section('page-title', 'Pendaftaran Sekolah')

@section('content')
<div class="space-y-5">
    @if (session('success'))
        <div role="status" class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ session('error') }}</div>
    @endif

    @if ($pendingCount > 0 && $statusFilter !== 'pending_approval')
        <a href="{{ route('superadmin.registrations.index', ['status' => 'pending_approval']) }}"
           class="block rounded-lg border border-amber-400/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200 hover:bg-amber-500/15">
            {{ $pendingCount }} pendaftaran menunggu persetujuan. Lihat antrean &rarr;
        </a>
    @endif

    <form method="GET" action="{{ route('superadmin.registrations.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-60">
            <label for="status" class="block text-xs font-medium text-slate-400">Status</label>
            <select id="status" name="status"
                    class="mt-1 block w-full rounded-md border border-white/15 bg-slate-900 px-3 py-2 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400">
                <option value="all" @selected($statusFilter === 'all')>Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label for="search" class="block text-xs font-medium text-slate-400">Cari</label>
            <input id="search" type="search" name="search" value="{{ $search }}" placeholder="Nama sekolah, NPSN, atau email pendaftar"
                   class="mt-1 block w-full rounded-md border border-white/15 bg-white/5 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-slate-400">
        </div>
        <button type="submit"
                class="rounded-md bg-white/10 px-4 py-2 text-sm font-medium text-slate-100 hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
            Terapkan
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-white/10 bg-white/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-white/10 text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-400">
                        <th class="px-4 py-3 font-medium">Masuk</th>
                        <th class="px-4 py-3 font-medium">Sekolah</th>
                        <th class="px-4 py-3 font-medium">Pendaftar</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-200">
                    @forelse ($registrations as $r)
                        <tr class="hover:bg-white/5">
                            <td class="whitespace-nowrap px-4 py-3 text-slate-400">{{ $r->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-100">{{ $r->school_name }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $r->level }}@if ($r->npsn) · NPSN {{ $r->npsn }}@endif
                                    @if ($r->city) · {{ $r->city }}@endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ $r->applicant_name }}</div>
                                <div class="text-xs text-slate-400">{{ $r->applicant_email }}</div>
                            </td>
                            <td class="px-4 py-3">@include('superadmin.registrations._status-badge', ['status' => $r->status, 'statuses' => $statuses])</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <a href="{{ route('superadmin.registrations.show', $r) }}"
                                   class="text-sm font-medium text-slate-100 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400">
                                    {{ $r->status === 'pending_approval' ? 'Tinjau' : 'Lihat' }} &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-400">
                                @if ($search !== '')
                                    Tidak ada pendaftaran yang cocok dengan pencarian ini.
                                @elseif ($statusFilter === 'pending_approval')
                                    Tidak ada pendaftaran yang menunggu persetujuan.
                                @elseif ($statusFilter !== 'all')
                                    Tidak ada pendaftaran dengan status ini.
                                @else
                                    Belum ada pendaftaran.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $registrations->links() }}</div>
</div>
@endsection
