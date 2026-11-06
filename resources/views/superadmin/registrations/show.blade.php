@extends('superadmin.layouts.app')

@section('title', $registration->school_name . ' — Pendaftaran')
@section('page-title', 'Detail Pendaftaran')

@section('content')
@php
    // Nama class lengkap lewat variabel (hindari use di dalam blok php)
    $R = \App\Models\SchoolRegistration::class;
    $Svc = \App\Services\SchoolRegistrationService::class;

    $status = $registration->status;
    $fmt = fn ($date) => $date ? $date->translatedFormat('d M Y H:i') : null;
    $address = collect([
        $registration->address, $registration->village, $registration->district,
        $registration->city, $registration->province, $registration->postal_code,
    ])->filter()->implode(', ');
@endphp

<div class="space-y-6" x-data="{ rejecting: {{ $errors->has('reason') ? 'true' : 'false' }} }">
    <a href="{{ route('superadmin.registrations.index') }}" class="text-sm text-slate-400 hover:underline">&larr; Kembali ke daftar pendaftaran</a>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-100">{{ $registration->school_name }}</h2>
            <p class="text-sm text-slate-400">Masuk {{ $fmt($registration->created_at) }}</p>
        </div>
        @include('superadmin.registrations._status-badge', ['status' => $status, 'statuses' => $statuses])
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-white/10 bg-white/5 p-5">
            <h3 class="text-sm font-semibold text-slate-100">Data sekolah</h3>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-400">Nama sekolah</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->school_name }}</dd></div>
                <div><dt class="text-xs text-slate-400">NPSN</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->npsn ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Jenjang</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->level ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Telepon sekolah</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->school_phone ?: '-' }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-slate-400">Alamat</dt><dd class="mt-1 text-sm text-slate-100">{{ $address ?: '-' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-xl border border-white/10 bg-white/5 p-5">
            <h3 class="text-sm font-semibold text-slate-100">Data pendaftar</h3>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-400">Nama</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->applicant_name }}</dd></div>
                <div><dt class="text-xs text-slate-400">Jabatan</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->applicant_position ?: '-' }}</dd></div>
                <div><dt class="text-xs text-slate-400">Email</dt><dd class="mt-1 break-all text-sm text-slate-100">{{ $registration->applicant_email }}</dd></div>
                <div><dt class="text-xs text-slate-400">Nomor HP</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->applicant_phone ?: '-' }}</dd></div>
                <div>
                    <dt class="text-xs text-slate-400">Email diverifikasi</dt>
                    <dd class="mt-1 text-sm text-slate-100">{{ $fmt($registration->email_verified_at) ?? 'Belum' }}</dd>
                </div>
                <div><dt class="text-xs text-slate-400">Alamat IP</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->ip_address ?: '-' }}</dd></div>
            </dl>
        </section>
    </div>

    {{-- ============== Bagian keputusan, bergantung pada status ============== --}}

    @if ($status === $R::STATUS_PENDING_APPROVAL)
        <section class="rounded-xl border border-white/10 bg-white/5 p-5">
            <h3 class="text-sm font-semibold text-slate-100">Keputusan</h3>

            @if ($npsnTaken)
                <div role="alert" class="mt-4 rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    NPSN {{ $registration->npsn }} sudah dipakai sekolah lain. Menyetujui akan ditolak sistem.
                </div>
            @endif
            @if ($emailTaken)
                <div role="alert" class="mt-4 rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                    Email pendaftar sudah dipakai akun lain. Menyetujui akan ditolak sistem; tolak pendaftaran ini dan minta pendaftar memakai email berbeda.
                </div>
            @endif

            <p class="mt-4 text-sm text-slate-300">Jika disetujui, sistem akan:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-slate-400">
                <li>membuat sekolah dengan paket Trial {{ $Svc::TRIAL_DAYS }} hari dan maksimal {{ $Svc::TRIAL_MAX_USERS }} pengguna,</li>
                <li>membuat akun Tata Usaha atas nama pendaftar,</li>
                <li>mengirim email berisi tautan untuk membuat kata sandi.</li>
            </ul>

            @if ($errors->has('reason'))
                <div role="alert" class="mt-4 rounded-lg border border-red-400/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ $errors->first('reason') }}</div>
            @endif

            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('superadmin.registrations.approve', $registration) }}"
                      x-data="{ busy: false }"
                      @submit="if (! confirm('Setujui pendaftaran ini? Sekolah dan akun Tata Usaha akan dibuat, dan email dikirim ke pendaftar.')) { $event.preventDefault(); return; } busy = true">
                    @csrf
                    <button type="submit" :disabled="busy"
                            class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-medium text-white hover:bg-emerald-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="busy ? 'Memproses...' : 'Setujui'">Setujui</span>
                    </button>
                </form>

                <button type="button" @click="rejecting = ! rejecting" :aria-expanded="rejecting"
                        class="rounded-md border border-red-400/40 px-5 py-2 text-sm font-medium text-red-300 hover:bg-red-500/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300">
                    Tolak
                </button>
            </div>

            <form x-show="rejecting" x-cloak method="POST" action="{{ route('superadmin.registrations.reject', $registration) }}"
                  x-data="{ busy: false }" @submit="busy = true" class="mt-5 space-y-3">
                @csrf
                <div>
                    <label for="reason" class="block text-xs font-medium text-slate-400">Alasan penolakan (dikirim ke pendaftar lewat email)</label>
                    <textarea id="reason" name="reason" rows="3" required minlength="10" maxlength="1000"
                              class="mt-1 block w-full rounded-md border border-white/15 bg-white/5 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-red-300">{{ old('reason') }}</textarea>
                </div>
                <button type="submit" :disabled="busy"
                        class="rounded-md bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-300 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-text="busy ? 'Mengirim...' : 'Kirim penolakan'">Kirim penolakan</span>
                </button>
            </form>
        </section>

    @elseif ($status === $R::STATUS_PENDING_EMAIL)
        <section class="rounded-xl border border-white/10 bg-white/5 p-5 text-sm text-slate-300">
            Menunggu pendaftar memverifikasi emailnya.
            @if ($registration->email_verification_expires_at)
                Tautan berlaku sampai {{ $fmt($registration->email_verification_expires_at) }}; setelah itu pendaftaran ditandai kedaluwarsa otomatis.
            @endif
        </section>

    @elseif ($status === $R::STATUS_APPROVED)
        <section class="rounded-xl border border-white/10 bg-white/5 p-5">
            <h3 class="text-sm font-semibold text-slate-100">Hasil persetujuan</h3>
            <dl class="mt-4 grid gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs text-slate-400">Disetujui</dt><dd class="mt-1 text-sm text-slate-100">{{ $fmt($registration->reviewed_at) }}@if ($registration->reviewer) oleh {{ $registration->reviewer->username }}@endif</dd></div>
                <div>
                    <dt class="text-xs text-slate-400">Sekolah</dt>
                    <dd class="mt-1 text-sm text-slate-100">
                        @if ($registration->school_id)
                            <a href="{{ route('superadmin.schools.edit', $registration->school_id) }}" class="underline hover:text-white">{{ $registration->school?->name ?? 'Buka sekolah' }}</a>
                        @else
                            -
                        @endif
                    </dd>
                </div>
                <div><dt class="text-xs text-slate-400">Username Tata Usaha</dt><dd class="mt-1 text-sm text-slate-100">{{ $registration->user?->username ?? '-' }}</dd></div>
                <div>
                    <dt class="text-xs text-slate-400">Kata sandi</dt>
                    <dd class="mt-1 text-sm text-slate-100">
                        @if ($registration->password_setup_used_at)
                            Sudah dibuat pada {{ $fmt($registration->password_setup_used_at) }}
                        @elseif ($registration->password_setup_expires_at?->isPast())
                            Belum dibuat, tautan sudah kedaluwarsa ({{ $fmt($registration->password_setup_expires_at) }})
                        @else
                            Belum dibuat, tautan berlaku sampai {{ $fmt($registration->password_setup_expires_at) }}
                        @endif
                    </dd>
                </div>
            </dl>

            @unless ($registration->password_setup_used_at)
                <form method="POST" action="{{ route('superadmin.registrations.resend-password-setup', $registration) }}"
                      x-data="{ busy: false }" @submit="busy = true" class="mt-5">
                    @csrf
                    <button type="submit" :disabled="busy"
                            class="rounded-md border border-white/20 px-4 py-2 text-sm font-medium text-slate-100 hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="busy ? 'Mengirim...' : 'Kirim ulang tautan kata sandi'">Kirim ulang tautan kata sandi</span>
                    </button>
                    <p class="mt-2 text-xs text-slate-400">Tautan baru dikirim ke {{ $registration->applicant_email }}. Tautan sebelumnya tidak berlaku lagi.</p>
                </form>
            @endunless
        </section>

    @elseif ($status === $R::STATUS_REJECTED)
        <section class="rounded-xl border border-white/10 bg-white/5 p-5">
            <h3 class="text-sm font-semibold text-slate-100">Ditolak</h3>
            <p class="mt-1 text-xs text-slate-400">{{ $fmt($registration->reviewed_at) }}@if ($registration->reviewer) oleh {{ $registration->reviewer->username }}@endif</p>
            <p class="mt-3 whitespace-pre-line text-sm text-slate-300">{{ $registration->rejection_reason }}</p>
        </section>

    @else
        <section class="rounded-xl border border-white/10 bg-white/5 p-5 text-sm text-slate-300">
            Pendaftaran ini kedaluwarsa karena emailnya tidak diverifikasi tepat waktu. Pendaftar dapat mendaftar ulang dari halaman pendaftaran.
        </section>
    @endif
</div>
@endsection
