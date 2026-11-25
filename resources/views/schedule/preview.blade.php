@extends('layouts.app')

@section('title', 'Preview Jadwal Belajar - Fluidity')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header -->
    <div>
        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1 mb-2">
            &larr; Kembali ke Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900">📅 Preview Jadwal Belajar</h1>
        <p class="text-sm text-slate-500 mt-1">
            Algoritma Fluidity (SM-2 + Exam Urgency) telah menyusun jadwal optimal untuk Anda. Review dulu, lalu konfirmasi.
        </p>

        @if(!empty($calendarConnected))
            <div class="mt-3 inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                Google Calendar Tersambung &bull; {{ $busyCount ?? 0 }} agenda sibuk terdeteksi &amp; otomatis dihindari
            </div>
        @endif

        <!-- Pengaturan Cepat Jam Luang -->
        <div x-data="{ openPref: false }" class="mt-4 bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="text-slate-600">
                    ⏰ <strong class="text-slate-800">Jam Luang Aktif:</strong>
                    <span class="font-bold text-blue-700">{{ substr($user->study_window_start ?? '08:00', 0, 5) }} - {{ substr($user->study_window_end ?? '22:00', 0, 5) }} WIB</span>
                    (Maks. {{ $user->max_daily_sessions ?? 2 }} sesi/hari)
                </div>
                <button type="button" @click="openPref = !openPref" class="text-blue-600 hover:text-blue-800 font-semibold self-start sm:self-auto">
                    <span x-text="openPref ? 'Tutup Pengaturan' : '✏️ Ubah Jam Luang'"></span>
                </button>
            </div>
            @php
                $currentStart = substr($user->study_window_start ?? '08:00', 0, 5);
                $currentEnd = substr($user->study_window_end ?? '22:00', 0, 5);
                [$startH, $startM] = explode(':', $currentStart);
                [$endH, $endM] = explode(':', $currentEnd);
            @endphp
            <form x-show="openPref" x-transition method="POST" action="{{ route('preferences.update') }}"
                x-data="{ startH: '{{ $startH }}', startM: '{{ $startM }}', endH: '{{ $endH }}', endM: '{{ $endM }}' }"
                class="mt-3 pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                @csrf
                <input type="hidden" name="study_window_start" :value="startH + ':' + startM">
                <input type="hidden" name="study_window_end" :value="endH + ':' + endM">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Mulai Luang (24 Jam)</label>
                    <div class="flex items-center gap-1">
                        <select x-model="startH" class="w-full rounded-xl border border-slate-300 px-2 py-1.5 text-xs">
                            @for($h = 0; $h < 24; $h++)
                                @php $hh = sprintf('%02d', $h); @endphp
                                <option value="{{ $hh }}">{{ $hh }}</option>
                            @endfor
                        </select>
                        <span class="font-bold text-slate-500">:</span>
                        <select x-model="startM" class="w-full rounded-xl border border-slate-300 px-2 py-1.5 text-xs">
                            @for($m = 0; $m < 60; $m++)
                                @php $mm = sprintf('%02d', $m); @endphp
                                <option value="{{ $mm }}">{{ $mm }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Selesai Belajar (24 Jam)</label>
                    <div class="flex items-center gap-1">
                        <select x-model="endH" class="w-full rounded-xl border border-slate-300 px-2 py-1.5 text-xs">
                            @for($h = 0; $h < 24; $h++)
                                @php $hh = sprintf('%02d', $h); @endphp
                                <option value="{{ $hh }}">{{ $hh }}</option>
                            @endfor
                        </select>
                        <span class="font-bold text-slate-500">:</span>
                        <select x-model="endM" class="w-full rounded-xl border border-slate-300 px-2 py-1.5 text-xs">
                            @for($m = 0; $m < 60; $m++)
                                @php $mm = sprintf('%02d', $m); @endphp
                                <option value="{{ $mm }}">{{ $mm }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Maks. Sesi / Hari</label>
                    <select name="max_daily_sessions" class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-xs">
                        @for($s = 1; $s <= 4; $s++)
                            <option value="{{ $s }}" {{ ($user->max_daily_sessions ?? 2) == $s ? 'selected' : '' }}>{{ $s }} sesi / hari</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition">
                        🔄 Simpan &amp; Hitung Ulang
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(empty($schedulePlan))
        <div class="bg-white rounded-2xl p-10 border border-slate-200 text-center">
            <div class="text-3xl mb-3">📭</div>
            <h3 class="text-sm font-semibold text-slate-700">Belum ada jadwal yang bisa digenerate</h3>
            <p class="text-xs text-slate-500 mt-1">Pastikan sudah menambahkan mata kuliah dengan tanggal ujian mendatang.</p>
        </div>
    @else
        <!-- Stats Summary -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl p-4 border border-slate-200 text-center">
                <div class="text-2xl font-extrabold text-blue-600">{{ count($schedulePlan) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total Sesi</div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 text-center">
                <div class="text-2xl font-extrabold text-emerald-600">{{ count(collect($schedulePlan)->pluck('scheduled_start')->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())->unique()) }}</div>
                <div class="text-xs text-slate-500 mt-1">Hari Belajar</div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 text-center">
                <div class="text-2xl font-extrabold text-amber-600">{{ $topics->count() }}</div>
                <div class="text-xs text-slate-500 mt-1">Topik Tercakup</div>
            </div>
            <div class="bg-white rounded-xl p-4 border border-slate-200 text-center">
                <div class="text-2xl font-extrabold text-rose-600">2</div>
                <div class="text-xs text-slate-500 mt-1">Maks Sesi/Hari</div>
            </div>
        </div>

        <!-- Schedule List -->
        <form method="POST" action="{{ route('schedule.confirm') }}">
            @csrf

            <div class="space-y-3">
                @php $currentDate = ''; @endphp
                @foreach($schedulePlan as $i => $slot)
                    @php
                        $slotDate = \Carbon\Carbon::parse($slot['scheduled_start'])->toDateString();
                        $isNewDate = $slotDate !== $currentDate;
                        $currentDate = $slotDate;
                    @endphp

                    @if($isNewDate)
                        <div class="pt-4 pb-1">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                {{ \Carbon\Carbon::parse($slotDate)->isoFormat('dddd, D MMMM Y') }}
                            </h3>
                        </div>
                    @endif

                    <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div class="text-center min-w-[56px] bg-slate-50 rounded-lg p-2 border border-slate-100">
                                <div class="text-xs font-bold text-blue-700">
                                    {{ \Carbon\Carbon::parse($slot['scheduled_start'])->format('H:i') }}
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    {{ \Carbon\Carbon::parse($slot['scheduled_end'])->format('H:i') }}
                                </div>
                            </div>
                            <div>
                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700">
                                    {{ $slot['course_name'] }}
                                </span>
                                <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $slot['topic_title'] }}</h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">{{ $slot['reason'] }}</p>
                            </div>
                        </div>

                        <div class="text-right flex-shrink-0">
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                @if($slot['priority_score'] >= 0.7) bg-rose-50 text-rose-700 border border-rose-200
                                @elseif($slot['priority_score'] >= 0.4) bg-amber-50 text-amber-700 border border-amber-200
                                @else bg-emerald-50 text-emerald-700 border border-emerald-200
                                @endif
                            ">
                                Prioritas: {{ number_format($slot['priority_score'] * 100, 0) }}%
                            </span>
                        </div>

                        <!-- Hidden inputs untuk form submit -->
                        <input type="hidden" name="sessions[{{ $i }}][topic_id]" value="{{ $slot['topic_id'] }}">
                        <input type="hidden" name="sessions[{{ $i }}][scheduled_start]" value="{{ $slot['scheduled_start'] }}">
                        <input type="hidden" name="sessions[{{ $i }}][scheduled_end]" value="{{ $slot['scheduled_end'] }}">
                        <input type="hidden" name="sessions[{{ $i }}][duration_minutes]" value="{{ $slot['duration_minutes'] }}">
                    </div>
                @endforeach
            </div>

            <!-- Confirm Button -->
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white rounded-2xl p-6 border border-slate-200">
                <p class="text-xs text-slate-500">
                    ⚡ Jadwal ini dioptimalkan berdasarkan bobot materi, tingkat penguasaan, dan urgensi tanggal ujian Anda.
                </p>
                <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-sm transition text-sm">
                    @if(!empty($calendarConnected))
                        📆 Konfirmasi &amp; Tambahkan ke Google Calendar ({{ count($schedulePlan) }} Sesi)
                    @else
                        ✅ Konfirmasi &amp; Jadwalkan {{ count($schedulePlan) }} Sesi
                    @endif
                </button>
            </div>
        </form>
    @endif

</div>
@endsection
