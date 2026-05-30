<?php

/**
 * SatuSehatService Class
 * Integrates Kemenkes SatuSehat FHIR API with safety guards
 */
class SatuSehatService
{
    private $enabled;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $orgId;

    /**
     * Constructor - Load configurations
     */
    public function __construct()
    {
        $this->enabled = config('satusehat.enabled', false);
        $this->baseUrl = config('satusehat.base_url', '');
        $this->clientId = config('satusehat.client_id', '');
        $this->clientSecret = config('satusehat.client_secret', '');
        $this->orgId = config('satusehat.org_id', '');
    }

    /**
     * Get OAuth 2.0 Access Token
     * Uses session cache to prevent requesting token on every call
     * 
     * @return string|null JWT access token
     */
    public function getAccessToken()
    {
        if (!$this->enabled) {
            return 'mock-jwt-token-value';
        }

        // Check database cache instead of session
        $cachedToken = $this->getDbSetting('satusehat_token');
        $cachedExpires = $this->getDbSetting('satusehat_token_expires');

        if ($cachedToken && $cachedExpires && (int)$cachedExpires > time()) {
            return $cachedToken;
        }

        $url = rtrim($this->baseUrl, '/') . '/oauth2/v1/token';
        
        // OAuth request requires application/x-www-form-urlencoded
        $body = http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ]);

        $headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $result = HttpClient::request('POST', $url, $headers, $body);

        if ($result['status_code'] !== 200) {
            error_log("SatuSehat Auth Error: Code " . $result['status_code'] . " | " . $result['error']);
            return null;
        }

        $data = json_decode($result['body'], true);
        if (isset($data['access_token'])) {
            $token = $data['access_token'];
            // Expire 5 minutes early to be safe (typically valid for 3600 seconds)
            $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 3600;
            $expiresAt = time() + $expiresIn - 300;

            $this->setDbSetting('satusehat_token', $token);
            $this->setDbSetting('satusehat_token_expires', (string)$expiresAt);

            return $token;
        }

        return null;
    }

    /**
     * Send authenticated FHIR request to SatuSehat
     * 
     * @param string $method
     * @param string $endpoint
     * @param mixed $payload
     * @return array
     */
    private function sendRequest($method, $endpoint, $payload = null)
    {
        if (!$this->enabled) {
            $this->logMockRequest($method, $endpoint, $payload);
            return $this->getMockResponse($endpoint, $payload);
        }

        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'error' => true,
                'message' => 'Failed to obtain access token'
            ];
        }

        $headers = [
            "Authorization: Bearer " . $token,
            "Content-Type: application/json"
        ];

        $url = rtrim($this->baseUrl, '/') . '/fhir-r4/v1/' . ltrim($endpoint, '/');
        
        $result = HttpClient::request($method, $url, $headers, $payload);

        $responseObj = json_decode($result['body'], true);
        if (!$responseObj) {
            return [
                'error' => true,
                'status_code' => $result['status_code'],
                'message' => 'Invalid JSON response from SatuSehat: ' . $result['body']
            ];
        }

        return $responseObj;
    }

    /**
     * Search Patient UUID by NIK
     * 
     * @param string $nik
     * @return string|null
     */
    public function getPatientIdByNik($nik)
    {
        $result = $this->sendRequest('GET', "Patient?identifier=https://fhir.kemkes.go.id/id/nik|" . $nik);
        
        if (isset($result['entry'][0]['resource']['id'])) {
            return $result['entry'][0]['resource']['id'];
        }

        // Return a mock ID if disabled
        if (!$this->enabled) {
            return 'mock-patient-uuid-' . md5($nik);
        }

        return null;
    }

    /**
     * Search Practitioner (Doctor) UUID by NIK
     * 
     * @param string $nik
     * @return string|null
     */
    public function getPractitionerIdByNik($nik)
    {
        $result = $this->sendRequest('GET', "Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|" . $nik);
        
        if (isset($result['entry'][0]['resource']['id'])) {
            return $result['entry'][0]['resource']['id'];
        }

        if (!$this->enabled) {
            return 'mock-practitioner-uuid-' . md5($nik);
        }

        return null;
    }

    /**
     * Create Encounter (Kunjungan)
     * 
     * @param string $patientId FHIR Patient UUID
     * @param string $practitionerId FHIR Practitioner UUID
     * @param string $locationId FHIR Location UUID
     * @param string $practitionerName Doctor Name
     * @param string $locationName Room/Poli Name
     * @return array
     */
    public function createEncounter($patientId, $practitionerId, $locationId, $practitionerName, $locationName)
    {
        $payload = [
            'resourceType' => 'Encounter',
            'status' => 'arrived',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB', // ambulatory / rawat jalan
                'display' => 'ambulatory'
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientId
            ],
            'participant' => [
                [
                    'type' => [
                        [
                            'coding' => [
                                [
                                    'system' => 'http://terminology.hl7.org/CodeSystem/v3-ParticipationType',
                                    'code' => 'PPRF',
                                    'display' => 'primary performer'
                                ]
                            ]
                        ]
                    ],
                    'individual' => [
                        'reference' => 'Practitioner/' . $practitionerId,
                        'display' => $practitionerName
                    ]
                ]
            ],
            'period' => [
                'start' => date('c')
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/' . $locationId,
                        'display' => $locationName
                    ]
                ]
            ],
            'serviceProvider' => [
                'reference' => 'Organization/' . $this->orgId
            ]
        ];

        return $this->sendRequest('POST', 'Encounter', $payload);
    }

    /**
     * Create Observation (Tanda Vital)
     * 
     * @param string $encounterId FHIR Encounter UUID
     * @param string $patientId FHIR Patient UUID
     * @param int $systolic
     * @param int $diastolic
     * @param float $temp
     * @param int $heartRate
     * @return array
     */
    public function createObservation($encounterId, $patientId, $systolic, $diastolic, $temp, $heartRate)
    {
        $payload = [
            'resourceType' => 'Observation',
            'status' => 'final',
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/observation-category',
                            'code' => 'vital-signs',
                            'display' => 'Vital Signs'
                        ]
                    ]
                ]
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://loinc.org',
                        'code' => '85354-9',
                        'display' => 'Blood pressure panel with all children optional'
                    ]
                ]
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientId
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId
            ],
            'effectiveDateTime' => date('c'),
            'component' => [
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => '8480-6',
                                'display' => 'Systolic blood pressure'
                            ]
                        ]
                    ],
                    'valueQuantity' => [
                        'value' => (int)$systolic,
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]'
                    ]
                ],
                [
                    'code' => [
                        'coding' => [
                            [
                                'system' => 'http://loinc.org',
                                'code' => '8462-4',
                                'display' => 'Diastolic blood pressure'
                            ]
                        ]
                    ],
                    'valueQuantity' => [
                        'value' => (int)$diastolic,
                        'unit' => 'mmHg',
                        'system' => 'http://unitsofmeasure.org',
                        'code' => 'mm[Hg]'
                    ]
                ]
            ]
        ];

        return $this->sendRequest('POST', 'Observation', $payload);
    }

    /**
     * Create Condition (Diagnosa)
     * 
     * @param string $encounterId FHIR Encounter UUID
     * @param string $patientId FHIR Patient UUID
     * @param string $icd10Code
     * @param string $icd10Name
     * @return array
     */
    public function createCondition($encounterId, $patientId, $icd10Code, $icd10Name)
    {
        $payload = [
            'resourceType' => 'Condition',
            'clinicalStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-clinical',
                        'code' => 'active',
                        'display' => 'Active'
                    ]
                ]
            ],
            'verificationStatus' => [
                'coding' => [
                    [
                        'system' => 'http://terminology.hl7.org/CodeSystem/condition-verstatus',
                        'code' => 'confirmed',
                        'display' => 'Confirmed'
                    ]
                ]
            ],
            'category' => [
                [
                    'coding' => [
                        [
                            'system' => 'http://terminology.hl7.org/CodeSystem/condition-category',
                            'code' => 'encounter-diagnosis',
                            'display' => 'Encounter Diagnosis'
                        ]
                    ]
                ]
            ],
            'code' => [
                'coding' => [
                    [
                        'system' => 'http://hl7.org/fhir/sid/icd-10',
                        'code' => $icd10Code,
                        'display' => $icd10Name
                    ]
                ]
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientId
            ],
            'encounter' => [
                'reference' => 'Encounter/' . $encounterId
            ]
        ];

        return $this->sendRequest('POST', 'Condition', $payload);
    }

    /**
     * Finish Encounter (Selesai Kunjungan)
     * 
     * @param string $encounterId FHIR Encounter UUID
     * @param string $patientId FHIR Patient UUID
     * @param string $practitionerId FHIR Practitioner UUID
     * @param string $locationId FHIR Location UUID
     * @param string $practitionerName Doctor Name
     * @param string $locationName Room/Poli Name
     * @param string $startTime ISO 8601 Encounter start datetime
     * @return array
     */
    public function finishEncounter($encounterId, $patientId, $practitionerId, $locationId, $practitionerName, $locationName, $startTime)
    {
        $payload = [
            'resourceType' => 'Encounter',
            'id' => $encounterId,
            'status' => 'finished',
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => 'AMB',
                'display' => 'ambulatory'
            ],
            'subject' => [
                'reference' => 'Patient/' . $patientId
            ],
            'participant' => [
                [
                    'individual' => [
                        'reference' => 'Practitioner/' . $practitionerId,
                        'display' => $practitionerName
                    ]
                ]
            ],
            'period' => [
                'start' => $startTime,
                'end' => date('c')
            ],
            'location' => [
                [
                    'location' => [
                        'reference' => 'Location/' . $locationId,
                        'display' => $locationName
                    ]
                ]
            ],
            'serviceProvider' => [
                'reference' => 'Organization/' . $this->orgId
            ]
        ];

        return $this->sendRequest('PUT', 'Encounter/' . $encounterId, $payload);
    }

    /**
     * Log details of a simulated integration request
     */
    private function logMockRequest($method, $endpoint, $payload)
    {
        $logPath = STORAGE_PATH . '/logs/satusehat.log';
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
        $id = 'mock-ss-uuid-' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        
        return [
            'resourceType' => explode('/', $endpoint)[0],
            'id' => $id,
            'status' => 'success',
            'message' => 'Simulated success for endpoint: ' . $endpoint
        ];
    }

    /**
     * Get a setting value from database
     */
    private function getDbSetting($key)
    {
        try {
            $row = Database::fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
            return $row ? $row['setting_value'] : null;
        } catch (Exception $e) {
            error_log("SatuSehatService: Failed to get DB setting {$key}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set a setting value in database (upsert)
     */
    private function setDbSetting($key, $value)
    {
        try {
            $row = Database::fetchOne("SELECT id FROM system_settings WHERE setting_key = ?", [$key]);
            if ($row) {
                Database::update('system_settings', ['setting_value' => $value], ['setting_key' => $key]);
            } else {
                Database::insert('system_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'setting_type' => 'string',
                    'category' => 'security',
                    'description' => 'SatuSehat temporary integration token/expiry',
                    'is_public' => 0
                ]);
            }
            return true;
        } catch (Exception $e) {
            error_log("SatuSehatService: Failed to set DB setting {$key}: " . $e->getMessage());
            return false;
        }
    }
}
