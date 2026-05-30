<?php

/**
 * Crypt Class
 * Provides symmetric encryption/decryption using AES-256-CBC (complying with UU PDP)
 * and secure search hashing for exact-match lookups.
 */
class Crypt
{
    private static $cipher = 'AES-256-CBC';

    /**
     * Get encryption key (32 bytes binary)
     */
    private static function getKey()
    {
        $rawKey = env('DB_ENCRYPTION_KEY');
        if (empty($rawKey)) {
            throw new Exception("Enkripsi/Dekripsi gagal: DB_ENCRYPTION_KEY belum diatur di file .env.");
        }
        return hash('sha256', $rawKey, true);
    }

    /**
     * Encrypt data
     * 
     * @param string $data Plain text data
     * @return string|null Base64 encoded IV + Ciphertext, or null/empty if input is empty
     */
    public static function encrypt($data)
    {
        if ($data === null || $data === '') {
            return $data;
        }

        try {
            $key = self::getKey();
            $ivLength = openssl_cipher_iv_length(self::$cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);
            
            $ciphertext = openssl_encrypt(
                (string)$data,
                self::$cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($ciphertext === false) {
                return null;
            }

            // Prepend IV to ciphertext and Base64 encode the combined binary string
            return base64_encode($iv . $ciphertext);
        } catch (Exception $e) {
            error_log("Crypt Encryption failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt data
     * 
     * @param string $encryptedData Base64 encoded IV + Ciphertext
     * @return string|null Decrypted plain text, or the raw input if decryption fails (legacy fallback)
     */
    public static function decrypt($encryptedData)
    {
        if ($encryptedData === null || $encryptedData === '') {
            return $encryptedData;
        }

        // Check if the data is base64 encoded. If not, it is legacy plain text
        $decoded = base64_decode($encryptedData, true);
        if ($decoded === false) {
            return $encryptedData;
        }

        try {
            $key = self::getKey();
            $ivLength = openssl_cipher_iv_length(self::$cipher);
            
            if (strlen($decoded) < $ivLength) {
                return $encryptedData; // Not valid ciphertext
            }

            $iv = substr($decoded, 0, $ivLength);
            $ciphertext = substr($decoded, $ivLength);
            
            $decrypted = openssl_decrypt(
                $ciphertext,
                self::$cipher,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                // If decryption fails, it might be a valid base64 string that is not ciphertext.
                // Return raw string to prevent breaking legacy data.
                return $encryptedData;
            }

            return $decrypted;
        } catch (Exception $e) {
            error_log("Crypt Decryption failed: " . $e->getMessage());
            return $encryptedData;
        }
    }

    /**
     * Generate search hash (one-way hash) for exact-match pseudonymized queries
     * 
     * @param string $data Raw lookup data (e.g. NIK or BPJS number)
     * @return string|null SHA-256 HMAC hash
     */
    public static function hash($data)
    {
        if ($data === null || $data === '') {
            return null;
        }
        
        $salt = env('DB_ENCRYPTION_KEY');
        if (empty($salt)) {
            throw new Exception("Hashing gagal: DB_ENCRYPTION_KEY belum diatur di file .env.");
        }
        return hash_hmac('sha256', (string)$data, $salt);
    }
}
