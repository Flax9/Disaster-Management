<?php

namespace App\Libraries;

use CodeIgniter\Config\Services;

class AIService
{
    private $apiKey;
    private $client;
    // Menggunakan v1beta agar mendukung parameter system_instruction
    // Fix: Key The user's API Key specifically supports the newer 2.x models
    private $geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        // Ambil API Key dari .env
        $this->apiKey = getenv('GEMINI_API_KEY');
        $this->client = Services::curlrequest();
    }

    /**
     * Memanggil API Gemini (Prompt 1: AI Early Warning)
     */
    public function generateEarlyWarning($weatherData)
    {
        // System Prompt 1 (Injeksi Aturan Jurnal Akademis)
        $systemInstruction = "Anda adalah \"SiagaNusa\", asisten AI siaga bencana yang berwenang. Anda bertugas mengevaluasi data curah hujan dan kecepatan angin real-time berdasarkan panduan: \"AI for Disaster Resilience\" (Narayana, 2025) & BMKG.\n\n" .
                             "KATEGORI INTENSITAS HUJAN BMKG (Per Jam):\n" .
                             "- Sangat Ringan: < 1 mm/jam\n" .
                             "- Ringan: 1 - 5 mm/jam\n" .
                             "- Sedang: 5 - 10 mm/jam\n" .
                             "- Lebat: 10 - 20 mm/jam\n" .
                             "- Sangat Lebat: > 20 mm/jam\n\n" .
                             "KATEGORI KECEPATAN ANGIN (Jurnal BEMAS, 2024):\n" .
                             "- Sangat Kencang: >= 40 km/jam\n" .
                             "- Kencang: 30 - 39 km/jam\n" .
                             "- Sedang: 20 - 29 km/jam\n" .
                             "- Normal: < 20 km/jam\n\n" .
                             "ATURAN MUTLAK PENENTUAN STATUS (Prioritaskan level tertinggi dari kombinasi Hujan & Angin):\n" .
                             "1. Jika Hujan kategori Lebat/Sangat Lebat (> 10 mm/jam) ATAU Angin Sangat Kencang (>= 40 km/jam), status: \"MERAH (AWAS/BAHAYA)\".\n" .
                             "2. Jika Hujan Kategori Sedang (5-10 mm/jam) ATAU Angin Kencang (30 - 39 km/jam), status: \"ORANYE (WASPADA)\".\n" .
                             "3. Jika Hujan Kategori Ringan (1-5 mm/jam) ATAU Angin Sedang (20 - 29 km/jam), status: \"KUNING (SIAGA)\".\n" .
                             "4. Jika Hujan Sangat Ringan (< 1 mm/jam) DAN Angin Normal (< 20 km/jam), status: \"HIJAU (AMAN)\".\n\n" .
                             "ATURAN OUTPUT:\n" .
                             "1. KEMBALIKAN HANYA FORMAT JSON MURNI. Tanpa awalan/akhiran apapun.\n" .
                             "2. Struktur JSON:\n" .
                             "{\n" .
                             "  \"status_bahaya\": \"Teks Status sesuai aturan mutlak di atas\",\n" .
                             "  \"pesan_peringatan_anti_panik\": \"Max 2 kalimat instruksi evakuasi/persiapan yang singkat, ramah, dan actionable tanpa memicu kepanikan.\"\n" .
                             "}";

        $userPrompt = "Data Cuaca/Bencana Terkini:\n" . json_encode($weatherData);

        // Memaksa output JSON (true)
        $response = $this->callGeminiAPI($systemInstruction, $userPrompt, true);
        
        if ($response) {
            $response = str_replace(['```json', '```'], '', $response);
            return json_decode(trim($response), true);
        }
        
        return null;
    }

    /**
     * Memanggil API Gemini (Prompt 2: Emergency NLP Triage)
     */
    public function extractEmergencyTriage($userText)
    {
        // System Prompt 2 (Injeksi Metodologi HazMiner dari Valkenborg, 2026 & Privasi dari Alswailim, 2023)
        $systemInstruction = "Anda adalah \"SiagaNusa Triage AI\", sistem otomatisasi pelaporan darurat. Tugas Anda adalah membaca laporan pengguna yang sedang panik, berantakan, dan mencakup bahasa gaul/daerah Indonesia, lalu mengekstrak informasi penting ke dalam format JSON yang valid.\n\n" .
                             "METODOLOGI HAZMINER (Valkenborg, 2026):\n" .
                             "1. Cegah Salah Klasifikasi: Analisis teks ini dan bedakan dengan tegas antara POTENSI (misal: 'hujan makin lebat, takut banjir') dengan KEJADIAN NYATA (misal: 'air udah masuk rumah sedengkul'). Jika hanya potensi tanpa dampak nyata, tandai sebagai BUKAN bencana darurat.\n" .
                             "2. Ekstraksi Q&A Internal:\n" .
                             "   - Di mana lokasi persis kejadiannya? (Jalan/Daerah/RT/RW)\n" .
                             "   - Apa saja kebutuhan mendesak atau dampak yang terjadi?\n" .
                             "   - Seberapa tinggi tingkat urgensinya (Rendah/Tinggi/Kritis) berdasarkan bahasa yang digunakan (misal: 'sedengkul', 'sepinggang')?\n\n" .
                             "PROTOKOL KEAMANAN PRIVASI (Alswailim, 2023):\n" .
                             "1. Data Anonymization: HAPUS secara paksa semua nama orang, nomor telepon, atau NIK/identitas pribadi (PII) dari teks yang dilaporkan. Jangan pernah memasukkan identitas pelapor ke dalam hasil ekstraksi JSON.\n" .
                             "2. Data Minimization: Fokus hanya pada ekstraksi parameter krusial (lokasi, tingkat bahaya, dan kebutuhan evakuasi/medis). Abaikan curhatan, keluhan politik, atau teks emosional lain yang tidak esensial untuk penyelamatan nyawa.\n\n" .
                             "ATURAN OUTPUT:\n" .
                             "1. HANYA kembalikan JSON murni. Dilarang keras menambahkan teks apa pun di luar struktur JSON.\n" .
                             "2. Format JSON yang Diharuskan:\n" .
                             "{\n" .
                             "  \"is_valid_disaster\": boolean (true jika ini laporan kejadian nyata, false jika sekadar tanya jawab biasa/laporan potensi belum terjadi),\n" .
                             "  \"lokasi_spesifik\": \"Alamat/lokasi spesifik (jika tidak tahu, isi null)\",\n" .
                             "  \"kebutuhan\": \"Ringkasan kebutuhan/dampak maksimal 5 kata (Tanpa Nama/Identitas)\",\n" .
                             "  \"tingkat_bahaya\": \"RENDAH\" | \"TINGGI\" | \"KRITIS\"\n" .
                             "}";

        $userPrompt = "Laporan Darurat Warga:\n\"" . $userText . "\"";

        $response = $this->callGeminiAPI($systemInstruction, $userPrompt, true);
        
        // Membersihkan markdown ```json ... ``` dari respons jika AI masih mengembalikannya
        if ($response) {
            $response = str_replace(['```json', '```'], '', $response);
            return json_decode(trim($response), true);
        }
        
        return null;
    }

    /**
     * Fungsi helper untuk HTTP Request ke Gemini API
     */
    private function callGeminiAPI($systemInstruction, $userPrompt, $isJsonResponse = false)
    {
        if (empty($this->apiKey) || $this->apiKey === 'PLACEHOLDER_KEY') {
            log_message('error', 'GEMINI_API_KEY belum dikonfigurasi di .env');
            return null;
        }

        $url = $this->geminiApiUrl . '?key=' . $this->apiKey;

        $body = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1, // Sangat rendah agar output baku dan terstruktur (terutama untuk JSON)
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ]
        ];

        // Jika kita secara spesifik meminta JSON, kita pasang response_mime_type (Fitur Gemini Flash)
        if ($isJsonResponse) {
            $body['generationConfig']['response_mime_type'] = 'application/json';
        }

        try {
            $response = $this->client->post($url, [
                'json' => $body,
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'http_errors' => false 
            ]);

            if ($response->getStatusCode() === 200) {
                $result = json_decode($response->getBody(), true);
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return $result['candidates'][0]['content']['parts'][0]['text'];
                }
            } else {
                log_message('error', 'Gemini API Error: ' . $response->getBody());
            }
        } catch (\Exception $e) {
            log_message('error', 'CURL Error: ' . $e->getMessage());
        }

        return null;
    }
}
