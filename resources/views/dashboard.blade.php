@extends('layouts.app')

@section('title', 'Dashboard Mahasiswa - Fluidity')

@php
    $todayLeft = $upcomingSessions->filter(fn ($s) => $s->scheduled_start->isToday() && $s->status !== 'completed')->count();
    $daysLeft = fn ($course) => (int) today()->diffInDays($course->exam_date, false);

    // Level penguasaan 1-5 -> [persen, label, warna bar, warna teks]
    $mastery = fn ($level) => match (true) {
        $level <= 2 => [$level * 20, 'Lemah', 'bg-red-500', 'text-red-700'],
        $level === 3 => [$level * 20, 'Sedang', 'bg-yellow-500', 'text-yellow-800'],
        default => [$level * 20, 'Kuat', 'bg-emerald-600', 'text-emerald-700'],
    };
@endphp

@section('content')
<div>
    <!-- Hari ini -->
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-medium text-slate-900">{{ now()->locale('id')->isoFormat('dddd, D MMMM') }}</h1>
            <p class="mt-2 text-slate-600">
                @if($todayLeft > 0)
                    Masih ada {{ $todayLeft }} sesi hari ini.
                @else
                    Tidak ada sesi tersisa hari ini.
                @endif
                @if($focusCourse)
                    Ujian terdekat: {{ $focusCourse->name }}, {{ $daysLeft($focusCourse) === 0 ? 'hari ini' : $daysLeft($focusCourse) . ' hari lagi' }}.
                @endif
            </p>
        </div>
        <a href="{{ route('courses.create') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
            <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            Tambah mata kuliah
        </a>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <!-- Kolom kiri: rencana belajar -->
        <div class="flex flex-col gap-6 min-w-0">

            <!-- Sesi berikutnya -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="next-title">
                @if($nextSession)
                    @php
                        $topic = $nextSession->topic;
                        [$pct, $label, $bar, $ink] = $mastery($topic->mastery_level);
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-500">Sesi berikutnya</p>
                        <span class="text-sm text-slate-500 tabular-nums">
                            {{ $nextSession->scheduled_start->locale('id')->isoFormat('ddd, D MMM') }} ·
                            {{ $nextSession->scheduled_start->format('H.i') }} – {{ $nextSession->scheduled_end->format('H.i') }} · {{ $nextSession->duration_minutes }} menit
                        </span>
                    </div>
                    <div class="mt-4 flex flex-wrap items-start gap-4">
                        <div class="grid h-16 w-16 shrink-0 place-items-center rounded-full border-4 border-primary-500">
                            <span class="text-sm font-medium text-primary-700 tabular-nums">{{ $nextSession->duration_minutes }}:00</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            @if($topic->repetition_count > 0)
                                <span class="inline-block rounded-md bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-800">Review ke-{{ $topic->repetition_count + 1 }}</span>
                            @else
                                <span class="inline-block rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700">Sesi baru</span>
                            @endif
                            <h2 id="next-title" class="mt-2 text-xl font-medium text-slate-900">{{ $topic->course->name ?? 'Matkul' }}: {{ $topic->title }}</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                Penguasaanmu level {{ $topic->mastery_level }}/5{{ $topic->course ? ($daysLeft($topic->course) >= 0 ? ' dan ujian ' . $daysLeft($topic->course) . ' hari lagi' : ', ujian sudah lewat') : '' }}
                            </p>
                            <div class="mt-3 flex flex-wrap gap-1.5 text-xs font-medium" aria-label="Alur sesi">
                                <span class="rounded-md bg-rose-50 px-2 py-0.5 text-rose-700">Pomodoro</span>
                                <span class="text-slate-400" aria-hidden="true">→</span>
                                <span class="rounded-md bg-violet-50 px-2 py-0.5 text-violet-700">Active Recall</span>
                                <span class="text-slate-400" aria-hidden="true">→</span>
                                <span class="rounded-md bg-amber-50 px-2 py-0.5 text-amber-800">Feynman</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5">
                        <div class="flex justify-between text-xs"><span class="text-slate-500">Penguasaan topik</span><span class="font-medium tabular-nums {{ $ink }}">{{ $pct }}% · {{ $label }}</span></div>
                        <div class="mt-1.5 h-2 rounded-full bg-slate-200"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('session.room', $nextSession->id) }}" class="rounded-full bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">Mulai sesi</a>
                        <a href="{{ route('schedule.preview') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-800 hover:bg-slate-100 transition-colors">Atur ulang jadwal</a>
                    </div>
                    {{-- Arti warna mode sesi: dipakai di alur sesi di atas dan label Review di jadwal --}}
                    <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-200 pt-4 text-xs text-slate-500">
                        <ul class="flex flex-wrap gap-x-4 gap-y-2" aria-label="Arti warna mode sesi">
                            <li class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-500" aria-hidden="true"></span>Pomodoro</li>
                            <li class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-violet-600" aria-hidden="true"></span>Active Recall</li>
                            <li class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>Feynman</li>
                            <li class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-sky-600" aria-hidden="true"></span>Review</li>
                        </ul>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Sesi berikutnya</p>
                    <h2 id="next-title" class="mt-2 text-xl font-medium text-slate-900">Belum ada sesi yang dijadwalkan</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if($courses->isNotEmpty())
                            Buat jadwal dan Fluidity akan memilih topik yang paling perlu kamu pelajari di jam luangmu.
                        @else
                            Tambahkan mata kuliah dan materi babnya, lalu Fluidity akan menyusun jadwal belajarmu.
                        @endif
                    </p>
                    <div class="mt-5">
                        @if($courses->isNotEmpty())
                            <a href="{{ route('schedule.preview') }}" class="inline-block rounded-full bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">Buat jadwal belajar</a>
                        @else
                            <a href="{{ route('courses.create') }}" class="inline-block rounded-full bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700 transition-colors">Tambah mata kuliah</a>
                        @endif
                    </div>
                @endif
            </section>

            <!-- Jadwal terdekat -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="schedule-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="schedule-title" class="text-base font-medium text-slate-900">Jadwal terdekat</h2>
                    @if($courses->isNotEmpty())
                        <a href="{{ route('schedule.preview') }}" class="text-sm font-medium text-primary-700 hover:underline">Buat jadwal</a>
                    @endif
                </div>

                @if($upcomingSessions->isEmpty())
                    <p class="mt-4 rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">Jadwal belajarmu akan muncul di sini, lengkap dengan sinkronisasi ke Google Calendar.</p>
                @else
                    <ol class="mt-4 flex flex-col">
                        @foreach($upcomingSessions as $session)
                            @php
                                $done = $session->status === 'completed';
                                $isNext = $nextSession && $nextSession->id === $session->id;
                            @endphp
                            <li class="flex gap-4 py-3 {{ $loop->last ? '' : 'border-b border-slate-200' }}">
                                <div class="w-14 shrink-0 tabular-nums">
                                    <p class="text-sm {{ $isNext ? 'font-medium text-slate-900' : 'text-slate-500' }}">{{ $session->scheduled_start->format('H.i') }}</p>
                                    <p class="text-xs text-slate-500">{{ $session->scheduled_start->isToday() ? 'Hari ini' : $session->scheduled_start->locale('id')->isoFormat('ddd D/M') }}</p>
                                </div>
                                <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $session->topic->course->color ?? '#0f766e' }}" aria-hidden="true"></span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm {{ $done ? 'line-through text-slate-500' : ($isNext ? 'font-medium text-slate-900' : 'text-slate-900') }}">
                                        {{ $session->topic->course->name ?? 'Matkul' }}: {{ $session->topic->title }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ $session->duration_minutes }} menit
                                        @if($session->google_calendar_event_id) · Ada di Google Calendar @endif
                                        @if($isNext) · berikutnya @endif
                                    </p>
                                </div>
                                @if($done)
                                    <a href="{{ route('session.result', $session->id) }}" class="self-start text-xs text-slate-500 hover:text-slate-900 hover:underline">Selesai · lihat hasil</a>
                                @elseif($session->topic->repetition_count > 0)
                                    <a href="{{ route('session.room', $session->id) }}" class="self-start text-xs font-medium text-sky-700 hover:underline">Review</a>
                                @else
                                    <a href="{{ route('session.room', $session->id) }}" class="self-start text-xs font-medium text-primary-700 hover:underline">Sesi baru</a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <!-- Kolom kanan: konteks -->
        <div class="flex flex-col gap-6 min-w-0">

            <!-- Ringkasan minggu ini -->
            <section aria-labelledby="week-title">
                <h2 id="week-title" class="text-sm text-slate-500">Minggu ini</h2>
                <div class="mt-2 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs text-slate-500">Perlu review</p>
                        {{-- Aksen amber hanya kalau memang ada yang perlu di-review --}}
                        <p class="mt-1 text-2xl font-medium tabular-nums {{ $reviewDue > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $reviewDue }} <span class="text-sm font-normal text-slate-500">topik</span></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs text-slate-500">Sesi selesai</p>
                        <p class="mt-1 text-2xl font-medium text-slate-900 tabular-nums">{{ $weekCompleted }}<span class="text-sm font-normal text-slate-500"> dari {{ $weekTotal }}</span></p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="text-xs text-slate-500">Waktu belajar</p>
                        <p class="mt-1 text-2xl font-medium text-slate-900 tabular-nums">{{ str_replace('.', ',', $weekHours) }} <span class="text-sm font-normal text-slate-500">jam</span></p>
                    </div>
                </div>
            </section>

            <!-- Mata kuliah & ujian -->
            <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="exam-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="exam-title" class="text-base font-medium text-slate-900">Ujian mendatang</h2>
                    <span class="font-mono text-xs text-slate-500 tabular-nums">{{ $courses->count() }} matkul</span>
                </div>

                @if($courses->isEmpty())
                    <p class="mt-3 text-sm text-slate-500">Belum ada mata kuliah. Tambahkan satu untuk mulai menyusun jadwal.</p>
                @else
                    <ul class="mt-3 flex flex-col gap-3">
                        @foreach($courses->sortBy('exam_date') as $course)
                            @php
                                $left = $daysLeft($course);
                                $isFocus = $focusCourse && $focusCourse->id === $course->id;
                                $weak = $course->topics->where('mastery_level', '<=', 2)->count();
                            @endphp
                            <li>
                                <a href="{{ route('courses.show', $course->id) }}" class="flex items-center gap-3 rounded-lg -mx-2 px-2 py-1 hover:bg-slate-50 transition-colors">
                                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-lg text-center leading-tight tabular-nums {{ $isFocus ? 'bg-primary-50' : 'bg-slate-100' }}">
                                        <span class="text-xs {{ $isFocus ? 'text-primary-700' : 'text-slate-500' }}">{{ $course->exam_date->locale('id')->isoFormat('MMM') }}</span>
                                        <span class="text-base font-medium {{ $isFocus ? 'text-primary-700' : 'text-slate-900' }}">{{ $course->exam_date->format('j') }}</span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="flex items-center gap-1.5 text-sm font-medium text-slate-900">
                                            <span class="h-2 w-2 shrink-0 rounded-full" style="background-color: {{ $course->color ?? '#0f766e' }}" aria-hidden="true"></span>
                                            <span class="truncate">{{ $course->name }}</span>
                                        </p>
                                        <p class="text-xs text-slate-500">{{ $course->topics->count() }} topik{{ $weak ? ", {$weak} masih lemah" : '' }}</p>
                                    </div>
                                    @if($left < 0)
                                        <span class="text-sm text-slate-500">Sudah lewat</span>
                                    @elseif($left === 0)
                                        <span class="text-sm font-medium text-red-700">Hari ini</span>
                                    @else
                                        <span class="text-sm tabular-nums {{ $left <= 7 ? 'font-medium text-red-700' : 'text-slate-600' }}">{{ $left }} hari</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <!-- Penguasaan topik untuk ujian terdekat -->
            @if($focusCourse && $focusCourse->topics->isNotEmpty())
                @php $nextReview = $focusCourse->topics->whereNotNull('next_review_due')->sortBy('next_review_due')->first(); @endphp
                <section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6" aria-labelledby="mastery-title">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="mastery-title" class="text-base font-medium text-slate-900">Penguasaan {{ $focusCourse->name }}</h2>
                        <a href="{{ route('courses.show', $focusCourse->id) }}" class="text-sm font-medium text-primary-700 hover:underline whitespace-nowrap">Semua topik</a>
                    </div>
                    <ul class="mt-4 flex flex-col gap-4">
                        @foreach($focusCourse->topics->sortBy('mastery_level')->take(5) as $t)
                            @php [$pct, $label, $bar, $ink] = $mastery($t->mastery_level); @endphp
                            <li>
                                <div class="flex justify-between gap-3 text-sm"><span class="text-slate-800">{{ $t->title }}</span><span class="font-medium tabular-nums whitespace-nowrap {{ $ink }}">{{ $pct }}% · {{ $label }}</span></div>
                                <div class="mt-1.5 h-2 rounded-full bg-slate-200"><div class="h-2 rounded-full {{ $bar }}" style="width: {{ $pct }}%"></div></div>
                            </li>
                        @endforeach
                    </ul>
                    @if($nextReview)
                        <p class="mt-4 border-t border-slate-200 pt-4 text-xs text-slate-500">
                            Review berikutnya untuk {{ $nextReview->title }} dijadwalkan {{ $nextReview->next_review_due->locale('id')->isoFormat('dddd, D MMMM') }}
                        </p>
                    @endif
                </section>
            @endif

            <!-- Preferensi jam luang -->
            <section x-data="{ editingPref: false }" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 text-xs text-slate-600 space-y-4" aria-label="Jam luang belajar">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm">
                            <span class="font-medium text-slate-800">Jam luang belajar:</span>
                            <span class="font-medium text-slate-900 tabular-nums">{{ substr($user->study_window_start ?? '08:00', 0, 5) }} – {{ substr($user->study_window_end ?? '22:00', 0, 5) }} WIB</span>
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">Maksimal {{ $user->max_daily_sessions ?? 2 }} sesi per hari supaya tidak burnout</p>
                    </div>
                    <button type="button" @click="editingPref = !editingPref"
                        class="px-3 py-1.5 rounded-full border border-slate-200 hover:bg-slate-100 text-slate-800 font-medium transition-colors">
                        <span x-text="editingPref ? 'Tutup' : 'Atur jam luang'"></span>
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
                    class="pt-4 border-t border-slate-100 grid grid-cols-2 gap-3 items-end">
                    @csrf
                    <input type="hidden" name="study_window_start" :value="startH + ':' + startM">
                    <input type="hidden" name="study_window_end" :value="endH + ':' + endM">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Mulai (24 jam)</label>
                        <div class="flex items-center gap-1">
                            <select x-model="startH" aria-label="Jam mulai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                @for($h = 0; $h < 24; $h++)
                                    @php $hh = sprintf('%02d', $h); @endphp
                                    <option value="{{ $hh }}">{{ $hh }}</option>
                                @endfor
                            </select>
                            <span class="font-medium text-slate-500">:</span>
                            <select x-model="startM" aria-label="Menit mulai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                @for($m = 0; $m < 60; $m++)
                                    @php $mm = sprintf('%02d', $m); @endphp
                                    <option value="{{ $mm }}">{{ $mm }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Selesai (24 jam)</label>
                        <div class="flex items-center gap-1">
                            <select x-model="endH" aria-label="Jam selesai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                @for($h = 0; $h < 24; $h++)
                                    @php $hh = sprintf('%02d', $h); @endphp
                                    <option value="{{ $hh }}">{{ $hh }}</option>
                                @endfor
                            </select>
                            <span class="font-medium text-slate-500">:</span>
                            <select x-model="endM" aria-label="Menit selesai" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                @for($m = 0; $m < 60; $m++)
                                    @php $mm = sprintf('%02d', $m); @endphp
                                    <option value="{{ $mm }}">{{ $mm }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="max_daily_sessions" class="block text-xs font-medium text-slate-600 mb-1">Maks. sesi per hari</label>
                        <select id="max_daily_sessions" name="max_daily_sessions" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-800 focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            @for($s = 1; $s <= 4; $s++)
                                <option value="{{ $s }}" {{ ($user->max_daily_sessions ?? 2) == $s ? 'selected' : '' }}>{{ $s }} sesi / hari</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-full transition-colors">
                            Simpan jam luang
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection
