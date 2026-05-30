<?php

/**
 * BpjsCrypt Class
 * Cryptographic helper for BPJS integration (Signature HMAC & AES Decryption with LZ-String decompress)
 */
class BpjsCrypt
{
    /**
     * Generate BPJS API signature
     * 
     * @param string $consId
     * @param string $secretKey
     * @param string $timestamp
     * @return string Base64 encoded HMAC-SHA256 signature
     */
    public static function generateSignature($consId, $secretKey, $timestamp)
    {
        $data = $consId . "&" . $timestamp;
        $signature = hash_hmac('sha256', $data, $secretKey, true);
        return base64_encode($signature);
    }

    /**
     * Decrypt and decompress BPJS encrypted response
     * 
     * @param string $encryptedString Base64 encoded encrypted response
     * @param string $consId
     * @param string $secretKey
     * @param string $timestamp
     * @return string|false Decrypted JSON string or false on failure
     */
    public static function decryptResponse($encryptedString, $consId, $secretKey, $timestamp)
    {
        try {
            // Key is generated from consId + secretKey + timestamp hashed with SHA-256
            $keyHash = hash('sha256', $consId . $secretKey . $timestamp, true);
            
            // AES-256-CBC uses 32 bytes key and first 16 bytes as IV
            $key = substr($keyHash, 0, 32);
            $iv = substr($keyHash, 0, 16);
            
            $decrypted = openssl_decrypt(
                base64_decode($encryptedString), 
                'AES-256-CBC', 
                $key, 
                OPENSSL_RAW_DATA, 
                $iv
            );

            if ($decrypted === false) {
                return false;
            }

            // Decompress using LZ-String
            return self::lzDecompress($decrypted);
        } catch (Exception $e) {
            error_log("BPJS Decryption Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pure PHP LZ-String Decompress implementation
     * Based on standard LZ-String algorithm
     * 
     * @param string $compressed
     * @return string
     */
    private static function lzDecompress($compressed)
    {
        if ($compressed === null || $compressed === '') {
            return '';
        }

        $dictionary = [];
        $nextPower = 4;
        $enlargeIn = 4;
        $dictSize = 4;
        $numBits = 3;
        $entry = "";
        $result = [];

        $data_val = self::charCodeAt($compressed, 0);
        $data_position = 32768;
        $data_index = 1;

        for ($i = 0; $i < 3; $i++) {
            $dictionary[$i] = $i;
        }

        $bits = 0;
        $maxpower = 4;
        $power = 1;
        while ($power != $maxpower) {
            $resb = $data_val & $data_position;
            $data_position >>= 1;
            if ($data_position == 0) {
                $data_position = 32768;
                $data_val = self::charCodeAt($compressed, $data_index++);
            }
            $bits |= ($resb > 0 ? 1 : 0) * $power;
            $power <<= 1;
        }

        $next = $bits;
        switch ($next) {
            case 0:
                $bits = 0;
                $maxpower = 256;
                $power = 1;
                while ($power != $maxpower) {
                    $resb = $data_val & $data_position;
                    $data_position >>= 1;
                    if ($data_position == 0) {
                        $data_position = 32768;
                        $data_val = self::charCodeAt($compressed, $data_index++);
                    }
                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }
                $c = chr($bits);
                break;
            case 1:
                $bits = 0;
                $maxpower = 65536;
                $power = 1;
                while ($power != $maxpower) {
                    $resb = $data_val & $data_position;
                    $data_position >>= 1;
                    if ($data_position == 0) {
                        $data_position = 32768;
                        $data_val = self::charCodeAt($compressed, $data_index++);
                    }
                    $bits |= ($resb > 0 ? 1 : 0) * $power;
                    $power <<= 1;
                }
                $c = self::utf8Chr($bits);
                break;
            case 2:
                return "";
        }

        $dictionary[3] = $c;
        $w = $c;
        $result[] = $c;

        while (true) {
            if ($data_index > strlen($compressed) + 1) {
                return "";
            }

            $bits = 0;
            $maxpower = pow(2, $numBits);
            $power = 1;
            while ($power != $maxpower) {
                $resb = $data_val & $data_position;
                $data_position >>= 1;
                if ($data_position == 0) {
                    $data_position = 32768;
                    $data_val = self::charCodeAt($compressed, $data_index++);
                }
                $bits |= ($resb > 0 ? 1 : 0) * $power;
                $power <<= 1;
            }

            $c = $bits;
            switch ($c) {
                case 0:
                    $bits = 0;
                    $maxpower = 256;
                    $power = 1;
                    while ($power != $maxpower) {
                        $resb = $data_val & $data_position;
                        $data_position >>= 1;
                        if ($data_position == 0) {
                            $data_position = 32768;
                            $data_val = self::charCodeAt($compressed, $data_index++);
                        }
                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }
                    $dictionary[$dictSize++] = chr($bits);
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 1:
                    $bits = 0;
                    $maxpower = 65536;
                    $power = 1;
                    while ($power != $maxpower) {
                        $resb = $data_val & $data_position;
                        $data_position >>= 1;
                        if ($data_position == 0) {
                            $data_position = 32768;
                            $data_val = self::charCodeAt($compressed, $data_index++);
                        }
                        $bits |= ($resb > 0 ? 1 : 0) * $power;
                        $power <<= 1;
                    }
                    $dictionary[$dictSize++] = self::utf8Chr($bits);
                    $c = $dictSize - 1;
                    $enlargeIn--;
                    break;
                case 2:
                    return implode("", $result);
            }

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }

            if (isset($dictionary[$c])) {
                $entry = $dictionary[$c];
            } else {
                if ($c === $dictSize) {
                    $entry = $w . self::utf8CharAt($w, 0);
                } else {
                    return null;
                }
            }
            $result[] = $entry;

            $dictionary[$dictSize++] = $w . self::utf8CharAt($entry, 0);
            $enlargeIn--;
            $w = $entry;

            if ($enlargeIn == 0) {
                $enlargeIn = pow(2, $numBits);
                $numBits++;
            }
        }
    }

    /**
     * Helper to get charCodeAt (UTF-8 safe)
     */
    private static function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if ($char === '') return 0;
        
        $c = ord($char[0]);
        if ($c < 128) {
            return $c;
        }
        if ($c < 224) {
            return (($c - 192) << 6) + ord($char[1]) - 128;
        }
        if ($c < 240) {
            return (($c - 224) << 12) + ((ord($char[1]) - 128) << 6) + ord($char[2]) - 128;
        }
        return (($c - 240) << 18) + ((ord($char[1]) - 128) << 12) + ((ord($char[2]) - 128) << 6) + ord($char[3]) - 128;
    }

    /**
     * Helper to convert character code to UTF-8 character
     */
    private static function utf8Chr($code)
    {
        if ($code < 128) {
            return chr($code);
        }
        if ($code < 2048) {
            return chr(192 + (($code - ($code % 64)) / 64)) . chr(128 + ($code % 64));
        }
        if ($code < 65536) {
            return chr(224 + (($code - ($code % 4096)) / 4096)) . chr(128 + ((($code % 4096) - ($code % 64)) / 64)) . chr(128 + ($code % 64));
        }
        return chr(240 + (($code - ($code % 262144)) / 262144)) . chr(128 + ((($code % 262144) - ($code % 4096)) / 4096)) . chr(128 + ((($code % 4096) - ($code % 64)) / 64)) . chr(128 + ($code % 64));
    }

    /**
     * Helper to get charAt (UTF-8 safe)
     */
    private static function utf8CharAt($str, $index)
    {
        return mb_substr($str, $index, 1, 'UTF-8');
    }
}
