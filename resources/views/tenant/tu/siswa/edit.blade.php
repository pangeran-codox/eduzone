@extends('tenant.layouts.app')

@section('title', 'Edit Siswa')
@section('page-title', 'Edit Siswa — ' . $student->full_name)

@section('content')

<div class="t-card p-6 max-w-3xl">

    @if ($errors->any())
    <div class="mb-5 p-4 rounded-xl" style="background: var(--t-red-bg); border: 1px solid var(--t-red-tx);">
        <p class="text-sm font-semibold mb-1" style="color: var(--t-red-tx);">Ada input yang perlu diperbaiki:</p>
        <ul class="text-xs list-disc list-inside" style="color: var(--t-red-tx);">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('tu.siswa.update', $student) }}" class="space-y-8">
        @csrf
        @method('PUT')
        @include('tenant.tu.siswa._form', ['student' => $student])

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white" style="background: var(--t-dark);">
                Simpan Perubahan
            </button>
            <a href="{{ route('tu.siswa.index') }}" class="text-sm" style="color: var(--t-muted);">Batal</a>
        </div>
    </form>
</div>

@endsection

@include('tenant.tu.siswa._form-styles')