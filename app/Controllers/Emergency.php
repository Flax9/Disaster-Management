<?php

namespace App\Controllers;

use App\Libraries\AIService;

class Emergency extends BaseController
{
    private $aiService;

    public function __construct()
    {
        $this->aiService = new AIService();
    }

    /**
     * Endpoint untuk mendapatkan Peringatan Dini AI (Prompt 1)
     * Mengambil data cuaca REAL-TIME dari OpenMeteo API (Free, No Key required)
     */
    public function getWarning()
    {
        $lat = $this->request->getVar('lat');
        $lng = $this->request->getVar('lng');

        if (empty($lat) || empty($lng)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Koordinat lintang dan bujur diperlukan.'
            ])->setStatusCode(400);
        }

        // Fetch Data Cuaca Asli dari OpenMeteo
        $weatherData = $this->fetchRealWeatherData($lat, $lng);

        if (!$weatherData) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal mengambil data cuaca dari satelit OpenMeteo.'
            ])->setStatusCode(500);
        }

        // Kirim raw data cuaca tersebut ke "Otak AI" kita
        $warningMessage = $this->aiService->generateEarlyWarning($weatherData);

        if ($warningMessage) {
            return $this->response->setJSON([
                'status' => 'success',
                'warning' => $warningMessage
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Gagal menghasilkan peringatan dini AI.'
        ])->setStatusCode(500);
    }

    /**
     * Endpoint untuk memproses Laporan Darurat Warga (Prompt 2)
     */
    public function submitReport()
    {
        $userText = $this->request->getVar('report_text');
        $lat = $this->request->getVar('lat');
        $lng = $this->request->getVar('lng');

        if (empty($userText)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Laporan tidak boleh kosong.'
            ])->setStatusCode(400);
        }

        // Triage melalui AI (Extract data acak menjadi JSON)
        $triageData = $this->aiService->extractEmergencyTriage($userText);

        if ($triageData && is_array($triageData)) {
            // Sukses ekstrak JSON. Di sini kita bisa menyimpannya ke Database (Model).
            // Namun untuk MVP, kita langsung kembalikan ke Frontend untuk di map (Red Pin)
            
            // Tambahkan koordinat asli pengirim ke data JSON
            $triageData['lat'] = $lat;
            $triageData['lng'] = $lng;
            $triageData['original_text'] = $userText;

            return $this->response->setJSON([
                'status' => 'success',
                'data' => $triageData
            ]);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => 'Gagal memproses laporan darurat. AI tidak merespons sesuai format.'
        ])->setStatusCode(500);
    }

    /**
     * Helper Function: Mengambil data cuaca saat ini dari OpenMeteo
     */
    private function fetchRealWeatherData($lat, $lng)
    {
        $client = \Config\Services::curlrequest();
        
        // Meminta data 'current' untuk curah hujan (precipitation), cuaca (weather_code), dan kecepatan angin
        $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lng}&current=precipitation,weather_code,wind_speed_10m&timezone=Asia%2FJakarta";

        try {
            $response = $client->get($url, ['http_errors' => false]);
            
            if ($response->getStatusCode() === 200) {
                $rawResult = json_decode($response->getBody(), true);
                
                if (isset($rawResult['current'])) {
                    $current = $rawResult['current'];
                    
                    // Format terjemahan sederhana untuk dikonsumsi Prompt Gemini
                    return [
                        'sumber_data' => 'OpenMeteo API',
                        'elevasi_lokasi' => $rawResult['elevation'] . ' meter dpl',
                        'curah_hujan_saat_ini' => $current['precipitation'] . ' mm',
                        'kecepatan_angin' => $current['wind_speed_10m'] . ' km/jam',
                        'kode_cuaca_wmo' => $current['weather_code'] . ' (Semakin besar umumnya semakin ekstrem)'
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'OpenMeteo Fetch Error: ' . $e->getMessage());
        }

        return null;
    }
}
