@extends('guest.layouts.app')

@section('title', 'Cek email Anda')
@section('centered', true)

@section('content')
    <div class="flex h-11 w-11 items-center justify-center rounded-full mb-6 bg-[#1B3A34]/10 text-[#1B3A34]">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
        </svg>
    </div>

    <h1 class="font-display text-xl font-semibold mb-2 text-[#1B3A34]">Cek email Anda</h1>

    <p class="text-sm leading-relaxed text-[#4A4A44]">
        @if ($email)
            Kami mengirim tautan verifikasi ke <span class="break-all font-medium text-[#1C1C1A]">{{ $email }}</span>.
        @else
            Kami mengirim tautan verifikasi ke email pendaftar.
        @endif
        Klik tautan itu dalam {{ $hours }} jam untuk melanjutkan pendaftaran.
    </p>

    <p class="text-sm leading-relaxed mt-3 text-[#4A4A44]">
        Setelah email terverifikasi, tim EduZone akan meninjau pendaftaran sekolah Anda dan mengabari lewat email.
    </p>

    <div class="mt-6 rounded-md px-3 py-2 text-sm bg-white border border-[#D8D4C6] text-[#6B6B63]">
        Belum menerima email? Periksa folder spam. Kalau salah mengetik alamat email, Anda bisa mendaftar ulang
        setelah tautan kedaluwarsa.
    </div>
@endsection
