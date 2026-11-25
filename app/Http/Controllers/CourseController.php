<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Topic;
use App\Services\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CourseController extends Controller
{
    /**
     * Tampilkan formulir tambah mata kuliah & modul
     */
    public function create(): View
    {
        return view('courses.create');
    }

    /**
     * Simpan mata kuliah, upload multi-modul, dan ekstrak ringkasan cerdas via Gemini AI
     */
    public function store(Request $request, GeminiService $geminiService): RedirectResponse
    {
        if ($errorResponse = $this->checkPhpUploadErrors()) {
            return $errorResponse;
        }

        $validated = $this->validateCourseRequest($request);
        $userId = Auth::id();

        DB::transaction(function () use ($validated, $userId, $request, $geminiService) {
            $course = Course::create([
                'user_id' => $userId,
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'exam_date' => $validated['exam_date'],
                'color' => $validated['color'] ?? '#3b82f6',
            ]);

            foreach ($validated['topics'] as $index => $topicData) {
                $uploadedFiles = $this->collectUploadedFiles($request, $index);
                $processed = $this->processModuleFiles($uploadedFiles, $topicData['title']);
                $aiResult = $geminiService->processModule(
                    $processed['snippet'],
                    $topicData['title'],
                    $processed['pdfFallbackPath']
                );

                Topic::create([
                    'course_id' => $course->id,
                    'title' => $topicData['title'],
                    'difficulty_weight' => (int) $topicData['difficulty_weight'],
                    'module_file_path' => $processed['files'][0]['path'] ?? null,
                    'module_files' => $processed['files'],
                    'ai_summary' => $aiResult['summary'] ?? null,
                    'ai_questions' => $aiResult['questions'] ?? [],
                    'mastery_level' => 1,
                    'ease_factor' => 2.50,
                    'repetition_count' => 0,
                    'interval_days' => 0,
                ]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Mata kuliah dan bab modul berhasil ditambahkan & diproses oleh AI!');
    }

    /**
     * Tampilkan detail mata kuliah dan topik di dalamnya
     */
    public function show(Course $course): View
    {
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke mata kuliah ini.');
        }

        $course->load('topics');

        return view('courses.show', [
            'course' => $course,
        ]);
    }

    /**
     * Tampilkan formulir edit mata kuliah, bab, dan modul
     */
    public function edit(Course $course): View
    {
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke mata kuliah ini.');
        }

        $course->load('topics');

        return view('courses.edit', [
            'course' => $course,
        ]);
    }

    /**
     * Perbarui mata kuliah, edit/tambah/hapus bab, serta tambah/hapus multi-file modul
     */
    public function update(Request $request, Course $course, GeminiService $geminiService): RedirectResponse
    {
        if ($course->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke mata kuliah ini.');
        }

        if ($errorResponse = $this->checkPhpUploadErrors()) {
            return $errorResponse;
        }

        $validated = $this->validateCourseRequest($request, isUpdate: true);

        DB::transaction(function () use ($validated, $course, $request, $geminiService) {
            $course->update([
                'name' => $validated['name'],
                'code' => $validated['code'] ?? null,
                'exam_date' => $validated['exam_date'],
                'color' => $validated['color'] ?? '#3b82f6',
            ]);

            $keptTopicIds = [];

            foreach ($validated['topics'] as $index => $topicData) {
                $topicId = $topicData['id'] ?? null;
                $existingTopic = $topicId
                    ? $course->topics()->where('id', $topicId)->first()
                    : null;

                $uploadedFiles = $this->collectUploadedFiles($request, $index);

                if ($existingTopic) {
                    $keptTopicIds[] = $existingTopic->id;

                    // Kelola file modul lama (hapus jika dicentang user)
                    $currentFiles = $existingTopic->allModuleFiles();
                    $removePaths = (array) ($topicData['remove_files'] ?? []);

                    if (! empty($removePaths)) {
                        foreach ($removePaths as $pathToRemove) {
                            Storage::disk('public')->delete($pathToRemove);
                        }
                        $currentFiles = array_values(array_filter(
                            $currentFiles,
                            fn ($f) => ! in_array($f['path'], $removePaths, true)
                        ));
                    }

                    // Proses file modul baru jika ada yang diunggah
                    $hasNewFiles = ! empty($uploadedFiles);
                    $processedNew = $this->processModuleFiles($uploadedFiles, $topicData['title']);
                    $mergedFiles = array_values(array_merge($currentFiles, $processedNew['files']));

                    $updates = [
                        'title' => $topicData['title'],
                        'difficulty_weight' => (int) $topicData['difficulty_weight'],
                        'module_file_path' => $mergedFiles[0]['path'] ?? null,
                        'module_files' => $mergedFiles,
                    ];

                    // Re-generate AI summary & questions hanya jika ada file baru atau judul berubah
                    $titleChanged = $existingTopic->title !== $topicData['title'];
                    if ($hasNewFiles || $titleChanged) {
                        $combinedSnippet = $hasNewFiles
                            ? $processedNew['snippet']
                            : $this->buildSnippetFromStoredFiles($mergedFiles, $topicData['title']);
                        $aiResult = $geminiService->processModule(
                            $combinedSnippet,
                            $topicData['title'],
                            $processedNew['pdfFallbackPath']
                        );
                        $updates['ai_summary'] = $aiResult['summary'] ?? $existingTopic->ai_summary;
                        $updates['ai_questions'] = $aiResult['questions'] ?? $existingTopic->ai_questions;
                    }

                    $existingTopic->update($updates);
                } else {
                    // Bab baru ditambahkan saat edit
                    $processed = $this->processModuleFiles($uploadedFiles, $topicData['title']);
                    $aiResult = $geminiService->processModule(
                        $processed['snippet'],
                        $topicData['title'],
                        $processed['pdfFallbackPath']
                    );

                    $newTopic = Topic::create([
                        'course_id' => $course->id,
                        'title' => $topicData['title'],
                        'difficulty_weight' => (int) $topicData['difficulty_weight'],
                        'module_file_path' => $processed['files'][0]['path'] ?? null,
                        'module_files' => $processed['files'],
                        'ai_summary' => $aiResult['summary'] ?? null,
                        'ai_questions' => $aiResult['questions'] ?? [],
                        'mastery_level' => 1,
                        'ease_factor' => 2.50,
                        'repetition_count' => 0,
                        'interval_days' => 0,
                    ]);

                    $keptTopicIds[] = $newTopic->id;
                }
            }

            // Hapus bab yang dihapus user dari form edit
            $topicsToDelete = $course->topics()->whereNotIn('id', $keptTopicIds)->get();
            foreach ($topicsToDelete as $oldTopic) {
                foreach ($oldTopic->allModuleFiles() as $f) {
                    Storage::disk('public')->delete($f['path']);
                }
                $oldTopic->delete();
            }
        });

        return redirect()->route('courses.show', $course->id)
            ->with('success', 'Perubahan mata kuliah & modul berhasil disimpan!');
    }

    /**
     * Validasi request store/update mata kuliah
     */
    private function validateCourseRequest(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'exam_date' => $isUpdate ? ['required', 'date'] : ['required', 'date', 'after_or_equal:today'],
            'color' => ['nullable', 'string', 'max:7'],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*.id' => ['nullable', 'integer'],
            'topics.*.title' => ['required', 'string', 'max:255'],
            'topics.*.difficulty_weight' => ['required', 'integer', 'min:1', 'max:5'],
            'topics.*.module_files' => ['nullable', 'array'],
            'topics.*.module_files.*' => ['file', 'mimes:pdf,txt,doc,docx', 'max:5120'],
            'topics.*.remove_files' => ['nullable', 'array'],
            'topics.*.remove_files.*' => ['string'],
        ];

        return $request->validate($rules, [
            'topics.*.module_files.*.max' => 'Ukuran setiap file modul tidak boleh melebihi 5 MB.',
            'topics.*.module_files.*.mimes' => 'File modul harus berformat PDF, DOC, DOCX, atau TXT.',
            'exam_date.after_or_equal' => 'Tanggal ujian harus hari ini atau di masa mendatang.',
        ]);
    }

    /**
     * Deteksi spesifik jika PHP menolak file sebelum divalidasi Laravel
     */
    private function checkPhpUploadErrors(): ?RedirectResponse
    {
        if (! isset($_FILES['topics']['error'])) {
            return null;
        }

        foreach ($_FILES['topics']['error'] as $index => $fileInfo) {
            $errors = $fileInfo['module_files'] ?? [$fileInfo['module_file'] ?? UPLOAD_ERR_OK];
            if (! is_array($errors)) {
                $errors = [$errors];
            }

            foreach ($errors as $errorCode) {
                if ($errorCode === UPLOAD_ERR_INI_SIZE || $errorCode === UPLOAD_ERR_FORM_SIZE) {
                    $serverMax = ini_get('upload_max_filesize');
                    return back()->withInput()->withErrors([
                        "topics.{$index}.module_files" => "Salah satu file bab " . ($index + 1) . " ditolak oleh PHP karena melebihi batas server saat ini ({$serverMax}).",
                    ]);
                } elseif ($errorCode === UPLOAD_ERR_CANT_WRITE) {
                    return back()->withInput()->withErrors([
                        "topics.{$index}.module_files" => 'Server gagal menulis file ke disk (masalah izin folder temporary /tmp).',
                    ]);
                } elseif ($errorCode !== UPLOAD_ERR_OK && $errorCode !== UPLOAD_ERR_NO_FILE) {
                    return back()->withInput()->withErrors([
                        "topics.{$index}.module_files" => "Upload file bab " . ($index + 1) . " gagal dengan kode error PHP #{$errorCode}.",
                    ]);
                }
            }
        }

        return null;
    }

    /**
     * Kumpulkan array UploadedFile dari input multi-file (module_files[])
     *
     * @return array<int, UploadedFile>
     */
    private function collectUploadedFiles(Request $request, int $index): array
    {
        $files = $request->file("topics.{$index}.module_files", []);
        if ($files instanceof UploadedFile) {
            return [$files];
        }
        return is_array($files) ? array_values(array_filter($files)) : [];
    }

    /**
     * Simpan multi-file modul dan ekstrak teks gabungannya untuk prompt Gemini AI
     *
     * @param array<int, UploadedFile> $files
     * @return array{files: array<int, array{path: string, name: string}>, snippet: string, pdfFallbackPath: ?string}
     */
    private function processModuleFiles(array $files, string $topicTitle): array
    {
        $storedFiles = [];
        $textParts = [];
        $pdfFallbackPath = null;

        foreach ($files as $file) {
            $filePath = $file->store('modules', 'public');
            $originalName = $file->getClientOriginalName();
            $storedFiles[] = [
                'path' => $filePath,
                'name' => $originalName,
            ];

            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'txt') {
                $content = @file_get_contents($file->getRealPath());
                if ($content) {
                    $textParts[] = "=== {$originalName} ===\n" . mb_substr($content, 0, 2500);
                }
            } elseif ($ext === 'pdf') {
                $savedPdfPath = storage_path('app/public/' . $filePath);
                $extractedText = $this->extractPdfTextLocally($savedPdfPath);

                if (mb_strlen($extractedText) >= 100) {
                    $textParts[] = "=== {$originalName} ===\n" . mb_substr($extractedText, 0, 2500);
                } elseif (! $pdfFallbackPath) {
                    $pdfFallbackPath = $savedPdfPath;
                    $textParts[] = "Modul PDF terlampir: {$originalName}.";
                }
            } else {
                $textParts[] = "Modul: {$originalName} untuk materi {$topicTitle}.";
            }
        }

        $snippet = ! empty($textParts)
            ? mb_substr(implode("\n\n", $textParts), 0, 6000)
            : $topicTitle;

        return [
            'files' => $storedFiles,
            'snippet' => $snippet,
            'pdfFallbackPath' => $pdfFallbackPath,
        ];
    }

    /**
     * Bangun snippet teks dari file modul yang sudah tersimpan (jika judul diedit tanpa upload ulang)
     */
    private function buildSnippetFromStoredFiles(array $storedFiles, string $topicTitle): string
    {
        $textParts = [];
        foreach ($storedFiles as $fileInfo) {
            $fullPath = storage_path('app/public/' . $fileInfo['path']);
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if ($ext === 'txt' && file_exists($fullPath)) {
                $content = @file_get_contents($fullPath);
                if ($content) {
                    $textParts[] = "=== {$fileInfo['name']} ===\n" . mb_substr($content, 0, 2500);
                }
            } elseif ($ext === 'pdf' && file_exists($fullPath)) {
                $extracted = $this->extractPdfTextLocally($fullPath);
                if (mb_strlen($extracted) >= 100) {
                    $textParts[] = "=== {$fileInfo['name']} ===\n" . mb_substr($extracted, 0, 2500);
                }
            }
        }

        return ! empty($textParts)
            ? mb_substr(implode("\n\n", $textParts), 0, 6000)
            : $topicTitle;
    }

    /**
     * Ekstrak teks dari file PDF secara lokal memakai pdftotext agar tidak perlu upload PDF besar ke AI
     */
    private function extractPdfTextLocally(string $absolutePath): string
    {
        if (! file_exists($absolutePath)) {
            return '';
        }

        $command = 'pdftotext ' . escapeshellarg($absolutePath) . ' - 2>/dev/null';
        $output = @shell_exec($command);

        return is_string($output) ? trim($output) : '';
    }
}
