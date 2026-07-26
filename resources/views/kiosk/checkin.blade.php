<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Absensi — {{ $school->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/areas/kiosk.js'])
</head>
<body class="h-full bg-[#0B1220] text-[#EAF0F6] font-sans overflow-hidden select-none">

    <div
        id="kiosk-root"
        class="h-full flex flex-col"
        data-device-code="{{ $device->device_code }}"
    >
        {{-- Header: identitas sekolah & device --}}
        <header class="flex items-center justify-between px-10 py-6 border-b border-white/10">
            <div>
                <p class="text-sm uppercase tracking-[0.2em] text-[#7DD3C0]">{{ $school->name }}</p>
                <h1 class="text-2xl font-semibold mt-1">{{ $device->name }}</h1>
                @if($device->location)
                    <p class="text-sm text-white/50 mt-0.5">{{ $device->location }}</p>
                @endif
            </div>
            <div class="text-right">
                <div id="kiosk-clock" class="text-4xl font-mono font-semibold tabular-nums">--:--:--</div>
                <div id="kiosk-date" class="text-sm text-white/50 mt-1">-</div>
            </div>
        </header>

        {{-- Toggle Masuk / Pulang - event_type wajib dikirim client ke gateway --}}
        <div class="flex justify-center pt-6">
            <div class="inline-flex bg-white/5 rounded-full p-1 border border-white/10">
                <button type="button" data-event-type="check_in" class="event-toggle event-toggle-active">Masuk</button>
                <button type="button" data-event-type="check_out" class="event-toggle">Pulang</button>
            </div>
        </div>

        {{-- Area status: idle / berhasil / gagal --}}
        <div class="flex-1 flex items-center justify-center px-10">
            <div id="kiosk-status" class="w-full max-w-3xl text-center transition-all duration-300">
                <div id="status-idle" class="flex flex-col items-center gap-4">
                    <div class="w-24 h-24 rounded-full border-4 border-[#7DD3C0]/30 flex items-center justify-center">
                        <div class="w-4 h-4 rounded-full bg-[#7DD3C0] animate-pulse"></div>
                    </div>
                    <p class="text-xl text-white/70">Silakan tap kartu atau scan QR</p>
                </div>

                <div id="status-success" class="hidden flex-col items-center gap-3">
                    <div id="status-success-icon" class="w-24 h-24 rounded-full bg-[#2FBF71] flex items-center justify-center text-5xl">✓</div>
                    <p id="status-success-name" class="text-3xl font-semibold"></p>
                    <p id="status-success-detail" class="text-xl text-[#2FBF71]"></p>
                </div>

                <div id="status-failed" class="hidden flex-col items-center gap-3">
                    <div class="w-24 h-24 rounded-full bg-[#E5484D] flex items-center justify-center text-5xl">✕</div>
                    <p id="status-failed-message" class="text-2xl font-semibold text-[#E5484D]"></p>
                </div>
            </div>
        </div>

        {{-- Input methods --}}
        <div class="border-t border-white/10 px-10 py-6">
            <div class="flex gap-2 mb-4" role="tablist">
                <button type="button" data-tab="rfid" class="kiosk-tab kiosk-tab-active">RFID</button>
                <button type="button" data-tab="qr" class="kiosk-tab">QR Scan</button>
                <button type="button" data-tab="manual" class="kiosk-tab">Manual</button>
                <button type="button" data-tab="face" class="kiosk-tab">Wajah</button>
            </div>

            <div id="panel-rfid" class="kiosk-panel">
                <input
                    id="rfid-input"
                    type="text"
                    autocomplete="off"
                    autofocus
                    placeholder="Tap kartu RFID di sini..."
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-6 py-4 text-2xl tracking-widest text-center outline-none focus:border-[#7DD3C0]"
                >
            </div>

            <div id="panel-qr" class="kiosk-panel hidden">
                <div class="flex flex-col items-center gap-3">
                    <video id="qr-video" class="rounded-xl w-full max-w-md aspect-video bg-black/40" muted playsinline></video>
                    <p id="qr-status" class="text-sm text-white/50">Mengaktifkan kamera...</p>
                </div>
            </div>

            {{--
                Manual: gateway Go saat ini cuma terima method rfid/qr/face
                (lihat validMethods di checkin_device.go) - "manual" belum
                ada di kontrak API-nya. Sengaja dibuat placeholder dulu,
                SAMA seperti tab Wajah, sampai ada keputusan/perubahan di
                sisi gateway. Jangan aktifkan submit di kiosk.js untuk tab
                ini sebelum itu.
            --}}
            <div id="panel-manual" class="kiosk-panel hidden">
                <div class="text-center py-6 text-white/50">
                    <p class="text-lg">Metode manual belum didukung gateway absensi.</p>
                    <p class="text-sm mt-1">Gunakan RFID atau QR untuk saat ini.</p>
                </div>
            </div>

            <div id="panel-face" class="kiosk-panel hidden">
                <div class="text-center py-6 text-white/50">
                    <p class="text-lg">Modul face recognition belum terhubung.</p>
                    <p class="text-sm mt-1">Worker Python belum diintegrasikan ke gateway ini — gunakan RFID/QR dulu.</p>
                </div>
            </div>
        </div>

        {{-- Setup device key (muncul sekali kalau localStorage kosong) --}}
        <div id="device-key-modal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center px-10">
            <div class="bg-[#0B1220] border border-white/10 rounded-2xl p-8 max-w-md w-full">
                <h2 class="text-xl font-semibold mb-2">Setup Device</h2>
                <p class="text-white/60 text-sm mb-4">Masukkan device key untuk <strong>{{ $device->device_code }}</strong> (sekali saja, disimpan lokal di browser ini).</p>
                <input id="device-key-input" type="password" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 mb-4 outline-none focus:border-[#7DD3C0]" placeholder="Device key">
                <button id="device-key-save" class="w-full bg-[#7DD3C0] text-[#0B1220] font-semibold rounded-xl py-3">Simpan</button>
            </div>
        </div>
    </div>

    <style>
        .kiosk-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.05);
        }
        .kiosk-tab-active {
            background: #7DD3C0;
            color: #0B1220;
            font-weight: 600;
        }
        .event-toggle {
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.9rem;
            font-weight: 600;
            color: rgba(255,255,255,0.5);
        }
        .event-toggle-active {
            background: #7DD3C0;
            color: #0B1220;
        }
    </style>
</body>
</html>
