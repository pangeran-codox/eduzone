@props([
    'name',
    'label',
    'type' => 'text',
    'as' => 'input',
    'required' => false,
    'hint' => null,
    'rows' => 3,
])

@php
    $id = 'field-' . $name;
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id . '-hint ' : '') . ($error ? $id . '-error' : ''));

    // Token warna sama dengan auth/login.blade.php (border #D8D4C6, ring #1B3A34, error #A32D2D)
    $control = 'block w-full rounded-md border bg-white px-3 text-sm text-[#1C1C1A] placeholder:text-[#9A9A8E] focus:outline-none focus:ring-2';
    $size = $as === 'textarea' ? 'py-2' : 'h-10';
    $tone = $error
        ? 'border-[#A32D2D] focus:ring-[#A32D2D]/40'
        : 'border-[#D8D4C6] focus:ring-[#1B3A34]';
@endphp

{{-- Hanya `class` yang dipasang di pembungkus (mis. sm:col-span-2); atribut lain ke input --}}
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="block text-xs font-medium mb-1.5 text-[#4A4A44]">
        {{ $label }}
        @unless ($required)
            <span class="font-normal text-[#8A8A80]">(opsional)</span>
        @endunless
    </label>

    @if ($as === 'textarea')
        <textarea
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @required($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except('class') }}
            class="{{ $control }} {{ $size }} {{ $tone }}"
        >{{ old($name) }}</textarea>
    @else
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : old($name) }}"
            @required($required)
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except('class') }}
            class="{{ $control }} {{ $size }} {{ $tone }}"
        >
    @endif

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs mt-1 text-[#8A8A80]">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-xs mt-1 text-[#A32D2D]">{{ $error }}</p>
    @endif
</div>
