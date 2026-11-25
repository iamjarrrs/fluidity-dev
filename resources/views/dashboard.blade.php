@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa - Fluidity')

@section('content')
<div class="space-y-6">

    <!-- Top Greeting & Calendar Status Card -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Halo, {{ $user->name }}! </h1>
            <p class="text-slate-500 text-sm mt-1">
                Selamat datang di ruang belajar mandiri Fluidity. Jadwal belajar Anda diatur adaptif agar siap menjelang ujian.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($calendarConnected)
                <div class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Google Calendar Terhubung
                </div>
            @else
                <a href="{{ route('auth.google') }}" class="flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-50 border border-amber-200 text-amber-700 text-xs font-semibold hover:bg-amber-100 transition">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Hubungkan Google Calendar
                </a>
            @endif
        </div>
    </div>

    <!-- Main Grid: Upcoming Sessions & Courses Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column (2 Cols): Jadwal Belajar Mendatang -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span>📅</span> Jadwal Belajar Terdekat
                    </h2>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-500 font-medium hidden sm:inline">Maks. 2 sesi / hari</span>
                        @if($courses->isNotEmpty())
                            <a href="{{ route('schedule.preview') }}" class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 text-xs font-semibold rounded-xl transition">
                                ✨ Generate Jadwal
                            </a>
                        @endif
                    </div>
                </div>

                @if($upcomingSessions->isEmpty())
                    <div class="text-center py-10 px-4 border-2 border-dashed border-slate-200 rounded-xl">
                        <div class="text-3xl mb-2">📖</div>
                        <h3 class="text-sm font-semibold text-slate-700">Belum ada sesi belajar yang dijadwalkan</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto mb-4">
                            Tambahkan mata kuliah dan upload materi bab Anda untuk membiarkan algoritma Fluidity menjadwalkan waktu belajar terbaik.
                        </p>
                        @if($courses->isNotEmpty())
                            <a href="{{ route('schedule.preview') }}" class="inline-block px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                ✨ Generate Jadwal Belajar Sekarang
                            </a>
                        @endif
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($upcomingSessions as $session)
                            <div class="py-3.5 flex items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @php $courseColor = $session->topic->course->color ?? '#3b82f6'; @endphp
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                              style="background-color: {{ $courseColor }}18; color: {{ $courseColor }}; border: 1px solid {{ $courseColor }}35;">
                                            {{ $session->topic->course->name ?? 'Matkul' }}
                                        </span>
                                        @if($session->google_calendar_event_id)
                                            <span class="text-[11px] font-medium px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                📆 Google Calendar
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $session->topic->title }}</h4>
                                    <p class="text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($session->scheduled_start)->isoFormat('dddd, D MMMM Y • HH:mm') }} WIB ({{ $session->duration_minutes }} menit)
                                    </p>
                                </div>
                                <div>
                                    @if($session->status === 'completed')
                                        <a href="{{ route('session.result', $session->id) }}" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-xl transition">
                                            ✅ Lihat Hasil
                                        </a>
                                    @else
                                        <a href="{{ route('session.room', $session->id) }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition">
                                            Mulai Belajar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Preferensi Belajar Card (Dengan Pengaturan Jam Luang) -->
            <div x-data="{ editingPref: false }" class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm text-xs text-slate-600 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="text-lg">⚙️</span>
                        <div>
                            <div>
                                <span class="font-semibold text-slate-800">Jam Luang Belajar Anda:</span>
                                <span class="font-bold text-blue-700">{{ substr($user->study_window_start ?? '08:00', 0, 5) }} - {{ substr($user->study_window_end ?? '22:00', 0, 5) }} WIB</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Target Harian: Maksimal {{ $user->max_daily_sessions ?? 2 }} sesi / hari (anti-burnout)
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="editingPref = !editingPref"
                        class="px-3.5 py-1.5 rounded-xl border border-slate-300 hover:border-blue-400 text-slate-700 hover:text-blue-700 font-semibold transition self-start sm:self-auto">
                        <span x-text="editingPref ? 'Tutup' : '✏️ Atur Jam Luang'"></span>
                    </button>
                </div>

                @php
                    $currentStart = substr($user->study_window_start ?? '08:00', 0, 5);
                    $currentEnd = substr($user->study_window_end ?? '22:00', 0, 5);
                    [$startH, $startM] = explode(':', $currentStart);
                    [$endH, $endM] = explode(':', $currentEnd);
                @endphp
                <form x-show="editingPref" x-transition method="POST" action="{{ route('preferences.update') }}"
                    x-data="{ startH: '{{ $startH }}', startM: '{{ $startM }}', endH: '{{ $endH }}', endM: '{{ $endM }}' }"
                    class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <input type="hidden" name="study_window_start" :value="startH + ':' + startM">
                    <input type="hidden" name="study_window_end" :value="endH + ':' + endM">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Mulai Luang (24 Jam)</label>
                        <div class="flex items-center gap-1">
                            <select x-model="startH" class="w-full rounded-xl border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                @for($h = 0; $h < 24; $h++)
                                    @php $hh = sprintf('%02d', $h); @endphp
                                    <option value="{{ $hh }}">{{ $hh }}</option>
                                @endfor
                            </select>
                            <span class="font-bold text-slate-500">:</span>
                            <select x-model="startM" class="w-full rounded-xl border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
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
                            <select x-model="endH" class="w-full rounded-xl border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                @for($h = 0; $h < 24; $h++)
                                    @php $hh = sprintf('%02d', $h); @endphp
                                    <option value="{{ $hh }}">{{ $hh }}</option>
                                @endfor
                            </select>
                            <span class="font-bold text-slate-500">:</span>
                            <select x-model="endM" class="w-full rounded-xl border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                @for($m = 0; $m < 60; $m++)
                                    @php $mm = sprintf('%02d', $m); @endphp
                                    <option value="{{ $mm }}">{{ $mm }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Maks. Sesi / Hari</label>
                        <select name="max_daily_sessions" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                            @for($s = 1; $s <= 4; $s++)
                                <option value="{{ $s }}" {{ ($user->max_daily_sessions ?? 2) == $s ? 'selected' : '' }}>{{ $s }} sesi / hari</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-sm transition">
                            💾 Simpan Jam Luang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column (1 Col): Mata Kuliah & Modul -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span>📚</span> Mata Kuliah
                    </h2>
                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-bold">
                        {{ $courses->count() }}
                    </span>
                </div>

                @if($courses->isEmpty())
                    <div class="text-center py-8 px-4 border-2 border-dashed border-slate-200 rounded-xl">
                        <p class="text-xs text-slate-500 mb-4">Belum ada mata kuliah yang didaftarkan.</p>
                        <a href="{{ route('courses.create') }}" class="inline-block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                            + Tambah Mata Kuliah & Modul
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($courses as $course)
                            @php $cColor = $course->color ?? '#3b82f6'; @endphp
                            <div class="p-3.5 rounded-xl border transition shadow-sm hover:shadow"
                                 style="background-color: {{ $cColor }}14; border-color: {{ $cColor }}40; border-left: 4px solid {{ $cColor }};">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-slate-800">{{ $course->name }}</h4>
                                    <span class="text-[11px] font-semibold text-rose-600 bg-white/80 px-2 py-0.5 rounded border border-rose-200/70 flex-shrink-0">
                                        Ujian: {{ \Carbon\Carbon::parse($course->exam_date)->format('d M Y') }}
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-slate-600 flex items-center justify-between">
                                    <span>{{ $course->topics->count() }} Topik Materi</span>
                                    <a href="{{ route('courses.show', $course->id) }}" class="font-semibold hover:underline" style="color: {{ $cColor }};">Detail &amp; Modul &rarr;</a>
                                </div>
                            </div>
                        @endforeach

                        <a href="{{ route('courses.create') }}" class="block text-center w-full mt-2 px-4 py-2.5 border border-dashed border-slate-300 hover:border-blue-400 text-slate-600 hover:text-blue-600 text-xs font-semibold rounded-xl transition">
                            + Tambah Mata Kuliah Lain
                        </a>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
