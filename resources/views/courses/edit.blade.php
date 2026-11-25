@extends('layouts.app')

@section('title', 'Edit Mata Kuliah - ' . $course->name)

@section('content')
<div class="max-w-3xl mx-auto">

    <!-- Header & Back Link -->
    <div class="mb-6">
        <a href="{{ route('courses.show', $course->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1 mb-2">
            &larr; Kembali ke Detail Mata Kuliah
        </a>
        <h1 class="text-2xl font-bold text-slate-900">✏️ Edit Mata Kuliah &amp; Modul</h1>
        <p class="text-xs text-slate-500 mt-1">
            Perbarui informasi mata kuliah, edit atau tambah bab, serta tambahkan atau hapus file modul materi.
        </p>
    </div>

    <form action="{{ route('courses.update', $course->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Card 1: Informasi Mata Kuliah -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span>🎓</span> Detail Mata Kuliah
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Nama Mata Kuliah <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $course->name) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="code" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Kode Matkul <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code', $course->code) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label for="exam_date" class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Tanggal Ujian (UTS / UAS) <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="exam_date" id="exam_date"
                           value="{{ old('exam_date', \Carbon\Carbon::parse($course->exam_date)->format('Y-m-d')) }}" required
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                        Warna Tag Kalender
                    </label>
                    @include('partials.color-picker', ['selectedColor' => old('color', $course->color ?? '#3b82f6')])
                </div>
            </div>
        </div>

        <!-- Card 2: Daftar Bab / Topik Materi -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span>📖</span> Daftar Bab &amp; Materi Modul
                </h2>
                <button type="button" id="add-topic-btn" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                    + Tambah Bab Lain
                </button>
            </div>

            <p class="text-xs text-slate-500">
                Jika Anda mengunggah file modul baru atau mengubah judul bab, rangkuman dan soal Active Recall akan otomatis diperbarui oleh AI.
            </p>

            <div id="topics-container" class="space-y-4">
                @foreach($course->topics as $index => $topic)
                    <div class="topic-item p-5 rounded-2xl border border-slate-200 bg-slate-50/60 relative space-y-4">
                        <input type="hidden" name="topics[{{ $index }}][id]" value="{{ $topic->id }}">

                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 topic-number">Bab {{ $index + 1 }}</span>
                            @if($index > 0)
                                <button type="button" class="remove-topic-btn text-xs text-rose-500 hover:text-rose-700 font-semibold">
                                    Hapus Bab
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Judul / Nama Bab <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="topics[{{ $index }}][title]" value="{{ old("topics.{$index}.title", $topic->title) }}" required
                                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Bobot Kesulitan Materi
                                </label>
                                <select name="topics[{{ $index }}][difficulty_weight]" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="1" {{ $topic->difficulty_weight == 1 ? 'selected' : '' }}>1 - Sangat Mudah (Cepat paham)</option>
                                    <option value="2" {{ $topic->difficulty_weight == 2 ? 'selected' : '' }}>2 - Mudah</option>
                                    <option value="3" {{ $topic->difficulty_weight == 3 ? 'selected' : '' }}>3 - Sedang (Rata-rata)</option>
                                    <option value="4" {{ $topic->difficulty_weight == 4 ? 'selected' : '' }}>4 - Berat (Banyak konsep)</option>
                                    <option value="5" {{ $topic->difficulty_weight == 5 ? 'selected' : '' }}>5 - Sangat Berat (Perlu prioritas tinggi)</option>
                                </select>
                            </div>

                            @php $existingFiles = $topic->allModuleFiles(); @endphp
                            @if(! empty($existingFiles))
                                <div class="md:col-span-2 bg-white rounded-xl p-3.5 border border-slate-200 space-y-2">
                                    <div class="text-xs font-semibold text-slate-700">📎 File Modul Tersimpan (centang untuk menghapus):</div>
                                    <div class="space-y-1.5">
                                        @foreach($existingFiles as $ef)
                                            <label class="flex items-center justify-between gap-2 text-xs text-slate-600 p-1.5 rounded-lg hover:bg-rose-50/50 cursor-pointer">
                                                <span class="truncate">{{ $ef['name'] }}</span>
                                                <span class="flex items-center gap-1.5 text-rose-600 font-medium flex-shrink-0">
                                                    <input type="checkbox" name="topics[{{ $index }}][remove_files][]" value="{{ $ef['path'] }}" class="rounded border-slate-300 text-rose-600">
                                                    Hapus
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                    Tambah File Modul Baru <span class="text-blue-600 font-semibold">(Bisa pilih lebih dari 1 file • PDF, DOCX, TXT - Maks. 5MB/file)</span>
                                </label>
                                <input type="file" name="topics[{{ $index }}][module_files][]" multiple accept=".pdf,.doc,.docx,.txt"
                                       class="module-file-input block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                <p class="selected-files-list text-[11px] text-slate-500 mt-1 hidden"></p>
                                <p class="file-size-error text-xs text-rose-600 font-semibold mt-1 hidden"></p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4 pt-2">
            <a href="{{ route('courses.show', $course->id) }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-200 transition">
                💾 Simpan Perubahan
            </button>
        </div>

    </form>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('topics-container');
        const addBtn = document.getElementById('add-topic-btn');
        let topicCount = {{ $course->topics->count() }};

        document.querySelectorAll('.remove-topic-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                this.closest('.topic-item').remove();
            });
        });

        addBtn.addEventListener('click', function () {
            const index = topicCount;
            const newCard = document.createElement('div');
            newCard.className = 'topic-item p-5 rounded-2xl border border-slate-200 bg-slate-50/60 relative space-y-4';
            newCard.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 topic-number">Bab Baru</span>
                    <button type="button" class="remove-topic-btn text-xs text-rose-500 hover:text-rose-700 font-semibold">
                        Hapus Bab
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Judul / Nama Bab <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="topics[${index}][title]" required placeholder="Contoh: Bab baru"
                               class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Bobot Kesulitan Materi
                        </label>
                        <select name="topics[${index}][difficulty_weight]" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option value="1">1 - Sangat Mudah</option>
                            <option value="2">2 - Mudah</option>
                            <option value="3" selected>3 - Sedang</option>
                            <option value="4">4 - Berat</option>
                            <option value="5">5 - Sangat Berat</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Unggah File Modul / Slide Dosen <span class="text-blue-600 font-semibold">(Bisa pilih lebih dari 1 file • PDF, DOCX, TXT - Maks. 5MB/file)</span>
                        </label>
                        <input type="file" name="topics[${index}][module_files][]" multiple accept=".pdf,.doc,.docx,.txt"
                               class="module-file-input block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <p class="selected-files-list text-[11px] text-slate-500 mt-1 hidden"></p>
                        <p class="file-size-error text-xs text-rose-600 font-semibold mt-1 hidden"></p>
                    </div>
                </div>
            `;

            container.appendChild(newCard);
            attachValidation(newCard.querySelector('.module-file-input'));
            topicCount++;

            newCard.querySelector('.remove-topic-btn').addEventListener('click', function () {
                newCard.remove();
            });
        });

        function attachValidation(input) {
            if (!input) return;
            input.addEventListener('change', function () {
                const errorText = this.parentElement.querySelector('.file-size-error');
                const listText = this.parentElement.querySelector('.selected-files-list');
                const files = Array.from(this.files || []);
                const oversized = files.find(f => f.size / (1024 * 1024) > 5);

                if (oversized) {
                    const mb = (oversized.size / (1024 * 1024)).toFixed(2);
                    if (errorText) {
                        errorText.textContent = `File "${oversized.name}" terlalu besar (${mb} MB). Maksimal ukuran per file adalah 5 MB.`;
                        errorText.classList.remove('hidden');
                    }
                    if (listText) listText.classList.add('hidden');
                    this.value = '';
                } else {
                    if (errorText) errorText.classList.add('hidden');
                    if (listText) {
                        if (files.length > 0) {
                            listText.textContent = `${files.length} file dipilih: ` + files.map(f => f.name).join(', ');
                            listText.classList.remove('hidden');
                        } else {
                            listText.classList.add('hidden');
                        }
                    }
                }
            });
        }

        document.querySelectorAll('.module-file-input').forEach(attachValidation);
    });
</script>
@endsection
