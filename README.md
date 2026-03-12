# SiagaNusa - IDCamp Hackathon MVP

![SiagaNusa Banner](https://img.shields.io/badge/Status-MVP_Completed-success) ![Tech Stack](https://img.shields.io/badge/Tech-CodeIgniter_4_|_Gemini_AI_|_Tailwind_CSS_|_Leaflet.js-blue)

**"Small Apps for Big Preparedness"**

SiagaNusa adalah prototipe aplikasi peringatan dini dan pelaporan darurat bencana yang dibangun khusus untuk kompetisi IDCamp Hackathon. Aplikasi ini berfokus pada aksesibilitas informasi di saat panik (*Panic-Proof UI*) dan pengolahan bahasa alami tingkat lanjut menggunakan **Generative AI (Gemini Flash 2.5)**.

## 🚀 Core Context & Value Proposition
Saat terjadi bencana, masyarakat sering kali panik, sulit membaca data cuaca mentah yang rumit, dan melapor menggunakan bahasa gaul/daerah yang tidak terstruktur. SiagaNusa mengatasi masalah tersebut melalui **"AI Brain"** backend yang bertindak sebagai penerjemah dan asisten triase.

## 🛠️ Tech Stack
- **Backend Framework:** CodeIgniter 4 (PHP 8.2+)
- **Frontend / UI:** Vanilla Tailwind CSS (Dark Mode Native)
- **Mapping & Geolocation:** Leaflet.js & HTML5 Geolocation API
- **AI Engine:** Google Gemini API (`gemini-2.5-flash`)
- **Live Weather API:** OpenMeteo (Open Source)

## 🧠 AI Features (The "AI Brain")

Aplikasi ini menggunakan pendekatan *Prompt Engineering* tinggi di sisi server (`app/Libraries/AIService.php`) melalui 2 Endpoint utama:

### 1. AI Early Warning (`GET /api/warning`)
**Konsep:** Menerjemahkan data teknis cuaca/bencana menjadi kalimat evakuasi (Actionable) ramah manusia.
- **Input:** Titik koordinat Lokasi (*Lat/Lng*) dikonversi menjadi data satelit cuaca *real-time* via OpenMeteo (Curah hujan, kecepatan angin, elevasi, dll).
- **Proses AI:** Gemini disuntik dengan *System Prompt* ketat untuk bertindak sebagai otoritas kebencanaan yang menenangkan, menganalisis data meteorologi tersebut, dan merangkumnya dalam **Maksimal 3 Kalimat**.
- **Output:** Status Siaga / Saran berlindung / Rute Evakuasi.

### 2. Emergency NLP Triage (`POST /api/report`)
**Konsep:** Mengekstrak informasi terstruktur dari laporan kepanikan warga yang berantakan (menggunakan dialek lokal/informal).
- **Input:** Teks acak warga (Contoh: *"Tolong min air tiba-tiba naik sedengkul di rumah saya di Perumahan Anggrek Blok B!! Kakek saya stroke kejebak di kamar butuh banget perahu karet..."*).
- **Proses AI:** Gemini dipaksa melalui *JSON response format* untuk hanya mengembalikan struktur data khusus.
- **Output JSON:** `location`, `status`, level `priority` (LOW/MEDIUM/HIGH/CRITICAL), dan *array* `specific_needs` (contoh: ["Perahu Karet", "P3K"]).
- Frontend Leaflet.js kemudian mem-parsing JSON ini untuk menggambar **Red Pin Darurat Berdenyut** di peta secara dinamis.

## ⚙️ How to Run Locally

1. **Clone & Setup Folder**
   Pastikan Anda berada di direktori proyek `disastermanagement`.
2. **Install Dependencies**
   Jalankan `composer install` (jika vendor belum ada).
3. **Konfigurasi Environment**
   Ganti nama file `env` menjadi `.env`.
   Buka `.env`, sesuaikan pengaturan jika perlu, lalu tambahkan API Key Gemini Anda di baris paling bawah:
   ```env
   GEMINI_API_KEY="AIzaSyYourGoogleGeminiKeyHere"
   ```
4. **Jalankan Spark Local Server**
   ```bash
   php spark serve
   ```
5. **Akses Aplikasi**
   Buka browser di `http://localhost:8080/`. Izinkan (*Allow*) akses lokasi pada prompt browser untuk mengaktifkan Map dan AI Cuaca secara otomatis.

---
*Dibangun dengan ❤️ menggunakan pendampingan Agentic AI Prototype.*
