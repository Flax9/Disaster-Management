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
        // System Prompt 1
        $systemInstruction = "Anda adalah \"SiagaNusa\", asisten AI siaga bencana yang sangat ahli dan berwenang. Tugas utama Anda adalah menerjemahkan data mentah cuaca/bencana (seperti curah hujan, kecepatan angin, atau peringatan geologis) menjadi instruksi evakuasi yang singkat, jelas, menenangkan, dan menyelamatkan nyawa masyarakat umum.\n\nAturan ketat:\n1. Gunakan bahasa Indonesia yang mudah dipahami, tidak kaku, namun tegas.\n2. Maksimal panjang balasan adalah 3 kalimat pendek dan padat.\n3. Kalimat pertama: Status siaga yang jelas (misalnya: [WASPADA], [BAHAYA], atau [SIAGA]).\n4. Kalimat kedua: Penjelasan singkat tentang ancaman dan waktu kejadian (jika ada).\n5. Kalimat ketiga: Instruksi paling prioritas/langkah langsung yang harus dilakukan penyelematan mandiri (Actionable).\n6. Jangan gunakan kata-kata yang memicu kepanikan buta, fokus pada solusi.";

        $userPrompt = "Data Cuaca/Bencana Terkini:\n" . json_encode($weatherData);

        return $this->callGeminiAPI($systemInstruction, $userPrompt);
    }

    /**
     * Memanggil API Gemini (Prompt 2: Emergency NLP Triage)
     */
    public function extractEmergencyTriage($userText)
    {
        // System Prompt 2
        $systemInstruction = "Anda adalah \"SiagaNusa Triage AI\", sistem otomatisasi pelaporan darurat. Tugas Anda adalah membaca laporan pengguna yang sedang panik, berantakan, dan mencakup bahasa gaul/daerah Indonesia, lalu mengekstrak informasi penting ke dalam format JSON yang valid dan ringkas.\n\nAturan ketat:\n1. HANYA kembalikan JSON. Dilarang keras menambahkan teks apa pun di luar struktur JSON.\n2. Format JSON yang Diharuskan:\n{\n  \"location\": \"Alamat spesifik/lokasi yang disebutkan (jika tidak tahu, isi null)\",\n  \"status\": \"Ringkasan kondisi darurat maksimal 5 kata\",\n  \"priority\": \"LOW\" | \"MEDIUM\" | \"HIGH\" | \"CRITICAL\",\n  \"specific_needs\": [\"Kebutuhan 1\", \"Kebutuhan 2\"]\n}\n3. Panduan Penentuan \"priority\":\n- CRITICAL: Ancaman nyawa langsung (terjebak, tenggelam, butuh evakuasi segera).\n- HIGH: Kerusakan berat atau ancaman tinggi jangka pendek.\n- MEDIUM: Bantuan logistik awal, air mulai masuk tapi aman sementara.\n- LOW: Laporan genangan kecil, tidak ada ancaman nyawa.";

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
