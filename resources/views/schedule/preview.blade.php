@extends('layouts.app')

@section('title', 'Rencana Belajar - Fluidity')

@use('Carbon\Carbon')
@php
    $plan = collect($schedulePlan ?? []);
    // Kunci asli dipertahankan supaya indeks input form tetap sama
    $days = $plan->groupBy(fn ($slot) => Carbon::parse($slot['scheduled_start'])->toDateString(), true);
    $courseColor = $topics->mapWithKeys(fn ($t) => [$t->id => $t->course->color ?? '#0f766e']);
    $maxDaily = $user->max_daily_sessions ?? 2;
@endphp

@section('content')
<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke Dashboard
    </a>

    <h1 class="mt-6 text-2xl sm:text-3xl font-medium text-slate-900">Rencana belajar 14 hari ke depan</h1>
    <p class="mt-2 max-w-2xl text-slate-600">
        Disusun dari bobot materi, tingkat penguasaan, dan sisa hari menuju ujian. Periksa dulu, lalu konfirmasi.
    </p>
    @if(!empty($calendarConnected))
        <p class="mt-3 inline-flex items-center gap-2 text-sm text-slate-600">
            <span class="h-2 w-2 rounded-full bg-emerald-600" aria-hidden="true"></span>
            {{ $busyCount ?? 0 }} agenda di Google Calendar dilewati
        </p>
    @endif

    <!-- Jam luang -->
    @php
        $currentStart = substr($user->study_window_start ?? '08:00', 0, 5);
        $currentEnd = substr($user->study_window_end ?? '22:00', 0, 5);
        [$startH, $startM] = explode(':', $currentStart);
        [$endH, $endM] = explode(':', $currentEnd);
    @endphp
    <section x-data="{ openPref: false }" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 text-xs text-slate-600 space-y-4" aria-label="Jam luang belajar">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-sm">
                    <span class="font-medium text-slate-800">Jam luang belajar:</span>
                    <span class="font-medium text-slate-900 tabular-nums">{{ $currentStart }} – {{ $currentEnd }} WIB</span>
                </p>
                <p class="mt-0.5 text-xs text-slate-500">Maksimal {{ $maxDaily }} sesi per hari</p>
            </div>
            <button type="button" @click="openPref = !openPref"
                class="self-start sm:self-auto rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-800 hover:bg-slate-100 transition-colors">
                <span x-text="openPref ? 'Tutup' : 'Atur jam luang'"></span>
            </button>
        </div>

        <form x-show="openPref" x-transition method="POST" action="{{ route('preferences.update') }}"
            x-data="{ startH: '{{ $startH }}', startM: '{{ $startM }}', endH: '{{ $endH }}', endM: '{{ $endM }}' }"
            class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            @csrf
            <input type="hidden" name="study_window_start" :value="startH + ':' + startM">
            <input type="hidden" name="study_window_end" :value="endH + ':' + endM">
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Mulai (24 jam)</label>
                <div class="flex items-center gap-1">
                    <select x-model="startH" aria-label="Jam mulai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @for($h = 0; $h < 24; $h++)
                            @php $hh = sprintf('%02d', $h); @endphp
                            <option value="{{ $hh }}">{{ $hh }}</option>
                        @endfor
                    </select>
                    <span class="text-slate-500">:</span>
                    <select x-model="startM" aria-label="Menit mulai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @for($m = 0; $m < 60; $m++)
                            @php $mm = sprintf('%02d', $m); @endphp
                            <option value="{{ $mm }}">{{ $mm }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Selesai (24 jam)</label>
                <div class="flex items-center gap-1">
                    <select x-model="endH" aria-label="Jam selesai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @for($h = 0; $h < 24; $h++)
                            @php $hh = sprintf('%02d', $h); @endphp
                            <option value="{{ $hh }}">{{ $hh }}</option>
                        @endfor
                    </select>
                    <span class="text-slate-500">:</span>
                    <select x-model="endM" aria-label="Menit selesai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @for($m = 0; $m < 60; $m++)
                            @php $mm = sprintf('%02d', $m); @endphp
                            <option value="{{ $mm }}">{{ $mm }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div>
                <label for="max_daily_sessions" class="mb-1 block text-xs font-medium text-slate-600">Maks. sesi per hari</label>
                <select id="max_daily_sessions" name="max_daily_sessions" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                    @for($s = 1; $s <= 4; $s++)
                        <option value="{{ $s }}" {{ $maxDaily == $s ? 'selected' : '' }}>{{ $s }} sesi / hari</option>
                    @endfor
                </select>
            </div>
            <div>
                <button type="submit" class="w-full rounded-lg bg-primary-600 px-4 py-2 font-medium text-white hover:bg-primary-700 transition-colors">
                    Simpan dan hitung ulang
                </button>
            </div>
        </form>
    </section>

    @if($plan->isEmpty())
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-10 text-center">
            <h2 class="text-base font-medium text-slate-900">Belum ada jadwal yang bisa disusun</h2>
            <p class="mt-1 text-sm text-slate-500">Pastikan sudah menambahkan mata kuliah dengan tanggal ujian mendatang.</p>
        </section>
    @else
        <!-- Ringkasan -->
        <section class="mt-6 grid grid-cols-2 sm:grid-cols-4 gap-3" aria-label="Ringkasan rencana">
            @foreach([
                ['Total sesi', $plan->count()],
                ['Hari belajar', $days->count()],
                ['Topik tercakup', $topics->count()],
                ['Maks. sesi per hari', $maxDaily],
            ] as [$label, $value])
                <div class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-medium text-slate-900 tabular-nums">{{ $value }}</p>
                </div>
            @endforeach
        </section>

        <!-- Agenda per hari -->
        <form method="POST" action="{{ route('schedule.confirm') }}" class="mt-8">
            @csrf

            <div class="space-y-6">
                @foreach($days as $date => $slots)
                    <section aria-labelledby="day-{{ $date }}">
                        <div class="flex items-baseline justify-between gap-3">
                            <h2 id="day-{{ $date }}" class="text-sm font-medium text-slate-900">{{ Carbon::parse($date)->locale('id')->isoFormat('dddd, D MMMM') }}</h2>
                            <span class="font-mono text-xs text-slate-500 tabular-nums">{{ $slots->count() }} sesi</span>
                        </div>

                        <ol class="mt-2 divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                            @foreach($slots as $i => $slot)
                                @php
                                    $start = Carbon::parse($slot['scheduled_start']);
                                    $end = Carbon::parse($slot['scheduled_end']);
                                    $priority = (int) round($slot['priority_score'] * 100);
                                    $priorityInk = $priority >= 70 ? 'text-red-700' : ($priority >= 40 ? 'text-amber-700' : 'text-slate-500');
                                @endphp
                                <li class="flex items-start gap-4 px-5 py-4">
                                    <div class="w-14 shrink-0 tabular-nums">
                                        <p class="text-sm font-medium text-slate-900">{{ $start->format('H.i') }}</p>
                                        <p class="text-xs text-slate-500">{{ $end->format('H.i') }}</p>
                                    </div>
                                    <div class="min-w-0 flex-1 border-l-2 pl-3" style="border-color: {{ $courseColor[$slot['topic_id']] ?? '#0f766e' }}">
                                        <p class="text-xs text-slate-500">{{ $slot['course_name'] }}</p>
                                        <h3 class="text-sm font-medium text-slate-900">{{ $slot['topic_title'] }}</h3>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $slot['reason'] }}</p>
                                        {{-- Di layar sempit prioritas turun ke bawah supaya judul tidak terjepit --}}
                                        <p class="mt-1 sm:hidden font-mono text-xs tabular-nums {{ $priorityInk }}">Prioritas {{ $priority }}%</p>
                                    </div>
                                    <span class="hidden sm:block shrink-0 font-mono text-xs tabular-nums {{ $priorityInk }}">
                                        Prioritas {{ $priority }}%
                                    </span>

                                    <input type="hidden" name="sessions[{{ $i }}][topic_id]" value="{{ $slot['topic_id'] }}">
                                    <input type="hidden" name="sessions[{{ $i }}][scheduled_start]" value="{{ $slot['scheduled_start'] }}">
                                    <input type="hidden" name="sessions[{{ $i }}][scheduled_end]" value="{{ $slot['scheduled_end'] }}">
                                    <input type="hidden" name="sessions[{{ $i }}][duration_minutes]" value="{{ $slot['duration_minutes'] }}">
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endforeach
            </div>

            <!-- Konfirmasi -->
            <div class="mt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
                <p class="text-sm text-slate-600">
                    {{ $plan->count() }} sesi dalam {{ $days->count() }} hari
                </p>
                <button type="submit" class="h-11 w-full sm:w-auto rounded-lg bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                    @if(!empty($calendarConnected))
                        Tambahkan {{ $plan->count() }} sesi ke Google Calendar
                    @else
                        Jadwalkan {{ $plan->count() }} sesi
                    @endif
                </button>
            </div>
        </form>
    @endif

</div>
@endsection
