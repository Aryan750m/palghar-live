<?php
// app/Validator.php

namespace App;

use App\Middleware\CSRFCheck;

class Validator {
    /**
     * Validate email format
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength (min 8 chars, must contain 1 uppercase, 1 lowercase, 1 number)
     */
    public static function passwordStrength(string $password): bool {
        if (strlen($password) < 8) {
            return false;
        }
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        return true;
    }

    /**
     * Validate CSRF token matches session token
     */
    public static function csrf(?string $token): bool {
        return CSRFCheck::validateToken($token);
    }

    /**
     * Validate file upload size and true MIME types (prevent spoofing)
     */
    public static function imageUpload(array $file, int $maxSizeBytes = 10485760): bool {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        if ($file['size'] > $maxSizeBytes) {
            return false;
        }
        
        // Open fileinfo resource
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mimeType, $allowedTypes)) {
            return false;
        }
        
        // Final structural check using getimagesize
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return false;
        }
        
        return true;
    }

    /**
     * Validate string fields are present and not empty
     */
    public static function required(array $data, array $fields): array {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $errors[$field] = sprintf('The %s field is required.', str_replace('_', ' ', $field));
            }
        }
        return $errors;
    }
}
