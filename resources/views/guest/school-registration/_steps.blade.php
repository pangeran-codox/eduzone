{{--
    Urutan langkah setelah mendaftar. Ini memang urutan nyata, jadi bernomor.
    Variabel: $tone = 'dark' (di panel hijau) | 'light' (di atas latar krem/putih).
--}}
@php
    $dark = ($tone ?? 'dark') === 'dark';

    $lineCls = $dark ? 'border-[#F6F3EC]/20' : 'border-[#D8D4C6]';
    $numCls = $dark ? 'bg-[#C9A227] text-[#1B3A34]' : 'bg-[#1B3A34] text-[#F6F3EC]';
    $titleCls = $dark ? 'text-[#F6F3EC]' : 'text-[#1C1C1A]';
    $bodyCls = $dark ? 'text-[#9FB8AE]' : 'text-[#6B6B63]';

    $steps = [
        ['Verifikasi email', 'Kami mengirim tautan ke email pendaftar. Klik tautan itu untuk melanjutkan.'],
        ['Tinjauan tim EduZone', 'Kami memeriksa data sekolah Anda. Hasilnya dikirim lewat email.'],
        ['Akun Tata Usaha aktif', 'Jika disetujui, email berisi tautan untuk membuat kata sandi. Setelah itu Anda bisa masuk.'],
    ];
@endphp

<ol class="space-y-6 border-l pl-6 {{ $lineCls }}">
    @foreach ($steps as $i => $step)
        <li class="relative">
            <span class="absolute -left-[2.14rem] flex h-5 w-5 items-center justify-center rounded-full text-xs font-medium {{ $numCls }}">{{ $i + 1 }}</span>
            <p class="text-sm font-medium {{ $titleCls }}">{{ $step[0] }}</p>
            <p class="mt-1 text-sm leading-relaxed {{ $bodyCls }}">{{ $step[1] }}</p>
        </li>
    @endforeach
</ol>
