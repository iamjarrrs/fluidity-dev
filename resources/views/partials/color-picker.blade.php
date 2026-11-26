@php
    // Sengaja menghindari rose, violet, amber, dan sky karena itu warna mode sesi
    $swatches = [
        '#0f766e' => 'Teal',
        '#15803d' => 'Hijau',
        '#65a30d' => 'Lime',
        '#3730a3' => 'Indigo',
        '#be185d' => 'Magenta',
        '#c2410c' => 'Oranye',
        '#92400e' => 'Cokelat',
        '#475569' => 'Abu',
    ];
    $initialColor = strtolower($selectedColor ?? '#0f766e');
@endphp

{{-- Memancarkan event course-color supaya bagian lain halaman bisa ikut menampilkan warnanya --}}
<div x-data="{ color: '{{ $initialColor }}', presets: @js(array_keys($swatches)) }" x-effect="$dispatch('course-color', color)" class="space-y-3">
    <input type="hidden" name="color" :value="color">

    <div class="flex flex-wrap items-center gap-3" role="group" aria-label="Pilih warna mata kuliah">
        @foreach($swatches as $hex => $name)
            <button type="button" @click="color = '{{ $hex }}'"
                aria-label="{{ $name }}" title="{{ $name }}" :aria-pressed="color === '{{ $hex }}'"
                class="grid size-7 place-items-center rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                :class="color === '{{ $hex }}' && 'ring-2 ring-slate-900 ring-offset-2'"
                style="background-color: {{ $hex }}">
                <svg x-show="color === '{{ $hex }}'" class="size-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
            </button>
        @endforeach

        <label title="Warna lain"
            class="relative grid size-7 cursor-pointer place-items-center overflow-hidden rounded-full border border-dashed border-slate-400 bg-white focus-within:ring-2 focus-within:ring-primary-500 focus-within:ring-offset-2"
            :class="!presets.includes(color) && 'border-solid border-transparent ring-2 ring-slate-900 ring-offset-2'"
            :style="!presets.includes(color) && 'background-color: ' + color">
            <input type="color" x-model="color" aria-label="Warna lain" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
            <svg x-show="presets.includes(color)" class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
        </label>
    </div>

    <p class="text-xs text-slate-500">
        Warna terpilih <code class="font-mono text-slate-700" x-text="color.toUpperCase()"></code>
    </p>
</div>
