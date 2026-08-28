@extends('tenant.layouts.app')

@section('title', 'Edit Device')
@section('page-title', 'Edit Device')

@section('content')

    <div class="max-w-lg">
        <div class="mb-6">
            <a href="{{ route('absensi.devices.index') }}" class="text-xs font-semibold" style="color: var(--t-muted);">&larr; Kembali ke daftar device</a>
        </div>

        <div class="t-card p-6">
            <form method="POST" action="{{ route('absensi.devices.update', $device) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--t-dark);">Kode Device</label>
                    <input type="text" value="{{ $device->device_code }}" disabled
                        class="w-full px-4 py-2.5 rounded-xl border text-sm font-mono" style="border-color: var(--t-border); background: var(--t-slate-bg); color: var(--t-muted);">
                    <p class="text-xs mt-1" style="color: var(--t-muted);">Kode device tidak bisa diubah setelah dibuat.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--t-dark);">Nama Device</label>
                    <input type="text" name="name" value="{{ old('name', $device->name) }}"
                        class="w-full px-4 py-2.5 rounded-xl border text-sm" style="border-color: var(--t-border);">
                    @error('name')
                        <p class="text-xs mt-1" style="color: var(--t-red-tx);">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-2" style="color: var(--t-dark);">Metode Input (bisa pilih lebih dari 1)</label>
                    <div class="space-y-2">
                        @foreach ($capabilityOptions as $value => $label)
                            <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer" style="border-color: var(--t-border);">
                                <input type="checkbox" name="capabilities[]" value="{{ $value }}"
                                    @checked(in_array($value, old('capabilities', $device->capabilities ?? [])))
                                    class="w-4 h-4">
                                <span class="text-sm" style="color: var(--t-dark);">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('capabilities')
                        <p class="text-xs mt-1" style="color: var(--t-red-tx);">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--t-dark);">Lokasi</label>
                    <input type="text" name="location" value="{{ old('location', $device->location) }}"
                        class="w-full px-4 py-2.5 rounded-xl border text-sm" style="border-color: var(--t-border);">
                    @error('location')
                        <p class="text-xs mt-1" style="color: var(--t-red-tx);">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--t-dark);">Kelas Default (opsional)</label>
                    <select name="default_class_id" class="w-full px-4 py-2.5 rounded-xl border text-sm" style="border-color: var(--t-border);">
                        <option value="">— Tidak terikat kelas tertentu —</option>
                        @foreach ($classes as $id => $namaKelas)
                            <option value="{{ $id }}" @selected(old('default_class_id', $device->default_class_id) === $id)>{{ $namaKelas }}</option>
                        @endforeach
                    </select>
                    @error('default_class_id')
                        <p class="text-xs mt-1" style="color: var(--t-red-tx);">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--t-dark);">Alamat IP (opsional)</label>
                    <input type="text" name="ip_address" value="{{ old('ip_address', $device->ip_address) }}"
                        class="w-full px-4 py-2.5 rounded-xl border text-sm font-mono" style="border-color: var(--t-border);">
                    @error('ip_address')
                        <p class="text-xs mt-1" style="color: var(--t-red-tx);">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $device->is_active)) class="w-4 h-4">
                    <label for="is_active" class="text-sm font-medium" style="color: var(--t-dark);">Device aktif</label>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm" style="background: var(--t-dark); color: #F6F3EC;">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection