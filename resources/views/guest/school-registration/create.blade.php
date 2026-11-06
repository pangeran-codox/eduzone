@extends('guest.layouts.app')

@section('title', 'Daftarkan sekolah')

{{-- Panel kiri (desktop): urutan langkah setelah mendaftar --}}
@section('panel')
    <div class="text-sm font-medium mb-5 text-[#C9A227]">Setelah Anda mendaftar</div>
    @include('guest.school-registration._steps', ['tone' => 'dark'])
@endsection

@section('content')
    <h1 class="font-display text-xl font-semibold mb-1 text-[#1B3A34]">Daftarkan sekolah Anda</h1>
    <p class="text-sm mb-7 text-[#6B6B63]">
        Pendaftar akan menjadi akun Tata Usaha, yang mengelola data induk siswa, guru, dan staff.
    </p>

    {{-- Urutan langkah di mobile (panel kiri disembunyikan di layar kecil) --}}
    <details class="lg:hidden mb-7 rounded-md border border-[#D8D4C6] bg-white px-4 py-3 text-sm">
        <summary class="cursor-pointer font-medium text-[#1B3A34]">Bagaimana alurnya setelah mendaftar?</summary>
        <div class="mt-5 pl-1">
            @include('guest.school-registration._steps', ['tone' => 'light'])
        </div>
    </details>

    @if ($errors->any())
        <div role="alert" class="mb-5 text-sm rounded-md px-3 py-2 bg-[#FCEBEB] text-[#A32D2D]">
            @if ($errors->has('general'))
                {{ $errors->first('general') }}
            @else
                Ada {{ $errors->count() }} isian yang perlu diperbaiki. Cek kolom yang bertanda merah.
            @endif
        </div>
    @endif

    <form method="POST"
          action="{{ route('guest.school-registration.store') }}"
          class="flex flex-col gap-9"
          x-data="{ sending: false }"
          @submit="sending = true">
        @csrf

        {{-- Honeypot anti-bot: tidak terlihat oleh manusia, bot biasanya mengisinya --}}
        <div class="absolute -left-[9999px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
            <label for="hp_field">Kosongkan kolom ini</label>
            <input type="text" id="hp_field" name="hp_field" value="" tabindex="-1" autocomplete="off">
        </div>

        <fieldset>
            <legend class="text-sm font-medium mb-4 text-[#1B3A34]">Data sekolah</legend>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <x-guest.field class="sm:col-span-2" name="school_name" label="Nama sekolah" required
                               autocomplete="organization" placeholder="Nama resmi sekolah" />

                <x-guest.field name="npsn" label="NPSN" required inputmode="numeric" maxlength="8"
                               autocomplete="off" hint="8 digit angka." />

                <div>
                    <label for="field-level" class="block text-xs font-medium mb-1.5 text-[#4A4A44]">Jenjang</label>
                    <select id="field-level" name="level" required
                            @if ($errors->has('level')) aria-invalid="true" aria-describedby="field-level-error" @endif
                            class="block w-full h-10 rounded-md border bg-white px-3 text-sm text-[#1C1C1A] focus:outline-none focus:ring-2 {{ $errors->has('level') ? 'border-[#A32D2D] focus:ring-[#A32D2D]/40' : 'border-[#D8D4C6] focus:ring-[#1B3A34]' }}">
                        <option value="" disabled @selected(old('level') === null)>Pilih jenjang</option>
                        @foreach ($levels as $level)
                            <option value="{{ $level }}" @selected(old('level') === $level)>{{ $level }}</option>
                        @endforeach
                    </select>
                    @error('level')
                        <p id="field-level-error" class="text-xs mt-1 text-[#A32D2D]">{{ $message }}</p>
                    @enderror
                </div>

                <x-guest.field class="sm:col-span-2" name="address" label="Alamat" as="textarea" required
                               autocomplete="street-address" />

                <x-guest.field name="village" label="Kelurahan/desa" />
                <x-guest.field name="district" label="Kecamatan" />
                <x-guest.field name="city" label="Kota/kabupaten" required />
                <x-guest.field name="province" label="Provinsi" required />
                <x-guest.field name="postal_code" label="Kode pos" inputmode="numeric" maxlength="10"
                               autocomplete="postal-code" />
                <x-guest.field name="school_phone" label="Telepon sekolah" type="tel" autocomplete="tel" />
            </div>
        </fieldset>

        <fieldset>
            <legend class="text-sm font-medium mb-1 text-[#1B3A34]">Data pendaftar</legend>
            <p class="text-xs mb-4 text-[#6B6B63]">Akun Tata Usaha akan dibuat atas nama pendaftar.</p>

            <div class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2">
                <x-guest.field name="applicant_name" label="Nama lengkap" required autocomplete="name" />
                <x-guest.field name="applicant_position" label="Jabatan di sekolah"
                               placeholder="Contoh: Staf Tata Usaha" />
                <x-guest.field name="applicant_email" label="Email" type="email" required autocomplete="email"
                               hint="Tautan verifikasi dan tautan membuat kata sandi dikirim ke email ini." />
                <x-guest.field name="applicant_phone" label="Nomor HP" type="tel" autocomplete="tel" />
            </div>
        </fieldset>

        <div>
            <button type="submit"
                    :disabled="sending"
                    class="w-full sm:w-auto sm:px-10 h-[42px] rounded-md text-sm font-medium transition-opacity hover:opacity-90 bg-[#1B3A34] text-[#F6F3EC] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1B3A34] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60">
                <span x-text="sending ? 'Mengirim...' : 'Daftarkan sekolah'">Daftarkan sekolah</span>
            </button>
            <p class="text-xs mt-3 text-[#9A9A8E]">Dengan mendaftar, Anda menyatakan data di atas benar.</p>
        </div>
    </form>
@endsection
