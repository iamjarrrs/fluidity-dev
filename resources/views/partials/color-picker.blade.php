@php
    $swatches = [
        ['hex' => '#3b82f6', 'label' => 'Ocean Blue'],
        ['hex' => '#6366f1', 'label' => 'Indigo'],
        ['hex' => '#8b5cf6', 'label' => 'Violet'],
        ['hex' => '#ec4899', 'label' => 'Rose'],
        ['hex' => '#ef4444', 'label' => 'Coral'],
        ['hex' => '#f59e0b', 'label' => 'Amber'],
        ['hex' => '#10b981', 'label' => 'Emerald'],
        ['hex' => '#06b6d4', 'label' => 'Cyan'],
    ];
    $initialColor = strtolower($selectedColor ?? '#3b82f6');
@endphp

<div x-data="{ color: '{{ $initialColor }}' }" class="space-y-2">
    <input type="hidden" name="color" :value="color">

    <div class="flex flex-wrap items-center gap-2.5">
        @foreach($swatches as $swatch)
            <button type="button"
                @click="color = '{{ $swatch['hex'] }}'"
                title="{{ $swatch['label'] }}"
                class="w-7 h-7 rounded-full transition-all duration-150 flex items-center justify-center focus:outline-none"
                style="background-color: {{ $swatch['hex'] }};"
                :class="color === '{{ $swatch['hex'] }}' ? 'ring-2 ring-offset-2 ring-slate-800 scale-110 shadow-sm' : 'opacity-85 hover:opacity-100 hover:scale-105'">
                <svg x-show="color === '{{ $swatch['hex'] }}'" class="w-3.5 h-3.5 text-white drop-shadow" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        @endforeach

        <!-- Custom Color Picker Opsional -->
        <label title="Pilih warna kustom"
            class="w-7 h-7 rounded-full border-2 border-dashed border-slate-300 hover:border-slate-500 flex items-center justify-center cursor-pointer transition overflow-hidden relative bg-gradient-to-tr from-rose-400 via-amber-300 to-sky-400"
            :class="!['#3b82f6','#6366f1','#8b5cf6','#ec4899','#ef4444','#f59e0b','#10b981','#06b6d4'].includes(color) ? 'ring-2 ring-offset-2 ring-slate-800 scale-110' : 'opacity-80 hover:opacity-100'">
            <input type="color" x-model="color" class="opacity-0 absolute inset-0 w-full h-full cursor-pointer">
            <span class="text-[11px] font-bold text-white drop-shadow">+</span>
        </label>
    </div>

    <div class="flex items-center gap-2 text-[11px] text-slate-500">
        <span class="w-3 h-3 rounded-full inline-block border border-slate-200" :style="'background-color: ' + color"></span>
        <span>Warna terpilih: <code class="font-mono font-semibold text-slate-700" x-text="color.toUpperCase()"></code></span>
    </div>
</div>
