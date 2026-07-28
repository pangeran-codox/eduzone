// Entry JS khusus layar kiosk (RFID/QR/Face/manual device absensi).
// SENGAJA vanilla JS, TANPA Alpine.js - lihat FRONTEND.md §5.
//
// PENTING: check-in device sekarang manggil LANGSUNG ke absensi-gateway
// (Go), BUKAN ke endpoint Laravel - lihat README.md absensi-gateway &
// checkin_device.go untuk kontrak API-nya. Laravel di sini cuma
// nge-render halaman ini, bukan lagi yang nyatet attendance_events untuk
// device flow.
//
// GATEWAY_BASE_PATH diarahkan lewat Nginx Proxy Manager Custom Location
// (path /gateway di domain yang sama dengan halaman ini), BUKAN hostname
// Docker internal (mis. "absensi-gateway") - browser di kiosk fisik tidak
// bisa resolve hostname Docker itu. Ini juga sekalian menghindari CORS
// karena origin-nya jadi sama persis dengan halaman kiosk.
// import '../bootstrap';
import '../../css/app.css';

const GATEWAY_BASE_PATH = '/gateway/api/v1';

const root = document.getElementById('kiosk-root');
const deviceCode = root.dataset.deviceCode;
const DEVICE_KEY_STORAGE = `kiosk_device_key_${deviceCode}`;

// ---------------------------------------------------------------------
// Jam & tanggal
// ---------------------------------------------------------------------
function tickClock() {
    const now = new Date();
    document.getElementById('kiosk-clock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
    document.getElementById('kiosk-date').textContent = now.toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}
tickClock();
setInterval(tickClock, 1000);

// ---------------------------------------------------------------------
// Setup device key (sekali per browser, disimpan di localStorage)
// ---------------------------------------------------------------------
const deviceKeyModal = document.getElementById('device-key-modal');

function getDeviceKey() {
    return localStorage.getItem(DEVICE_KEY_STORAGE);
}

function ensureDeviceKey() {
    if (!getDeviceKey()) {
        deviceKeyModal.classList.remove('hidden');
    }
}

document.getElementById('device-key-save').addEventListener('click', () => {
    const value = document.getElementById('device-key-input').value.trim();
    if (!value) return;
    localStorage.setItem(DEVICE_KEY_STORAGE, value);
    deviceKeyModal.classList.add('hidden');
    focusActiveInput();
});

ensureDeviceKey();

// ---------------------------------------------------------------------
// Toggle Masuk / Pulang - event_type wajib dikirim ke gateway
// ---------------------------------------------------------------------
let activeEventType = 'check_in';
const eventToggles = document.querySelectorAll('.event-toggle');

eventToggles.forEach((btn) => {
    btn.addEventListener('click', () => {
        eventToggles.forEach((b) => b.classList.remove('event-toggle-active'));
        btn.classList.add('event-toggle-active');
        activeEventType = btn.dataset.eventType;
    });
});

// ---------------------------------------------------------------------
// Tab switching (RFID / QR aktif, Manual & Wajah masih placeholder -
// gateway belum dukung method itu, lihat komentar di checkin.blade.php)
// ---------------------------------------------------------------------
const tabs = document.querySelectorAll('.kiosk-tab');
const panels = document.querySelectorAll('.kiosk-panel');

tabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        tabs.forEach((t) => t.classList.remove('kiosk-tab-active'));
        tab.classList.add('kiosk-tab-active');

        panels.forEach((p) => p.classList.add('hidden'));
        document.getElementById(`panel-${tab.dataset.tab}`).classList.remove('hidden');

        if (tab.dataset.tab === 'qr') startQrScanner();
        else stopQrScanner();

        focusActiveInput();
    });
});

function focusActiveInput() {
    const activeTab = document.querySelector('.kiosk-tab-active')?.dataset.tab;
    if (activeTab === 'rfid') document.getElementById('rfid-input')?.focus();
}

// ---------------------------------------------------------------------
// RFID (reader USB berperilaku seperti keyboard, ketik UID lalu Enter)
// ---------------------------------------------------------------------
const rfidInput = document.getElementById('rfid-input');
rfidInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && rfidInput.value.trim()) {
        submitCheckin('rfid', rfidInput.value.trim());
        rfidInput.value = '';
    }
});

// ---------------------------------------------------------------------
// QR - pakai BarcodeDetector native (Chrome/Edge). Browser lain: fallback
// pesan, karena nambah library JS eksternal (mis. jsQR) belum dipasang di
// project ini - tinggal npm install kalau nanti butuh dukungan lebih luas.
// ---------------------------------------------------------------------
let qrStream = null;
let qrRafId = null;
let qrLastValue = null;
let qrLastAt = 0;

