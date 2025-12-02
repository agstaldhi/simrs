<?php

/**
 * Validator Class
 * 
 * Handles input validation
 */

class Validator
{

    /**
     * Validate data against rules
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Errors (empty if valid)
     */
    public static function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $ruleList = explode('|', $ruleSet);

            foreach ($ruleList as $rule) {
                $params = [];

                // Check if rule has parameters
                if (strpos($rule, ':') !== false) {
                    list($rule, $paramString) = explode(':', $rule, 2);
                    $params = explode(',', $paramString);
                }

                $method = 'validate' . ucfirst($rule);

                if (method_exists(__CLASS__, $method)) {
                    $result = self::$method($field, $value, $params, $data);

                    if ($result !== true) {
                        $errors[$field] = $result;
                        break; // Stop on first error for this field
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate required field
     */
    protected static function validateRequired($field, $value, $params, $data)
    {
        if (empty($value) && $value !== '0') {
            return self::getErrorMessage($field, 'required');
        }
        return true;
    }

    /**
     * Validate email
     */
    protected static function validateEmail($field, $value, $params, $data)
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return self::getErrorMessage($field, 'email');
        }
        return true;
    }

    /**
     * Validate minimum length
     */
    protected static function validateMin($field, $value, $params, $data)
    {
        $min = $params[0] ?? 0;
        if (!empty($value) && strlen($value) < $min) {
            return str_replace(':min', $min, self::getErrorMessage($field, 'min'));
        }
        return true;
    }

    /**
     * Validate maximum length
     */
    protected static function validateMax($field, $value, $params, $data)
    {
        $max = $params[0] ?? 0;
        if (!empty($value) && strlen($value) > $max) {
            return str_replace(':max', $max, self::getErrorMessage($field, 'max'));
        }
        return true;
    }

    /**
     * Validate numeric
     */
    protected static function validateNumeric($field, $value, $params, $data)
    {
        if (!empty($value) && !is_numeric($value)) {
            return self::getErrorMessage($field, 'numeric');
        }
        return true;
    }

    /**
     * Validate integer
     */
    protected static function validateInteger($field, $value, $params, $data)
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            return self::getErrorMessage($field, 'integer');
        }
        return true;
    }

    /**
     * Validate alpha (letters only)
     */
    protected static function validateAlpha($field, $value, $params, $data)
    {
        if (!empty($value) && !preg_match('/^[a-zA-Z\s]+$/', $value)) {
            return self::getErrorMessage($field, 'alpha');
        }
        return true;
    }

    /**
     * Validate alphanumeric
     */
    protected static function validateAlphanumeric($field, $value, $params, $data)
    {
        if (!empty($value) && !preg_match('/^[a-zA-Z0-9\s]+$/', $value)) {
            return self::getErrorMessage($field, 'alphanumeric');
        }
        return true;
    }

    /**
     * Validate date
     */
    protected static function validateDate($field, $value, $params, $data)
    {
        if (!empty($value)) {
            $format = $params[0] ?? 'Y-m-d';
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                return self::getErrorMessage($field, 'date');
            }
        }
        return true;
    }

    /**
     * Validate URL
     */
    protected static function validateUrl($field, $value, $params, $data)
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            return self::getErrorMessage($field, 'url');
        }
        return true;
    }

    /**
     * Validate matches another field
     */
    protected static function validateMatches($field, $value, $params, $data)
    {
        $matchField = $params[0] ?? null;
        if (!empty($value) && $value !== ($data[$matchField] ?? null)) {
            return str_replace(':field', $matchField, self::getErrorMessage($field, 'matches'));
        }
        return true;
    }

    /**
     * Validate unique in database
     */
    protected static function validateUnique($field, $value, $params, $data)
    {
        if (empty($value)) {
            return true;
        }

        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;

        if (!$table) {
            return true;
        }

        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $queryParams = [$value];

        if ($exceptId) {
            $query .= " AND id != ?";
            $queryParams[] = $exceptId;
        }

        $result = Database::fetchOne($query, $queryParams);

        if ($result['count'] > 0) {
            return self::getErrorMessage($field, 'unique');
        }

        return true;
    }

    /**
     * Validate exists in database
     */
    protected static function validateExists($field, $value, $params, $data)
    {
        if (empty($value)) {
            return true;
        }

        $table = $params[0] ?? null;
        $column = $params[1] ?? $field;

        if (!$table) {
            return true;
        }

        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
        $result = Database::fetchOne($query, [$value]);

        if ($result['count'] == 0) {
            return self::getErrorMessage($field, 'exists');
        }

        return true;
    }

    /**
     * Validate in list
     */
    protected static function validateIn($field, $value, $params, $data)
    {
        if (!empty($value) && !in_array($value, $params)) {
            return self::getErrorMessage($field, 'in');
        }
        return true;
    }

    /**
     * Validate phone number (Indonesian format)
     */
    protected static function validatePhone($field, $value, $params, $data)
    {
        if (!empty($value) && !preg_match('/^(\+62|62|0)[0-9]{9,12}$/', $value)) {
            return self::getErrorMessage($field, 'phone');
        }
        return true;
    }

    /**
     * Validate NIK (Indonesian ID number)
     */
    protected static function validateNik($field, $value, $params, $data)
    {
        if (!empty($value) && !preg_match('/^[0-9]{16}$/', $value)) {
            return self::getErrorMessage($field, 'nik');
        }
        return true;
    }

    /**
     * Get error message
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @return string
     */
    protected static function getErrorMessage($field, $rule)
    {
        $messages = [
            'required' => 'Field :field wajib diisi',
            'email' => 'Field :field harus berupa email yang valid',
            'min' => 'Field :field minimal :min karakter',
            'max' => 'Field :field maksimal :max karakter',
            'numeric' => 'Field :field harus berupa angka',
            'integer' => 'Field :field harus berupa bilangan bulat',
            'alpha' => 'Field :field hanya boleh berisi huruf',
            'alphanumeric' => 'Field :field hanya boleh berisi huruf dan angka',
            'date' => 'Field :field harus berupa tanggal yang valid',
            'url' => 'Field :field harus berupa URL yang valid',
            'matches' => 'Field :field harus sama dengan :field',
            'unique' => 'Field :field sudah digunakan',
            'exists' => 'Field :field tidak ditemukan',
            'in' => 'Field :field tidak valid',
            'phone' => 'Field :field harus berupa nomor telepon yang valid',
            'nik' => 'Field :field harus berupa NIK 16 digit yang valid'
        ];

        $message = $messages[$rule] ?? 'Field :field tidak valid';
        return str_replace(':field', self::formatFieldName($field), $message);
    }

    /**
     * Format field name for display
     * 
     * @param string $field Field name
     * @return string
     */
    protected static function formatFieldName($field)
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Check if validation passed
     * 
     * @param array $errors Validation errors
     * @return bool
     */
    public static function passed($errors)
    {
        return empty($errors);
    }

    /**
     * Check if validation failed
     * 
     * @param array $errors Validation errors
     * @return bool
     */
    public static function failed($errors)
    {
        return !empty($errors);
    }
}
