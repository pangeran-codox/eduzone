@extends('guest.layouts.app')

@php
    // $state dari controller: form | used | expired | invalid
    $info = match ($state) {
        'used' => [
            'icon' => 'check',
            'tone' => 'bg-[#EAF3DE] text-[#3B6D11]',
            'title' => 'Kata sandi sudah dibuat',
            'body' => 'Akun Anda sudah siap dipakai. Silakan masuk dengan username dan kata sandi Anda.',
            'cta' => 'Masuk',
            'href' => url('/login'),
        ],
        'expired' => [
            'icon' => 'clock',
            'tone' => 'bg-[#FAEEDA] text-[#854F0B]',
            'title' => 'Tautan sudah kedaluwarsa',
            'body' => 'Tautan membuat kata sandi hanya berlaku sementara. Hubungi tim EduZone untuk meminta tautan baru.',
            'cta' => null,
            'href' => null,
        ],
        'invalid' => [
            'icon' => 'alert',
            'tone' => 'bg-[#FCEBEB] text-[#A32D2D]',
            'title' => 'Tautan tidak valid',
            'body' => 'Tautan mungkin terpotong saat disalin, atau sudah diganti dengan tautan yang lebih baru. Gunakan tautan terbaru dari email Anda.',
            'cta' => null,
            'href' => null,
        ],
        default => null,
    };
@endphp

@section('title', $info['title'] ?? 'Buat kata sandi')
@section('centered', true)

@section('content')
    @if ($state === 'form')
        <h1 class="font-display text-xl font-semibold mb-1 text-[#1B3A34]">Buat kata sandi</h1>
        <p class="text-sm mb-7 text-[#6B6B63]">
            Kata sandi ini dipakai untuk masuk ke akun Tata Usaha sekolah Anda.
        </p>

        @if ($errors->any())
            <div role="alert" class="mb-5 text-sm rounded-md px-3 py-2 bg-[#FCEBEB] text-[#A32D2D]">
                Kata sandi belum bisa disimpan. Periksa kolom yang bertanda merah.
            </div>
        @endif

        <form method="POST"
              action="{{ route('guest.password-setup.store', ['registration' => $registration, 'token' => $token]) }}"
              class="flex flex-col gap-4"
              x-data="{ sending: false }"
              @submit="sending = true">
            @csrf

            <x-guest.field name="password" label="Kata sandi baru" type="password" required
                           autocomplete="new-password" hint="Minimal 8 karakter." />
            <x-guest.field name="password_confirmation" label="Ulangi kata sandi" type="password" required
                           autocomplete="new-password" />

            <button type="submit"
                    :disabled="sending"
                    class="w-full h-[42px] rounded-md text-sm font-medium mt-1.5 transition-opacity hover:opacity-90 bg-[#1B3A34] text-[#F6F3EC] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-text="sending ? 'Menyimpan...' : 'Simpan kata sandi'">Simpan kata sandi</span>
            </button>
        </form>
    @else
        <div class="flex h-11 w-11 items-center justify-center rounded-full mb-6 {{ $info['tone'] }}">
            @switch($info['icon'])
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

        <h1 class="font-display text-xl font-semibold mb-2 text-[#1B3A34]">{{ $info['title'] }}</h1>
        <p class="text-sm leading-relaxed text-[#4A4A44]">{{ $info['body'] }}</p>

        @if ($info['cta'])
            <a href="{{ $info['href'] }}"
               class="mt-7 inline-flex h-[42px] items-center justify-center rounded-md px-6 text-sm font-medium transition-opacity hover:opacity-90 bg-[#1B3A34] text-[#F6F3EC] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2">
                {{ $info['cta'] }}
            </a>
        @endif
    @endif
@endsection
