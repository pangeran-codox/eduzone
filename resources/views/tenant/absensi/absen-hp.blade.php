@extends('tenant.layouts.app')

@section('title', 'Absen via HP')
@section('page-title', 'Absen via HP')

@section('content')

<div class="max-w-md mx-auto">

    @if (session('success'))
        <div class="t-card p-4 mb-4" style="background: var(--t-green-bg); border-color: var(--t-green-tx); border-width: 1px;">
            <p class="text-sm font-semibold" style="color: var(--t-green-tx);">{{ session('success') }}</p>
        </div>
    @endif

    @error('location')
        <div class="t-card p-4 mb-4" style="background: var(--t-red-bg); border-color: var(--t-red-tx); border-width: 1px;">
            <p class="text-sm font-semibold" style="color: var(--t-red-tx);">{{ $message }}</p>
        </div>
    @enderror

    @if (! $school)
        <div class="t-card p-8 text-center">
            <p class="text-sm" style="color: var(--t-muted);">
                Data lokasi sekolah belum tersedia. Hubungi admin sekolah.
            </p>
        </div>
    @else

        <div class="t-card p-6 text-center">
            <div id="geo-status" class="mb-6">
                <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center mb-3" style="background: var(--t-slate-bg);">
                    <svg class="w-6 h-6" style="color: var(--t-muted);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.5-7.5 11.25-7.5 11.25S4.5 18 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                </div>
                <p id="geo-status-text" class="text-sm" style="color: var(--t-muted);">Meminta izin lokasi…</p>
            </div>

            <form method="POST" action="{{ route('absen-hp.store') }}" id="absen-form">
                @csrf
                <input type="hidden" name="latitude" id="input-latitude">
                <input type="hidden" name="longitude" id="input-longitude">
                <input type="hidden" name="accuracy" id="input-accuracy">
                <input type="hidden" name="event_type" id="input-event-type" value="check_in">

                <div class="inline-flex rounded-xl p-1 mb-6" style="background: var(--t-slate-bg);" id="event-toggle">
                    <button type="button" data-event="check_in" class="px-5 py-2 rounded-lg text-sm font-semibold transition-colors" style="background: var(--t-gold); color: var(--t-dark);">
                        Masuk
                    </button>
                    <button type="button" data-event="check_out" class="px-5 py-2 rounded-lg text-sm font-semibold transition-colors" style="color: var(--t-muted);">
                        Pulang
                    </button>
                </div>

                <button
                    type="submit"
                    id="submit-btn"
                    disabled
                    class="w-full py-3.5 rounded-xl font-bold text-sm transition-colors"
                    style="background: var(--t-slate-bg); color: var(--t-muted); cursor: not-allowed;"
                >
                    Menunggu lokasi…
                </button>
            </form>
        </div>

        <p class="text-xs text-center mt-4" style="color: var(--t-muted);">
            Radius absen: {{ $school->geofence_radius_meters }} meter dari sekolah.
            Pastikan GPS aktif dan izin lokasi diperbolehkan di browser.
        </p>

    @endif

</div>

<script>
    const statusText  = document.getElementById('geo-status-text');
    const submitBtn   = document.getElementById('submit-btn');
    const latInput    = document.getElementById('input-latitude');
    const lonInput    = document.getElementById('input-longitude');
    const accInput    = document.getElementById('input-accuracy');
    const eventInput  = document.getElementById('input-event-type');
    const toggleBtns  = document.querySelectorAll('#event-toggle [data-event]');

    if (toggleBtns.length) {
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                eventInput.value = btn.dataset.event;
                toggleBtns.forEach(b => {
                    b.style.background = 'transparent';
                    b.style.color = 'var(--t-muted)';
                });
                btn.style.background = 'var(--t-gold)';
                btn.style.color = 'var(--t-dark)';
            });
        });
    }

    function setReady() {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Konfirmasi Absen';
        submitBtn.style.background = 'var(--t-dark)';
        submitBtn.style.color = '#F6F3EC';
        submitBtn.style.cursor = 'pointer';
    }

    if (statusText) {
        if (!navigator.geolocation) {
            statusText.textContent = 'Perangkat/browser ini tidak mendukung lokasi GPS.';
        } else {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    latInput.value = position.coords.latitude;
                    lonInput.value = position.coords.longitude;
                    accInput.value = position.coords.accuracy;
                    statusText.textContent = 'Lokasi ditemukan (akurasi ±' + Math.round(position.coords.accuracy) + ' m).';
                    setReady();
                },
                () => {
                    statusText.textContent = 'Gagal mengambil lokasi — izin ditolak atau GPS nonaktif. Aktifkan lokasi lalu muat ulang halaman.';
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }
    }
</script>

@endsection