@extends('tenant.layouts.app')

@section('title', 'Edit Guru')
@section('page-title', 'Edit Guru')

@section('content')
@include('tenant.tu.siswa._form-styles')

<div class="max-w-3xl">
    @if ($errors->any())
        <div class="t-card p-4 mb-4" style="background: var(--t-red-bg); border-color: var(--t-red-tx); border-width: 1px;">
            @foreach ($errors->all() as $error)
                <p class="text-sm font-semibold" style="color: var(--t-red-tx);">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('tu.guru.update', $teacher) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')
        @include('tenant.tu.guru._form')

        <div class="flex justify-end gap-3">
            <a href="{{ route('tu.guru.index') }}" class="px-4 py-2 rounded-lg text-sm border" style="border-color: var(--t-border); color: var(--t-muted);">Batal</a>
            <button type="submit" class="px-5 py-2 rounded-lg text-sm font-semibold" style="background: var(--t-gold); color: var(--t-dark);">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection