# Fluidity — Adaptive Study Guidance Web App

**Fluidity** adalah aplikasi web pendamping belajar adaptif berbasis AI dan *Spaced Repetition* yang dirancang untuk membantu mahasiswa mempersiapkan ujian secara terstruktur tanpa *burnout*.

Dikembangkan oleh **Tim WADW** untuk kompetisi **GDGoC ITB STACK 2026**.

---

## Latar Belakang & Tujuan

Mahasiswa sering kewalahan mengatur waktu belajar menjelang ujian—bingung harus mulai dari materi yang mana, menumpuk belajar dalam satu hari (*SKS / Sistem Kebut Semalam*), atau merasa sudah paham padahal baru sekadar membaca.

**Fluidity** menyelesaikan masalah tersebut dengan menggabungkan:
- **Penjadwalan Adaptif (SM-2 + Exam Urgency)** yang menyesuaikan jadwal kosong mahasiswa di Google Calendar.
- **Pembelajaran Aktif Terpandu** (*Pomodoro Timer* → *Active Recall* → *Feynman Technique*) dengan evaluasi pemahaman oleh AI.

---

## Fitur Utama

1. **Autentikasi Google OAuth 2.0 & Sinkronisasi Google Calendar**
   - Login cepat menggunakan akun Google.
   - Membaca agenda sibuk (*busy slots*) di Google Calendar mahasiswa agar jadwal belajar tidak bentrok dengan kelas atau kegiatan lain.
   - Menambahkan sesi belajar yang telah dikonfirmasi langsung ke Google Calendar lengkap dengan tautan menuju ruang belajar.

2. **Manajemen Mata Kuliah, Multi-Modul Materi, & Rangkuman AI**
   - Tambah dan **edit mata kuliah** (nama, kode, tanggal ujian, warna tag, serta edit/tambah/hapus bab secara dinamis).
   - **Pemilih warna mata kuliah** (palet 8 warna terkurasi + warna kustom) yang tampil langsung sebagai latar belakang dan aksen kartu mata kuliah di Dashboard.
   - Unggah **lebih dari satu file modul/slide dosen per bab** (`PDF`, `DOC`, `DOCX`, `TXT`, maks. `5 MB` per file), lengkap dengan opsi hapus atau tambah file saat edit.
   - Ekstraksi teks gabungan semua modul (dukungan ekstraksi lokal `pdftotext` maupun pembacaan PDF langsung) untuk menghasilkan **Rangkuman Konsep Inti** dan **6 Pertanyaan Active Recall** secara otomatis menggunakan **Gemini API**.

3. **Algoritma Penjadwalan Adaptif (SM-2 + Exam-Proximity Compression)**
   - Menghitung skor prioritas setiap bab berdasarkan:
     - **40%** Kelemahan Penguasaan (*Mastery Level*)
     - **35%** Urgensi Sisa Hari Menjelang Ujian
     - **25%** Bobot Kesulitan Materi
   - Membatasi sesi maksimal secara default (**2 sesi/hari**, dapat disesuaikan `1–4 sesi/hari`) untuk mencegah *burnout*.
   - Otomatis berhenti menjadwalkan setelah melewati tanggal ujian mata kuliah.

4. **Pengaturan Jam Luang Fleksibel (Format 24 Jam)**
   - Mahasiswa dapat mengatur sendiri rentang jam luang belajar harian hingga ke tingkat menit (`HH:MM`, format 24 jam) serta target maksimal sesi per hari.

5. **Study Room Interaktif (Pomodoro + Active Recall + Feynman)**
   - **Fase 1 — Pomodoro Timer:** Timer fokus (`50` / `25` menit, tersedia juga preset `5` dan `1` menit untuk demo) dengan kontrol mulai, jeda, dan reset.
   - **Fase 2 — Active Recall:** Memilih pertanyaan konseptual dari bank soal yang diprioritaskan pada pertanyaan yang belum pernah dijawab, dilengkapi tombol **Buat Soal Baru** secara *on-demand*.
   - **Fase 3 — Teknik Feynman & Evaluasi AI:** Mahasiswa menjelaskan konsep dengan bahasanya sendiri; AI memberikan umpan balik kualitatif yang suportif serta skor `1–5` yang langsung memperbarui *Mastery Level* dan interval review SM-2 berikutnya.

