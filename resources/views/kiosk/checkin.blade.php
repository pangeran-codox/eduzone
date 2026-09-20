@php
    // $device & $school dikirim CheckInController::show(). data_get() dipakai supaya
    // kolom yang belum ada di tabel tidak bikin error, cukup jatuh ke fallback.
    $schoolName     = data_get($school, 'name') ?: 'EduZone';
    $deviceName     = data_get($device, 'name');
    $deviceTitle    = $deviceName ? "{$deviceName} ({$device->device_code})" : $device->device_code;
    $deviceLocation = data_get($device, 'location');
@endphp
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Absensi — {{ $schoolName }}</title>
    @vite('resources/js/areas/kiosk.js')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,100..900;1,9..144,100..900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .voice { font-family: 'Fraunces', Georgia, serif; }
        .kiosk-tab {
            padding: 0.6rem 1.1rem;
            white-space: nowrap;
            border-radius: 12px;
            font-size: 0.875rem;
            color: rgba(252, 249, 239, 0.6);
            background: rgba(255, 255, 255, 0.05);
            transition: all 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }
        .kiosk-tab-active {
            background: #C9A227;
            color: #1B3A34;
            font-weight: 700;
            border-color: #C9A227;
            box-shadow: 0 4px 12px rgba(201, 162, 39, 0.2);
        }
        .kiosk-soon {
            margin-left: 0.4rem;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.7;
        }
        .event-toggle {
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            color: rgba(252, 249, 239, 0.5);
            transition: all 0.3s;
            cursor: pointer;
        }
        .event-toggle-active {
            background: #F6F3EC;
            color: #1B3A34;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .dot-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: rgba(252, 249, 239, 0.1);
        }
        .dot-active {
            background: #C9A227;
            box-shadow: 0 0 8px #C9A227;
        }
        .keypad-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            font-size: 1.25rem;
            font-weight: 600;
            padding: 0.85rem 0;
            transition: all 0.15s;
        }
        .keypad-btn:active {
            background: #C9A227;
            color: #1B3A34;
            transform: scale(0.96);
        }
        .scan-frame {
            position: relative;
            width: 220px;
            height: 220px;
        }
        .scan-corner {
            position: absolute;
            width: 32px;
            height: 32px;
            border: 3px solid #C9A227;
        }
        .scan-corner.tl { top: 0; left: 0; border-right: none; border-bottom: none; border-radius: 8px 0 0 0; }
        .scan-corner.tr { top: 0; right: 0; border-left: none; border-bottom: none; border-radius: 0 8px 0 0; }
        .scan-corner.bl { bottom: 0; left: 0; border-right: none; border-top: none; border-radius: 0 0 0 8px; }
        .scan-corner.br { bottom: 0; right: 0; border-left: none; border-top: none; border-radius: 0 0 8px 0; }
        .scan-line {
            position: absolute;
            left: 8px;
            right: 8px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #C9A227, transparent);
            box-shadow: 0 0 8px #C9A227;
            animation: scan-move 2.2s ease-in-out infinite;
        }
        @keyframes scan-move {
            0%, 100% { top: 10%; }
            50% { top: 88%; }
        }
        .face-oval {
            width: 180px;
            height: 220px;
            border: 3px dashed rgba(201, 162, 39, 0.5);
            border-radius: 50% / 45%;
        }
    </style>
