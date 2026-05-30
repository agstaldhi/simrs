<?php

/**
 * BpjsService Class
 * Integrates VClaim and Antrean Online with safety guards
 */
class BpjsService
{
    private $enabled;
    private $baseUrl;
    private $consId;
    private $secretKey;
    private $userKeyVclaim;
    private $userKeyAntrean;

    /**
     * Constructor - Load configurations
     */
    public function __construct()
    {
        $this->enabled = config('bpjs.enabled', false);
        $this->baseUrl = config('bpjs.base_url', '');
        $this->consId = config('bpjs.cons_id', '');
        $this->secretKey = config('bpjs.secret_key', '');
        $this->userKeyVclaim = config('bpjs.user_key_vclaim', '');
        $this->userKeyAntrean = config('bpjs.user_key_antrean', '');
    }

    /**
     * Send signed request to BPJS API
     * 
     * @param string $method
     * @param string $endpoint
     * @param mixed $payload
     * @param bool $isAntrean
     * @return array Response array in standard BPJS VClaim format
     */
    private function sendRequest($method, $endpoint, $payload = null, $isAntrean = false)
    {
        if (!$this->enabled) {
            $this->logMockRequest($method, $endpoint, $payload);
            return $this->getMockResponse($endpoint, $payload);
        }

        $timestamp = time();
        $signature = BpjsCrypt::generateSignature($this->consId, $this->secretKey, $timestamp);
        $userKey = $isAntrean ? $this->userKeyAntrean : $this->userKeyVclaim;

        $headers = [
            "X-Cons-ID: " . $this->consId,
            "X-Timestamp: " . $timestamp,
            "X-Signature: " . $signature,
            "user_key: " . $userKey,
            "Content-Type: " . ($method === 'POST' ? 'application/x-www-form-urlencoded' : 'application/json')
        ];

        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $result = HttpClient::request($method, $url, $headers, $payload);

        if ($result['status_code'] !== 200) {
            return [
                'metaData' => [
                    'code' => (string)$result['status_code'],
                    'message' => 'HTTP Connection Error: ' . ($result['error'] ?: 'Code ' . $result['status_code'])
                ],
                'response' => null
            ];
        }

        $responseObj = json_decode($result['body'], true);
        if (!$responseObj) {
            return [
                'metaData' => [
                    'code' => '500',
                    'message' => 'Invalid JSON response from BPJS'
                ],
                'response' => null
            ];
        }

        // BPJS encrypts the response string if successful
        if (isset($responseObj['response']) && is_string($responseObj['response']) && !empty($responseObj['response'])) {
            $decrypted = BpjsCrypt::decryptResponse($responseObj['response'], $this->consId, $this->secretKey, $timestamp);
            if ($decrypted !== false) {
                $responseObj['response'] = json_decode($decrypted, true) ?: $decrypted;
            } else {
                $responseObj['metaData']['message'] .= ' (Decryption Failed)';
            }
        }

        return $responseObj;
    }

    /**
     * Check BPJS Member Status by NIK or Card Number
     * 
     * @param string $noKartuOrNik
     * @return array
     */
    public function checkKepesertaan($noKartuOrNik)
    {
        $isNik = strlen($noKartuOrNik) === 16;
        $endpoint = $isNik 
            ? "Peserta/nik/" . $noKartuOrNik 
            : "Peserta/nokartu/" . $noKartuOrNik;
            
        return $this->sendRequest('GET', $endpoint);
    }

    /**
     * Check Referral (Rujukan) by Referral Number
     * 
     * @param string $noRujukan
     * @return array
     */
    public function checkRujukan($noRujukan)
    {
        return $this->sendRequest('GET', "Rujukan/" . $noRujukan);
    }

    /**
     * Create SEP (Surat Eligibilitas Peserta)
     * 
     * @param array $params
     * @return array
     */
    public function createSEP($params)
    {
        return $this->sendRequest('POST', "SEP/2.0/insert", $params);
    }

    /**
     * Update Queue Status (Task ID 1-7)
     * 
     * @param string $antreanId Kode booking antrean
     * @param int $taskId ID Task (1-7)
     * @param int|null $waktu Epoch timestamp in milliseconds
     * @return array
     */
    public function updateTaskId($antreanId, $taskId, $waktu = null)
    {
        $payload = [
            'kodebooking' => $antreanId,
            'taskid' => (int)$taskId,
            'waktu' => $waktu ?: (time() * 1000)
        ];
        return $this->sendRequest('POST', "antrean/update", $payload, true);
    }

    /**
     * Log details of a simulated integration request
     */
    private function logMockRequest($method, $endpoint, $payload)
    {
        $logPath = STORAGE_PATH . '/logs/bpjs.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logEntry = sprintf(
            "[%s] [MOCK_REQUEST] %s %s\nPayload: %s\n%s\n",
            date('Y-m-d H:i:s'),
            $method,
            $endpoint,
            $payload ? (is_array($payload) ? json_encode($payload) : $payload) : 'none',
            str_repeat('=', 80)
        );
        error_log($logEntry, 3, $logPath);
    }

    /**
     * Return mock responses for dry runs
     */
    private function getMockResponse($endpoint, $payload)
    {
        $code = "200";
        $message = "OK (SIMULATED)";
        $data = null;

        if (preg_match('/Peserta\/(nik|nokartu)\/(.+)/', $endpoint, $matches)) {
            $identifier = $matches[2];
            $data = [
                'peserta' => [
                    'nama' => 'Pasien Simulasi BPJS',
                    'noKartu' => strlen($identifier) === 16 ? '0001234567899' : $identifier,
                    'nik' => strlen($identifier) === 16 ? $identifier : '3201012345670009',
                    'statusPeserta' => [
                        'keterangan' => 'AKTIF',
                        'kode' => '1'
                    ],
                    'jenisPeserta' => [
                        'keterangan' => 'PBI (Penerima Bantuan Iuran)'
                    ],
                    'umur' => [
                        'umurSekarang' => '45 tahun'
                    ]
                ]
            ];
        } elseif (strpos($endpoint, 'Rujukan/') !== false) {
            $data = [
                'rujukan' => [
                    'noRujukan' => '123456789012345',
                    'tglRujukan' => date('Y-m-d'),
                    'provPerujuk' => [
                        'kode' => '0123G001',
                        'nama' => 'Klinik Simulasi Pratama'
                    ],
                    'peserta' => [
                        'nama' => 'Pasien Rujukan BPJS',
                        'noKartu' => '0001234567899'
                    ],
                    'diagnosa' => [
                        'kode' => 'J06.9',
                        'nama' => 'Acute nasopharyngitis [common cold]'
                    ],
                    'poliRujukan' => [
                        'kode' => 'DAL',
                        'nama' => 'Penyakit Dalam'
                    ]
                ]
            ];
        } elseif (strpos($endpoint, 'SEP/2.0/insert') !== false) {
            $data = [
                'sep' => [
                    'noSep' => '0301R001' . date('m') . date('y') . 'V' . sprintf('%06d', rand(1, 999999)),
                    'tglSep' => date('Y-m-d'),
                    'peserta' => [
                        'nama' => 'Pasien Sep BPJS',
                        'noKartu' => '0001234567899'
                    ],
                    'diagnosa' => 'Acute nasopharyngitis'
                ]
            ];
        } else {
            $data = [
                'message' => 'Simulated success for endpoint: ' . $endpoint
            ];
        }

        return [
            'metaData' => [
                'code' => $code,
                'message' => $message
            ],
            'response' => $data
        ];
    }
}