async function startQrScanner() {
    const statusEl = document.getElementById('qr-status');
    const videoEl = document.getElementById('qr-video');

    if (!('BarcodeDetector' in window)) {
        statusEl.textContent = 'Browser ini belum mendukung scan QR native. Gunakan RFID, atau pasang library QR (mis. jsQR) untuk dukungan lebih luas.';
        return;
    }

    try {
        qrStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        videoEl.srcObject = qrStream;
        await videoEl.play();
        statusEl.textContent = 'Arahkan QR ke kamera...';

        const detector = new BarcodeDetector({ formats: ['qr_code'] });

        const scanLoop = async () => {
            if (!qrStream) return;
            try {
                const codes = await detector.detect(videoEl);
                if (codes.length > 0) {
                    const value = codes[0].rawValue;
                    const now = Date.now();
                    if (value !== qrLastValue || now - qrLastAt > 3000) {
                        qrLastValue = value;
                        qrLastAt = now;
                        submitCheckin('qr', value);
                    }
                }
            } catch (err) {
                // deteksi gagal sesaat, abaikan dan lanjut loop
            }
            qrRafId = requestAnimationFrame(scanLoop);
        };
        scanLoop();
    } catch (err) {
        statusEl.textContent = 'Tidak bisa mengakses kamera: ' + err.message;
    }
}

function stopQrScanner() {
    if (qrRafId) cancelAnimationFrame(qrRafId);
    if (qrStream) {
        qrStream.getTracks().forEach((track) => track.stop());
        qrStream = null;
    }
}

// ---------------------------------------------------------------------
// Kirim check-in ke absensi-gateway & tampilkan status
// Kontrak sesuai checkin_device.go:
//   request  {method, event_type, credential_value, client_timestamp}
//   sukses   {status:"accepted"|"accepted_with_flag", event_id, person:{id,name,type}, schedule_id?, anomaly_reasons?}
//   error    4xx/5xx dengan body {status, reason, message} (lihat writeError)
//   lockout  429 + header Retry-After
// ---------------------------------------------------------------------
async function submitCheckin(method, credentialValue) {
    const deviceKey = getDeviceKey();
    if (!deviceKey) {
        ensureDeviceKey();
        return;
    }

    try {
        const response = await fetch(`${GATEWAY_BASE_PATH}/checkin/device`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Device-Key': deviceKey,
            },
            body: JSON.stringify({
                method,
                event_type: activeEventType,
                credential_value: credentialValue,
                client_timestamp: new Date().toISOString(),
            }),
        });

        if (response.status === 401) {
            // device key salah/nonaktif - minta setup ulang
            localStorage.removeItem(DEVICE_KEY_STORAGE);
            ensureDeviceKey();
            return;
        }

        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After');
            showResult({
                success: false,
                message: `Terlalu banyak percobaan gagal. Coba lagi dalam ${retryAfter ?? 'beberapa'} detik.`,
            });
            return;
        }

        const data = await response.json();

        if (!response.ok) {
            showResult({ success: false, message: data.message ?? 'Kartu/kode tidak dikenali' });
            return;
        }

        showResult({
            success: true,
            flagged: data.status === 'accepted_with_flag',
            personName: data.person?.name,
            eventType: activeEventType,
        });
    } catch (err) {
        showResult({ success: false, message: 'Gagal terhubung ke server absensi.' });
    }
}

function showResult({ success, flagged, personName, eventType, message }) {
    const idle = document.getElementById('status-idle');
    const success_ = document.getElementById('status-success');
    const failed = document.getElementById('status-failed');
    const successIcon = document.getElementById('status-success-icon');

    idle.classList.add('hidden');

    if (success) {
        success_.classList.remove('hidden');
        success_.classList.add('flex');
        document.getElementById('status-success-name').textContent = personName ?? '';
        document.getElementById('status-success-detail').textContent =
            (eventType === 'check_in' ? 'Hadir · Masuk' : 'Tercatat · Pulang') +
            (flagged ? ' (ditandai untuk ditinjau)' : '');
        // Kalau flagged (anomaly_reasons terisi, mis. duplicate_scan_within_5s),
        // event tetap tercatat di gateway tapi warnai kuning sebagai sinyal
        // "berhasil tapi perlu dicek", bukan hijau polos.
        successIcon.style.background = flagged ? '#D4A017' : '#2FBF71';
    } else {
        failed.classList.remove('hidden');
        failed.classList.add('flex');
        document.getElementById('status-failed-message').textContent = message ?? 'Kartu/kode tidak dikenali';
    }

    setTimeout(() => {
        success_.classList.add('hidden');
        success_.classList.remove('flex');
        failed.classList.add('hidden');
        failed.classList.remove('flex');
        idle.classList.remove('hidden');
        focusActiveInput();
    }, 3000);
}

focusActiveInput();
