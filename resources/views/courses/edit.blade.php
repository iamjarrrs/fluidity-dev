@extends('layouts.app')

@section('title', 'Edit Mata Kuliah - ' . $course->name)

@php
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200';
    $color = strtolower(old('color', $course->color ?? '#0f766e'));
@endphp

@section('content')
<div>

    <a href="{{ route('courses.show', $course->id) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
        <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Kembali ke {{ $course->name }}
    </a>

    <h1 class="mt-6 text-2xl sm:text-3xl font-medium text-slate-900">Edit mata kuliah</h1>
    <p class="mt-2 max-w-2xl text-slate-600">
        Perbarui tanggal ujian, bab, dan modul. Kalau judul bab atau modulnya berubah, Gemini menyusun ulang rangkuman dan pertanyaan Active Recall.
    </p>

    <form action="{{ route('courses.update', $course->id) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6"
          x-data="{ color: '{{ $color }}' }" @course-color="color = $event.detail">
        @csrf
        @method('PUT')

        <!-- Satu card: data mata kuliah di atas, bab dan modul di bawah (sama dengan halaman tambah) -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <section aria-labelledby="course-title">
            <h2 id="course-title" class="text-base font-medium text-slate-900">Mata kuliah</h2>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="name" class="mb-2 block text-sm text-slate-700">Nama mata kuliah</label>
                    <input id="name" type="text" name="name" required value="{{ old('name', $course->name) }}" class="{{ $field }}">
                </div>

                <div>
                    <label for="code" class="mb-2 block text-sm text-slate-700">Kode <span class="text-slate-500">(opsional)</span></label>
                    <input id="code" type="text" name="code" placeholder="MA1201" value="{{ old('code', $course->code) }}" class="{{ $field }}">
                </div>

                <div>
                    <label for="exam_date" class="mb-2 block text-sm text-slate-700">Tanggal ujian (UTS atau UAS)</label>
                    <input id="exam_date" type="date" name="exam_date" required
                           value="{{ old('exam_date', \Carbon\Carbon::parse($course->exam_date)->format('Y-m-d')) }}" class="{{ $field }}">
                    <p class="mt-2 text-xs text-slate-500">Jadwal belajar makin rapat saat tanggal ini makin dekat</p>
                </div>

                <div>
                    <p class="mb-2 text-sm text-slate-700">Warna di jadwal dan kalender</p>
                    @include('partials.color-picker', ['selectedColor' => $color])
                </div>
            </div>
        </section>

        <!-- Bab & modul -->
        <section class="mt-8 border-t border-slate-200 pt-8" aria-labelledby="topics-title">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 id="topics-title" class="text-base font-medium text-slate-900">Bab dan modul materi</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $course->topics->count() }} bab tersimpan</p>
                </div>
                <button type="button" id="add-topic-btn" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">
                    <svg class="size-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                    Tambah bab
                </button>
            </div>

            <div id="topics-container" class="mt-6 space-y-8">
                @foreach($course->topics->values() as $index => $topic)
                    @include('courses.topic-row', ['i' => $index, 'topic' => $topic])
                @endforeach
            </div>
            <template id="topic-template">@include('courses.topic-row', ['i' => '__I__', 'topic' => null])</template>
        </section>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('courses.show', $course->id) }}" class="inline-flex h-11 items-center rounded-full border border-slate-200 bg-white px-5 text-sm font-medium text-slate-900 hover:bg-slate-100 transition-colors">Batal</a>
            <button type="submit" class="h-11 rounded-full bg-primary-600 px-6 text-sm font-medium text-white hover:bg-primary-700 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                Simpan perubahan
            </button>
        </div>
    </form>
</div>

@include('courses.topic-script', ['nextIndex' => $course->topics->count()])
@endsection
