# Fluidity — Adaptive Study Guidance Web App

[Bahasa Indonesia](README.md) | **English**

**Fluidity** is an adaptive study companion web app built on AI and *Spaced Repetition*, designed to help university students prepare for exams in a structured way without *burnout*.

Developed by **Team WADW** for the **GDGoC ITB STACK 2026** competition.

---

## Background & Goals

Students often struggle to manage their study time before exams: they don't know which material to start with, they cram everything into a single night (*SKS / Sistem Kebut Semalam*, the Indonesian term for all-night cramming), or they feel they understand a topic when they have only read it.

**Fluidity** addresses this by combining:
- **Adaptive Scheduling (SM-2 + Exam Urgency)** that fits around the free slots in the student's Google Calendar.
- **Guided Active Learning** (*Pomodoro Timer* → *Active Recall* → *Feynman Technique*) with AI-based comprehension evaluation.

---

## Key Features

1. **Google OAuth 2.0 Authentication & Google Calendar Sync**
   - Quick sign-in with a Google account.
   - Reads *busy slots* from the student's Google Calendar so study sessions never clash with classes or other activities.
   - Adds confirmed study sessions straight to Google Calendar, including a link to the study room.

2. **Course Management, Multi-File Modules, & AI Summaries**
   - Add and **edit courses** (name, code, exam date, tag color, and dynamic add/edit/delete of chapters).
   - **Course color picker** (a curated 8-color palette plus custom colors), shown directly as the background and accent of each course card on the Dashboard.
   - Upload **multiple lecture modules/slides per chapter** (`PDF`, `DOC`, `DOCX`, `TXT`, max `5 MB` per file), with options to remove or add files when editing.
   - Combined text extraction from all modules (supports local `pdftotext` extraction as well as direct PDF reading) to automatically generate a **Core Concept Summary** and **6 Active Recall Questions** using the **Gemini API**.

3. **Adaptive Scheduling Algorithm (SM-2 + Exam-Proximity Compression)**
   - Calculates a priority score for each chapter based on:
     - **40%** Mastery weakness (*Mastery Level*)
     - **35%** Urgency of days remaining until the exam
     - **25%** Material difficulty weight
   - Caps sessions by default (**2 sessions/day**, adjustable to `1–4 sessions/day`) to prevent *burnout*.
   - Automatically stops scheduling once the course's exam date has passed.

4. **Flexible Free-Time Settings (24-Hour Format)**
   - Students can set their own daily study window down to the minute (`HH:MM`, 24-hour format) as well as a maximum number of sessions per day.

5. **Interactive Study Room (Pomodoro + Active Recall + Feynman)**
   - **Phase 1 — Pomodoro Timer:** Focus timer (`50` / `25` minutes, plus `5` and `1` minute presets for demos) with start, pause, and reset controls.
   - **Phase 2 — Active Recall:** Picks conceptual questions from the question bank, prioritizing ones not yet answered, with an on-demand **Generate New Question** button.
   - **Phase 3 — Feynman Technique & AI Evaluation:** The student explains the concept in their own words; the AI gives supportive qualitative feedback and a `1–5` score that immediately updates the *Mastery Level* and the next SM-2 review interval.

---

## Tech Stack

- **Backend:** PHP 8.2+ / Laravel 13
- **Database:** MySQL
- **Frontend:** Blade Templates, Tailwind CSS v4 (Vite), Alpine.js
- **External Integrations:**
  - Laravel Socialite (Google OAuth 2.0)
  - Google Calendar REST API v3
  - Google Gemini API (`gemini-3.5-flash` with *fallback* between Flash models)

---

## Running the Project (After Cloning)

### Prerequisites
- **PHP >= 8.2** (extensions `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`)
- **Composer**
- **Node.js & npm**
- **MySQL** (e.g. via XAMPP / MariaDB)
- *(Optional, highly recommended)* `pdftotext` (`poppler-utils`) for fast local PDF text extraction

### Installation Steps

1. **Clone the repository and enter the project directory**
   ```bash
   git clone <THIS_REPOSITORY_URL>
   cd fluidity-dev
   ```

2. **Install PHP and JavaScript dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Copy the environment file & generate the application key**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure the `.env` file**
   Set the MySQL database connection and the Google OAuth & Gemini API credentials:
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
   > **Google Cloud Console notes:**
   > - Enable the **Google Calendar API** in your Google Cloud project.
   > - Register `http://127.0.0.1:8000/auth/google/callback` under **Authorized redirect URIs** in your OAuth 2.0 Client ID.

5. **Create the database & run migrations**
   Make sure the MySQL service is running, create a database named `fluidity_dev`, then run:
   ```bash
   php artisan migrate
   php artisan storage:link
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Start the local server**
   Module uploads support files up to **5 MB**, but PHP's default limit (`upload_max_filesize`) on many systems is still **2 MB**. Choose one of the following options:
   - **Option A — Use the `./serve.sh` script (recommended if you use `php artisan serve` without changing the system `php.ini`):**
     ```bash
     ./serve.sh
     ```
     *(Or manually: `PHP_INI_SCAN_DIR=":$(pwd)/php-config" php artisan serve`)*
   - **Option B — Edit your system's global `php.ini` (then run plain `php artisan serve`):**
     Edit your `php.ini` (e.g. in XAMPP/Laragon or Linux `/etc/php/.../php.ini`), change the following values, then run `php artisan serve`:
     ```ini
     upload_max_filesize = 10M
     post_max_size = 20M
     ```
   - **Option C — Use Nginx / PHP-FPM / Apache (Laragon, Herd, XAMPP VirtualHost):**
     The [public/.user.ini](public/.user.ini) file is already provided and is read automatically by PHP-FPM/CGI (no need for `./serve.sh`).

8. **Open the app in your browser**
   Go to `http://127.0.0.1:8000` (use `127.0.0.1`, not `localhost`, so it matches the Google OAuth Redirect URI).

---

## Team WADW

- **Muhammad Rian Anugrah**
- **Afit Fajar Rianto**
- **Muhammad Hanif Muyassar**
- **Dani Eka Saputra**
