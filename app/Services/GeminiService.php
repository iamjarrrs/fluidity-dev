<?php

namespace App\Services;

use App\Models\Topic;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan Integrasi Google Gemini AI.
 * Bertanggung jawab merangkum modul kuliah, membuat bank pertanyaan Active Recall,
 * serta mengevaluasi penjelasan Feynman mahasiswa dengan umpan balik suportif.
 */
class GeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) config('services.gemini.key', env('GEMINI_API_KEY', ''));
        $this->model = (string) config('services.gemini.model', env('GEMINI_MODEL', 'gemini-3.5-flash'));
    }

    /**
     * Ekstraksi & Rangkuman Materi Modul Dosen serta Pembuatan Pertanyaan Active Recall
     *
     * @return array{summary: string, questions: array<int, string>}
     */
    public function processModule(string $textOrSummary, string $topicTitle, ?string $pdfAbsolutePath = null): array
    {
        if (empty($this->apiKey)) {
            return $this->fallbackModuleProcessing($topicTitle);
        }

        $prompt = <<<EOT
Kamu adalah mentor akademik cerdas untuk mahasiswa Indonesia.
Topik materi: "{$topicTitle}".
Berikut adalah potongan/isi materi modul yang dipelajari:
"{$textOrSummary}"

Tugasmu:
1. Buat ringkasan konsep inti yang SUBSTANSIAL dan INFORMATIF (5-7 kalimat). Harus mencakup:
   - Definisi/pengertian utama dari topik ini
   - Prinsip atau mekanisme kunci bagaimana konsep ini bekerja
   - Contoh penerapan nyata atau relevansi praktisnya
   Jangan membuat ringkasan generik yang bisa dipakai untuk topik apa saja. Harus SPESIFIK untuk topik "{$topicTitle}" dan merujuk pada isi modul jika dilampirkan.

2. Buat 6 pertanyaan konseptual yang UNIK dan SPESIFIK untuk metode Active Recall (agar tiap sesi belajar mahasiswa mendapat variasi soal yang berbeda):
   - Pertanyaan yang menguji pemahaman intuisi konsep, BUKAN hafalan rumus mati
   - Pertanyaan harus bervariasi: 2 definisi/prinsip, 2 analisis/perbandingan, 2 penerapan/skenario
   - Gunakan kata-kata dan konteks materi topik "{$topicTitle}" secara spesifik.

KEMBALIKAN HANYA FORMAT JSON MURNI TANPA BACKTICK MARKDOWN:
{
  "summary": "Ringkasan konsep spesifik dan informatif...",
  "questions": [
    "Pertanyaan prinsip 1...",
    "Pertanyaan prinsip 2...",
    "Pertanyaan analisis 1...",
    "Pertanyaan analisis 2...",
    "Pertanyaan penerapan 1...",
    "Pertanyaan penerapan 2..."
  ]
}
EOT;

        $parts = [['text' => $prompt]];

        // Jika ada file PDF (<= 5MB), kirim langsung sebagai inlineData agar Gemini membaca isi modul
        if ($pdfAbsolutePath && file_exists($pdfAbsolutePath) && strtolower(pathinfo($pdfAbsolutePath, PATHINFO_EXTENSION)) === 'pdf') {
            $pdfBytes = @file_get_contents($pdfAbsolutePath);
            if ($pdfBytes !== false) {
                $parts[] = [
                    'inlineData' => [
                        'mimeType' => 'application/pdf',
                        'data' => base64_encode($pdfBytes),
                    ],
                ];
            }
        }

        $parsed = $this->requestGeminiJson($parts, 0.4);
        if (is_array($parsed) && isset($parsed['summary'], $parsed['questions'])) {
            return [
                'summary' => $parsed['summary'],
                'questions' => (array) $parsed['questions'],
            ];
        }

        return $this->fallbackModuleProcessing($topicTitle);
    }

    /**
     * Evaluasi Feynman: Menilai penjelasan konsep mahasiswa dengan pendekatan ramah & suportif
     *
     * @return array{feedback: string, score: int}
     */
    public function evaluateFeynman(Topic $topic, string $question, string $studentExplanation): array
    {
        if (empty($this->apiKey)) {
            return [
                'feedback' => 'Penjelasan konsep Anda sudah runtut dan mencakup ide dasar dengan baik. Pertahankan pemahaman ini untuk persiapan ujian!',
                'score' => 4,
            ];
        }

        $prompt = <<<EOT
Kamu adalah dosen dan mentor belajar yang sangat suportif, ramah, dan berfokus pada pemahaman konseptual (Teknik Feynman).
Topik: "{$topic->title}".
Pertanyaan Konsep: "{$question}".
Penjelasan Konsep Mahasiswa: "{$studentExplanation}".

Aturan Penilaian:
1. Berikan apresiasi kualitatif yang tulus. Jika ada bagian yang keliru atau kurang lengkap, luruskan secara lembut dan bersahabat (2-3 kalimat).
2. Berikan skor 1 sampai 5. JANGAN PELIT NILAI. Jika mahasiswa sudah memahami konsep dasarnya dengan bahasa sendiri, berikan skor minimal 4. Hanya berikan nilai <=2 jika mahasiswa benar-benar salah konsep atau asal menjawab.

KEMBALIKAN HANYA FORMAT JSON MURNI:
{
  "feedback": "Feedback kualitatif...",
  "score": 4
}
EOT;

        $parsed = $this->requestGeminiJson([['text' => $prompt]], 0.5);
        if (is_array($parsed) && isset($parsed['feedback'], $parsed['score'])) {
            return [
                'feedback' => $parsed['feedback'],
                'score' => max(1, min(5, (int) $parsed['score'])),
            ];
        }

        return $this->fallbackEvaluateFeynman($topic, $studentExplanation);
    }

    /**
     * Fallback lokal cerdas jika Gemini API sedang tidak tersedia/limit habis.
     * Tetap mengecek apakah mahasiswa menjawab jujur tidak tahu / asal-asalan.
     *
     * @return array{feedback: string, score: int}
     */
    private function fallbackEvaluateFeynman(Topic $topic, string $studentExplanation): array
    {
        $text = mb_strtolower(trim($studentExplanation));

        $unsurePhrases = [
            'tidak tahu', 'tidak tau', 'gak tahu', 'gak tau', 'ga tahu', 'ga tau',
            'belum baca', 'belum belajar', 'belum paham', 'lupa', 'ngasal', 'asal',
            'entah', 'kurang tahu', 'kurang tau', 'skip', 'bingung',
        ];

        foreach ($unsurePhrases as $phrase) {
            if (str_contains($text, $phrase)) {
                return [
                    'feedback' => "Tidak apa-apa kalau belum sempat membaca materi {$topic->title}! Coba luangkan waktu sebentar untuk membaca rangkuman konsep di atas, lalu jelaskan kembali inti idenya dengan bahasamu sendiri.",
                    'score' => 1,
                ];
            }
        }

        // Cek apakah teks terlalu pendek atau terlihat seperti ketikan acak tanpa spasi
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) < 5) {
            return [
                'feedback' => "Penjelasanmu masih terlalu singkat. Coba jabarkan lebih rinci apa yang kamu pahami tentang {$topic->title} beserta contoh sederhananya agar pemahamanmu makin kuat!",
                'score' => 2,
            ];
        }

        return [
            'feedback' => 'Penjelasan Anda menunjukkan pemahaman konsep dasar yang baik. Terus asah intuisi logika Anda dan kaitkan dengan contoh penerapannya!',
            'score' => 4,
        ];
    }

    /**
     * Kirim request ke Gemini API dengan fallback otomatis antar-model Flash jika terjadi 503/429/404.
     */
    private function requestGeminiJson(array $parts, float $temperature): ?array
    {
        $models = array_values(array_unique([
            $this->model,
            'gemini-3.5-flash',
            'gemini-3.7-flash',
            'gemini-3.6-flash',
            'gemini-2.5-flash-lite',
            'gemini-3.5-flash-lite',
            'gemini-flash-latest',
            'gemini-flash-lite-latest',
        ]));

        foreach ($models as $modelName) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$this->apiKey}";

            try {
                $response = Http::timeout(10)->post($endpoint, [
                    'contents' => [
                        ['parts' => $parts],
                    ],
                    'generationConfig' => [
                        'temperature' => $temperature,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $parsed = json_decode($rawText, true);

                    if (is_array($parsed)) {
                        return $parsed;
                    }
                } else {
                    Log::warning("Gemini API {$modelName} failed ({$response->status()}): " . $response->body());
                }
            } catch (Exception $e) {
                Log::warning("Gemini API {$modelName} exception: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Buat 3 pertanyaan Active Recall baru yang berbeda dari pertanyaan sebelumnya
     *
     * @return array<int, string>
     */
    public function generateFreshQuestions(Topic $topic, array $excludeQuestions = []): array
    {
        if (empty($this->apiKey)) {
            return array_slice($this->fallbackModuleProcessing($topic->title)['questions'], 0, 3);
        }

        $context = $topic->ai_summary ?: $topic->title;
        $excludeText = empty($excludeQuestions)
            ? 'Tidak ada.'
            : implode("\n- ", array_slice($excludeQuestions, -10));

        $prompt = <<<EOT
Kamu adalah mentor akademik cerdas untuk mahasiswa Indonesia.
Topik: "{$topic->title}".
Konteks materi: "{$context}"

Buat 3 pertanyaan Active Recall BARU yang menguji pemahaman konsep dengan sudut pandang berbeda (1 prinsip/intuisi, 1 perbandingan/analisis, 1 skenario penerapan).
JANGAN ulangi atau mirip dengan pertanyaan berikut:
- {$excludeText}

KEMBALIKAN HANYA FORMAT JSON MURNI:
{
  "questions": [
    "Pertanyaan baru 1...",
    "Pertanyaan baru 2...",
    "Pertanyaan baru 3..."
  ]
}
EOT;

        $parsed = $this->requestGeminiJson([['text' => $prompt]], 0.7);
        if (is_array($parsed) && ! empty($parsed['questions'])) {
            return array_values((array) $parsed['questions']);
        }

        return array_slice($this->fallbackModuleProcessing($topic->title)['questions'], 0, 3);
    }

    /**
     * Fallback lokal jika API key Gemini belum diisi atau offline
     */
    private function fallbackModuleProcessing(string $topicTitle): array
    {
        return [
            'summary' => "Topik {$topicTitle} mencakup pemahaman prinsip-prinsip fundamental yang menjadi landasan konseptual, termasuk definisi kunci, mekanisme kerja, serta hubungan antarkonsep di dalamnya. Mahasiswa diharapkan mampu menjelaskan logika dasar topik ini dengan bahasa sendiri dan menghubungkannya dengan penerapan di konteks nyata. Pemahaman yang kuat pada topik ini akan mempermudah penguasaan materi lanjutan yang lebih kompleks.",
            'questions' => [
                "Jelaskan dengan bahasa sederhana: apa prinsip utama dari {$topicTitle} dan mengapa prinsip itu penting?",
                "Bandingkan dua pendekatan atau konsep berbeda dalam {$topicTitle} — apa kelebihan dan kekurangan masing-masing?",
                "Berikan satu contoh skenario nyata di mana konsep {$topicTitle} dapat diterapkan, dan jelaskan bagaimana penerapannya.",
                "Apa kesalahan atau miskonsepsi yang paling sering terjadi saat mempelajari {$topicTitle}, dan bagaimana cara menghindarinya?",
                "Bagaimana alur atau mekanisme kerja utama dalam {$topicTitle} dari input awal hingga menghasilkan output?",
                "Jika salah satu komponen kunci pada {$topicTitle} dihilangkan atau gagal, dampak apa yang terjadi pada keseluruhan sistem?",
            ],
        ];
    }
}