---

## Tech Stack

- **Backend:** PHP 8.2+ / Laravel 13
- **Database:** MySQL
- **Frontend:** Blade Templates, Tailwind CSS v4 (Vite), Alpine.js
- **Integrasi Eksternal:**
  - Laravel Socialite (Google OAuth 2.0)
  - Google Calendar REST API v3
  - Google Gemini API (`gemini-3.5-flash` dengan *fallback* antar-model Flash)

---

## Cara Menjalankan Project (Setelah Clone Repo)

### Prasyarat
- **PHP >= 8.2** (ekstensi `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`)
- **Composer**
- **Node.js & npm**
- **MySQL** (misalnya via XAMPP / MariaDB)
- *(Opsional, sangat disarankan)* `pdftotext` (`poppler-utils`) untuk ekstraksi teks PDF lokal yang cepat

### Langkah Instalasi

1. **Clone repository dan masuk ke direktori project**
   ```bash
   git clone <URL_REPOSITORY_INI>
   cd fluidity-dev
   ```

2. **Install dependensi PHP dan JavaScript**
   ```bash
   composer install
   npm install
   ```

3. **Salin file environment & generate application key**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi file `.env`**
   Sesuaikan koneksi database MySQL serta kredensial Google OAuth & Gemini API:
   ```env
   APP_URL=http://127.0.0.1:8000
   APP_TIMEZONE=Asia/Jakarta

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=fluidity_dev
   DB_USERNAME=root
   DB_PASSWORD=

   GOOGLE_CLIENT_ID=your_google_client_id
   GOOGLE_CLIENT_SECRET=your_google_client_secret
   GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback

   GEMINI_API_KEY=your_gemini_api_key
   GEMINI_MODEL=gemini-3.5-flash
   ```
   > **Catatan Google Cloud Console:**
   > - Aktifkan **Google Calendar API** pada project Google Cloud Anda.
   > - Daftarkan `http://127.0.0.1:8000/auth/google/callback` pada **Authorized redirect URIs** di OAuth 2.0 Client ID.

5. **Buat database & jalankan migrasi**
   Pastikan service MySQL sudah berjalan, buat database bernama `fluidity_dev`, lalu jalankan:
   ```bash
   php artisan migrate
   php artisan storage:link
   ```

6. **Build aset frontend**
   ```bash
   npm run build
   ```

7. **Jalankan server lokal**
   Fitur upload modul mendukung file hingga **5 MB**, sedangkan batas bawaan PHP (`upload_max_filesize`) di banyak sistem masih **2 MB**. Anda bisa memilih salah satu cara berikut:
   - **Cara A — Gunakan script `./serve.sh` (disarankan jika memakai `php artisan serve` tanpa mengubah `php.ini` sistem):**
     ```bash
     ./serve.sh
     ```
     *(Atau manual: `PHP_INI_SCAN_DIR=":$(pwd)/php-config" php artisan serve`)*
   - **Cara B — Ubah `php.ini` global di sistem Anda (lalu jalankan `php artisan serve` biasa):**
     Edit file `php.ini` Anda (misalnya di XAMPP/Laragon/Linux `/etc/php/.../php.ini`), ubah nilai berikut, lalu jalankan `php artisan serve`:
     ```ini
     upload_max_filesize = 10M
     post_max_size = 20M
     ```
   - **Cara C — Menggunakan Nginx / PHP-FPM / Apache (Laragon, Herd, XAMPP VirtualHost):**
     File [public/.user.ini](public/.user.ini) sudah disediakan dan akan dibaca otomatis oleh PHP-FPM/CGI (tidak perlu `./serve.sh`).

8. **Buka aplikasi di browser**
   Akses `http://127.0.0.1:8000` (gunakan `127.0.0.1`, bukan `localhost`, agar sesuai dengan Redirect URI Google OAuth).

---

## Tim WADW

- **Muhammad Rian Anugrah**
- **Afit Fajar Rianto**
- **Muhammad Hanif Muyassar**
- **Dani Eka Saputra**
