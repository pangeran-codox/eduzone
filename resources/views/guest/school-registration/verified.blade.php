@extends('guest.layouts.app')

@php
    // $state dari controller: success | already | expired | invalid
    $view = match ($state) {
        'success' => [
            'icon' => 'check',
            'tone' => 'bg-[#EAF3DE] text-[#3B6D11]',
            'title' => 'Email terverifikasi',
            'body' => 'Pendaftaran sekolah Anda sekarang menunggu tinjauan tim EduZone. Kami akan mengirim email setelah ada keputusan. Jika disetujui, email itu berisi tautan untuk membuat kata sandi akun Tata Usaha.',
            'cta' => null,
        ],
        'already' => [
            'icon' => 'check',
            'tone' => 'bg-[#1B3A34]/10 text-[#1B3A34]',
            'title' => 'Email sudah terverifikasi',
            'body' => 'Pendaftaran Anda sedang ditinjau atau sudah disetujui. Tidak ada yang perlu Anda lakukan sekarang.',
            'cta' => null,
        ],
        'expired' => [
            'icon' => 'clock',
            'tone' => 'bg-[#FAEEDA] text-[#854F0B]',
            'title' => 'Tautan sudah kedaluwarsa',
            'body' => 'Tautan verifikasi hanya berlaku sementara. Daftar ulang untuk mendapatkan tautan baru.',
            'cta' => 'Daftar ulang',
        ],
        default => [
            'icon' => 'alert',
            'tone' => 'bg-[#FCEBEB] text-[#A32D2D]',
            'title' => 'Tautan tidak valid',
            'body' => 'Tautan mungkin terpotong saat disalin atau sudah tidak berlaku. Buka kembali tautan dari email Anda, atau mulai dari formulir pendaftaran.',
            'cta' => 'Buka formulir pendaftaran',
        ],
    };
@endphp

@section('title', $view['title'])
@section('centered', true)

@section('content')
    <div class="flex h-11 w-11 items-center justify-center rounded-full mb-6 {{ $view['tone'] }}">
        @switch($view['icon'])
            @case('check')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                @break
            @case('clock')
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                @break
            @default
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
        @endswitch
    </div>

    <h1 class="font-display text-xl font-semibold mb-2 text-[#1B3A34]">{{ $view['title'] }}</h1>
    <p class="text-sm leading-relaxed text-[#4A4A44]">{{ $view['body'] }}</p>

    @if ($view['cta'])
        <a href="{{ route('guest.school-registration.create') }}"
           class="mt-7 inline-flex h-[42px] items-center justify-center rounded-md px-6 text-sm font-medium transition-opacity hover:opacity-90 bg-[#1B3A34] text-[#F6F3EC] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2">
            {{ $view['cta'] }}
        </a>
    @endif
@endsection
