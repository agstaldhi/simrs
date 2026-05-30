<?php

/**
 * BPJS Integration Configuration
 */

return [
    'enabled' => env('BPJS_ENABLED', false),
    'base_url' => env('BPJS_BASE_URL', 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev'),
    'cons_id' => env('BPJS_CONS_ID', ''),
    'secret_key' => env('BPJS_SECRET_KEY', ''),
    'user_key_vclaim' => env('BPJS_USER_KEY_VCLAIM', ''),
    'user_key_antrean' => env('BPJS_USER_KEY_ANTREAN', ''),
];
