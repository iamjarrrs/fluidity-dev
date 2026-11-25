@extends('layouts.app')

@section('title', 'Tambah Mata Kuliah & Modul - Fluidity')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header & Back Button -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition flex items-center gap-1 mb-2">
                &larr; Kembali ke Dashboard
            </a>
            <h1 class="text-2xl font-bold text-slate-900">Tambah Mata Kuliah & Unggah Modul</h1>
            <p class="text-xs text-slate-500 mt-1">
                Masukkan mata kuliah, tanggal ujian, serta bab materi yang ingin dijadwalkan secara adaptif oleh Fluidity.
            </p>
        </div>
    </div>

    <!-- Form Container -->
    <form action="{{ route('courses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- Section 1: Informasi Mata Kuliah -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
            <h2 class="text-base font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span>📚</span> 1. Informasi Mata Kuliah
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Nama Mata Kuliah <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Contoh: Kalkulus II, Fisika Dasar, Algoritma"
                           value="{{ old('name') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Kode Mata Kuliah <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="text" name="code" placeholder="Contoh: IF2110, MA1201"
                           value="{{ old('code') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Tanggal Ujian (UTS / UAS) <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="exam_date" required min="{{ date('Y-m-d') }}"
                           value="{{ old('exam_date') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-[11px] text-slate-500 mt-1">
                        Krusial: Algoritma SM-2 memadatkan jadwal secara dinamis berdasarkan sisa hari menuju tanggal ini.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Warna Penanda di Kalender
                    </label>
                    @include('partials.color-picker', ['selectedColor' => old('color', '#3b82f6')])
                </div>
            </div>
        </div>

        <!-- Section 2: Daftar Bab & Upload Modul Dosen -->
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <span>📄</span> 2. Daftar Bab & Unggah Modul Materi
                </h2>
                <button type="button" id="add-topic-btn" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                    + Tambah Bab Lain
                </button>
            </div>

            <p class="text-xs text-slate-500">
                Tandai bobot kesulitan untuk setiap bab. Gemini AI akan mengekstrak rangkuman dan menyiapkan pertanyaan untuk sesi Active Recall nanti.
            </p>

            <div id="topics-container" class="space-y-4">
                <!-- Topic Card 0 -->
                <div class="topic-item p-5 rounded-2xl border border-slate-200 bg-slate-50/60 relative space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 topic-number">Bab 1</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Judul / Nama Bab <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="topics[0][title]" required placeholder="Contoh: Turunan Parsial & Gradien"
                                   class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Bobot Kesulitan Materi
                            </label>
                            <select name="topics[0][difficulty_weight]" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="1">1 - Sangat Mudah (Cepat paham)</option>
                                <option value="2">2 - Mudah</option>
                                <option value="3" selected>3 - Sedang (Rata-rata)</option>
                                <option value="4">4 - Berat (Banyak konsep)</option>
                                <option value="5">5 - Sangat Berat (Perlu prioritas tinggi)</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Unggah File Modul / Slide Dosen <span class="text-blue-600 font-semibold">(Bisa pilih lebih dari 1 file • PDF, DOCX, TXT - Maks. 5MB/file)</span>
                            </label>
                            <input type="file" name="topics[0][module_files][]" multiple accept=".pdf,.doc,.docx,.txt"
                                   class="module-file-input block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                            <p class="selected-files-list text-[11px] text-slate-500 mt-1 hidden"></p>
                            <p class="file-size-error text-xs text-rose-600 font-semibold mt-1 hidden"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4 pt-2">
            <a href="{{ route('dashboard') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-800 transition">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-md shadow-blue-200 transition">
                Simpan & Proses Materi Cerdas
            </button>
        </div>

    </form>

</div>

<!-- Vanilla JS for Dynamic Topic Rows -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('topics-container');
        const addBtn = document.getElementById('add-topic-btn');
        let topicCount = 1;

        addBtn.addEventListener('click', function () {
            const index = topicCount;
            const newCard = document.createElement('div');
            newCard.className = 'topic-item p-5 rounded-2xl border border-slate-200 bg-slate-50/60 relative space-y-4';
            newCard.innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 topic-number">Bab ${index + 1}</span>
                    <button type="button" class="remove-topic-btn text-xs text-rose-500 hover:text-rose-700 font-semibold">
                        Hapus Bab
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Judul / Nama Bab <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="topics[${index}][title]" required placeholder="Contoh: Bab berikutnya"
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

        // Validasi ukuran tiap file real-time (maks. 5MB/file) untuk multi-file
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

        // Pasang pada Bab 1 pertama
        document.querySelectorAll('.module-file-input').forEach(attachValidation);
    });
</script>
@endsection