</head>
<body class="h-full bg-[#1B3A34] text-[#F6F3EC] overflow-hidden select-none">

    <div id="kiosk-root" class="h-full flex flex-col relative" data-device-code="{{ $device->device_code }}">
        <!-- Background Decorative Pattern (Roll-call dots) -->
        <div class="absolute top-10 right-10 opacity-20 pointer-events-none">
            <div class="dot-grid">
                <div class="dot dot-active"></div><div class="dot dot-active"></div><div class="dot dot-active"></div><div class="dot dot-active"></div><div class="dot dot-active"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
                <div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div><div class="dot"></div>
            </div>
        </div>

        {{-- Header: Identitas sekolah & device --}}
        <header class="flex items-center justify-between px-12 py-8 border-b border-white/10">
            <div class="flex items-center gap-6">
                <div class="w-16 h-16 rounded-2xl bg-[#F6F3EC] flex items-center justify-center">
                    <svg class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="#1B3A34" stroke-width="2">
                        <path d="M22 10v6M2 10v6M6 4h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM6 10h12M6 14h12" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#C9A227] font-bold">{{ $schoolName }}</p>
                    <h1 class="voice text-3xl font-semibold mt-1">{{ $deviceTitle }}</h1>
                    <p class="text-sm text-white/50 mt-0.5">{{ $deviceLocation ? $deviceLocation.' • ' : '' }}Terminal RFID/QR</p>
                </div>
            </div>
            <div class="text-right">
                <div id="kiosk-clock" class="text-5xl font-mono font-bold tabular-nums tracking-tight">--:--:--</div>
                <div id="kiosk-date" class="text-base text-[#C9A227] mt-1 font-medium">&nbsp;</div>
            </div>
        </header>

        {{-- Main Interaction Area --}}
        <main class="flex-1 flex flex-col items-center justify-center px-12 py-6">

            {{-- Toggle Masuk / Pulang --}}
            <div class="mb-12">
                <div class="inline-flex bg-black/20 rounded-2xl p-1.5 border border-white/10" id="event-toggle-group">
                    <button type="button" data-event-type="check_in" class="event-toggle event-toggle-active">Masuk Sekolah</button>
                    <button type="button" data-event-type="check_out" class="event-toggle">Pulang Sekolah</button>
                </div>
            </div>

            {{-- Area Status: Idle / Berhasil / Gagal --}}
            <div id="kiosk-status" class="w-full max-w-2xl text-center">
                <div id="status-idle" class="flex flex-col items-center gap-8">
                    <div class="relative">
                        <div class="w-32 h-32 rounded-full border-4 border-[#C9A227]/20 flex items-center justify-center">
                            <div class="w-6 h-6 rounded-full bg-[#C9A227] animate-ping absolute opacity-75"></div>
                            <div class="w-5 h-5 rounded-full bg-[#C9A227] shadow-[0_0_20px_rgba(201,162,39,0.5)]"></div>
                        </div>
                    </div>
                    <div>
                        <p id="status-idle-title" class="voice text-3xl font-medium mb-3">Siap Rekam Presensi</p>
                        <p id="status-idle-subtitle" class="text-xl text-white/50">Silakan tempelkan kartu RFID atau pindai kode QR Anda</p>
                    </div>
                </div>

                {{-- Hasil check-in: diisi kiosk.js dari respons gateway --}}
                <div id="status-success" class="hidden flex-col items-center gap-4">
                    <div id="status-success-icon" class="w-28 h-28 rounded-full bg-[#2FBF71] flex items-center justify-center text-5xl text-white shadow-[0_0_30px_rgba(47,191,113,0.3)]">✓</div>
                    <p id="status-success-name" class="text-4xl font-bold"></p>
                    <p id="status-success-detail" class="text-2xl text-[#2FBF71] font-medium"></p>
                </div>

                <div id="status-failed" class="hidden flex-col items-center gap-4">
                    <div class="w-28 h-28 rounded-full bg-[#E5484D] flex items-center justify-center text-5xl text-white shadow-[0_0_30px_rgba(229,72,77,0.3)]">✕</div>
                    <p id="status-failed-message" class="text-3xl font-semibold"></p>
                </div>
            </div>
        </main>

        {{-- Footer: Input Methods & Secondary Info --}}
        <footer class="bg-black/10 backdrop-blur-md border-t border-white/10 px-12 py-10">
            <div class="flex items-end justify-between max-w-6xl mx-auto w-full">
                <div class="flex-1 max-w-2xl">
                    <div class="flex gap-3 mb-6" role="tablist" id="kiosk-tabs">
                        <button type="button" data-tab="rfid" class="kiosk-tab kiosk-tab-active">💳 RFID Card</button>
                        <button type="button" data-tab="qr" class="kiosk-tab">📱 QR Code</button>
                        <button type="button" data-tab="manual" class="kiosk-tab">⌨️ Manual<span class="kiosk-soon">Segera</span></button>
                        <button type="button" data-tab="biometrik" class="kiosk-tab">👤 Biometrik<span class="kiosk-soon">Segera</span></button>
                    </div>

                    {{-- Panel: RFID (default) --}}
                    <div id="panel-rfid" class="kiosk-panel">
                        <div>
                            <input
                                id="rfid-input"
                                type="text"
                                autocomplete="off"
                                autofocus
                                placeholder="Menunggu sinyal RFID..."
                                class="w-full bg-white/5 border border-white/10 rounded-2xl px-8 py-5 text-2xl tracking-[0.2em] font-mono text-center outline-none focus:border-[#C9A227] focus:bg-white/10 transition-all placeholder:text-white/20"
                            >
                            <div class="mt-3 flex items-center justify-end gap-2">
                                <span id="reader-dot" class="w-2 h-2 rounded-full bg-[#2FBF71] animate-pulse"></span>
                                <span id="reader-label" class="text-[10px] uppercase font-bold text-white/30 tracking-widest">Reader Ready</span>
                            </div>
                        </div>
                    </div>

                    {{-- Panel: QR Code --}}
                    <div id="panel-qr" class="kiosk-panel hidden">
                        <div class="flex items-center gap-6">
                            <div class="scan-frame shrink-0">
                                <video id="qr-video" class="absolute inset-3 rounded-xl object-cover bg-white/5 border border-white/5" style="width:calc(100% - 1.5rem);height:calc(100% - 1.5rem)" muted playsinline></video>
                                <div class="scan-corner tl"></div>
                                <div class="scan-corner tr"></div>
                                <div class="scan-corner bl"></div>
                                <div class="scan-corner br"></div>
                                <div class="scan-line"></div>
                            </div>
                            <div class="text-left">
                                <p class="text-lg font-semibold mb-1">Arahkan Kode QR ke Kamera</p>
                                <p class="text-sm text-white/50 leading-relaxed">Pastikan kode QR terlihat jelas dan tidak terhalang di dalam bingkai pemindai.</p>
                                <p id="qr-status" class="text-sm text-white/60 mt-4 min-h-[1.25rem]"></p>
                            </div>
                        </div>
                    </div>

                    {{-- Panel: Manual (NIS/NIP) --}}
                    <div id="panel-manual" class="kiosk-panel hidden">
                        <p class="text-sm text-[#C9A227] mb-3">Input manual belum tersedia. Gunakan kartu RFID atau kode QR.</p>
                        <div class="flex gap-6 opacity-40 pointer-events-none" aria-disabled="true">
                            <div class="flex-1">
                                <input
                                    id="manual-input"
                                    type="text"
                                    autocomplete="off"
                                    readonly
                                    placeholder="Masukkan NIS / NIP"
                                    class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-xl tracking-[0.15em] font-mono text-center outline-none focus:border-[#C9A227] transition-all placeholder:text-white/20 mb-3"
                                >
                                <button id="manual-submit" type="button" disabled class="w-full bg-[#C9A227] text-[#1B3A34] font-bold rounded-2xl py-3 hover:bg-[#C9A227]/90 transition-colors">
                                    Belum tersedia
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-2 w-48 shrink-0" id="manual-keypad">
                                <button type="button" class="keypad-btn" data-key="1">1</button>
                                <button type="button" class="keypad-btn" data-key="2">2</button>
                                <button type="button" class="keypad-btn" data-key="3">3</button>
                                <button type="button" class="keypad-btn" data-key="4">4</button>
                                <button type="button" class="keypad-btn" data-key="5">5</button>
                                <button type="button" class="keypad-btn" data-key="6">6</button>
                                <button type="button" class="keypad-btn" data-key="7">7</button>
                                <button type="button" class="keypad-btn" data-key="8">8</button>
                                <button type="button" class="keypad-btn" data-key="9">9</button>
                                <button type="button" class="keypad-btn text-sm" data-key="clear">Hapus</button>
                                <button type="button" class="keypad-btn" data-key="0">0</button>
                                <button type="button" class="keypad-btn text-sm" data-key="back">⌫</button>
                            </div>
                        </div>
                    </div>

                    {{-- Panel: Biometrik (Wajah) --}}
                    <div id="panel-biometrik" class="kiosk-panel hidden">
                        <div class="flex items-center gap-6">
                            <div class="relative shrink-0 flex items-center justify-center" style="width:220px;height:220px;">
                                <div class="face-oval"></div>
                            </div>
                            <div class="text-left">
                                <p class="text-lg font-semibold mb-1">Posisikan Wajah Anda di Dalam Bingkai</p>
                                <p class="text-sm text-white/50 leading-relaxed">Lepas masker/topi jika memungkinkan dan pastikan wajah menghadap kamera secara langsung.</p>
                                <div class="flex items-center gap-2 mt-4">
                                    <span class="w-2 h-2 rounded-full bg-white/30"></span>
                                    <span class="text-[10px] uppercase font-bold text-white/30 tracking-widest">Belum tersedia · gunakan RFID atau QR</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-2 pl-12 text-right">
                    <div class="flex items-center gap-3 text-xs font-bold tracking-widest text-white/30 uppercase">
                        <span>Platform System</span>
                        <div class="w-1 h-1 rounded-full bg-white/30"></div>
                        <span>v2.4.0-SaaS</span>
                    </div>
                    <div class="flex items-center gap-4 bg-white/5 px-4 py-2 rounded-lg border border-white/5">
                         <div class="flex flex-col items-end">
                            <span class="text-[10px] text-white/40 uppercase font-bold tracking-tighter">Status Sistem</span>
                            <span id="gateway-status" class="text-xs text-white/40 font-bold">MENGECEK…</span>
                         </div>
                         <div class="w-px h-6 bg-white/10"></div>
                         <div class="flex flex-col items-end">
                            <span class="text-[10px] text-white/40 uppercase font-bold tracking-tighter">Lokasi</span>
                            <span class="text-xs font-bold uppercase">{{ $deviceLocation ?: '—' }}</span>
                         </div>
                    </div>
                </div>
            </div>
        </footer>

        {{-- Aktivasi device: device key diminta sekali per browser (disimpan di localStorage) --}}
        <div id="device-key-modal" class="hidden fixed inset-0 bg-[#1B3A34]/95 backdrop-blur-sm flex items-center justify-center px-10 z-[100]">
            <div class="bg-[#F6F3EC] rounded-3xl p-10 max-w-md w-full shadow-2xl text-[#1B3A34]">
                <h2 class="voice text-2xl font-bold mb-2">Aktivasi Perangkat</h2>
                <p class="text-[#1B3A34]/60 text-sm mb-8 leading-relaxed">Masukkan Device Key untuk terminal <span class="font-bold">{{ $device->device_code }}</span>. Kunci ini akan disimpan secara aman di penyimpanan lokal peramban ini.</p>
                <div class="space-y-4">
                    <input id="device-key-input" type="password" autocomplete="off" class="w-full bg-white border-2 border-[#1B3A34]/10 rounded-2xl px-6 py-4 outline-none focus:border-[#C9A227] transition-colors" placeholder="••••••••••••">
                    <button id="device-key-save" type="button" class="w-full bg-[#1B3A34] text-[#F6F3EC] font-bold rounded-2xl py-5 hover:bg-[#1B3A34]/90 transition-colors shadow-lg shadow-[#1B3A34]/20">Konfigurasi Terminal</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>