{{-- Satu baris bab. $i = indeks array form ('__I__' untuk template yang diklon JS); $topic opsional untuk halaman edit. --}}
@php
    $topic = $topic ?? null;
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-200';
    $weight = (int) old("topics.{$i}.difficulty_weight", $topic->difficulty_weight ?? 3);
    $existingFiles = $topic ? $topic->allModuleFiles() : [];
@endphp
<div class="topic-item border-l-2 pl-4" :style="'border-color: ' + color">
    @if($topic)
        <input type="hidden" name="topics[{{ $i }}][id]" value="{{ $topic->id }}">
    @endif

    <div class="flex items-center justify-between gap-3">
        <p class="topic-number text-sm font-medium text-slate-900">Bab {{ is_int($i) ? $i + 1 : '' }}</p>
        @if($i !== 0)
            <button type="button" class="remove-topic-btn text-xs text-slate-500 hover:text-red-700 transition-colors">Hapus bab</button>
        @endif
    </div>

    <div class="mt-3 grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_14rem] gap-4">
        <div>
            <label for="topic-{{ $i }}-title" class="mb-2 block text-sm text-slate-700">Judul bab</label>
            <input id="topic-{{ $i }}-title" type="text" name="topics[{{ $i }}][title]" required placeholder="Turunan parsial dan gradien"
                   value="{{ old("topics.{$i}.title", $topic->title ?? '') }}" class="{{ $field }}">
        </div>
        <div>
            <label for="topic-{{ $i }}-weight" class="mb-2 block text-sm text-slate-700">Bobot kesulitan</label>
            <select id="topic-{{ $i }}-weight" name="topics[{{ $i }}][difficulty_weight]" class="{{ $field }}">
                @foreach([1 => 'Sangat mudah', 2 => 'Mudah', 3 => 'Sedang', 4 => 'Berat, banyak konsep', 5 => 'Sangat berat'] as $value => $name)
                    <option value="{{ $value }}" @selected($weight === $value)>{{ $value }} · {{ $name }}</option>
                @endforeach
            </select>
        </div>

        @if(! empty($existingFiles))
            <fieldset class="md:col-span-2">
                <legend class="mb-2 text-sm text-slate-700">Modul tersimpan <span class="text-slate-500">(centang untuk menghapus)</span></legend>
                <ul class="divide-y divide-slate-200 rounded-lg border border-slate-200">
                    @foreach($existingFiles as $ef)
                        <li>
                            <label class="flex cursor-pointer items-center justify-between gap-3 px-3 py-2 text-sm hover:bg-slate-50 transition-colors">
                                <span class="truncate text-slate-900">{{ $ef['name'] }}</span>
                                <span class="flex shrink-0 items-center gap-2 text-xs text-slate-500">
                                    <input type="checkbox" name="topics[{{ $i }}][remove_files][]" value="{{ $ef['path'] }}" class="rounded border-slate-300 text-red-700 focus:ring-red-200">
                                    Hapus
                                </span>
                            </label>
                        </li>
                    @endforeach
                </ul>
            </fieldset>
        @endif

        <div class="md:col-span-2">
            <label for="topic-{{ $i }}-files" class="mb-2 block text-sm text-slate-700">
                {{ $topic ? 'Tambah modul baru' : 'Modul atau slide dosen' }} <span class="text-slate-500">(opsional)</span>
            </label>
            <input id="topic-{{ $i }}-files" type="file" name="topics[{{ $i }}][module_files][]" multiple accept=".pdf,.doc,.docx,.txt"
                   class="module-file-input block w-full cursor-pointer text-sm text-slate-500 file:mr-3 file:cursor-pointer file:rounded-full file:border file:border-slate-200 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-900 hover:file:bg-slate-100">
            <p class="mt-2 text-xs text-slate-500">PDF, DOCX, atau TXT, maks. 5 MB per file. Bisa pilih lebih dari satu</p>
            <p class="selected-files-list mt-1 hidden text-xs text-slate-600"></p>
            <p class="file-size-error mt-1 hidden text-xs text-red-700" role="alert"></p>
        </div>
    </div>
</div>
