<?php

namespace MyBooking\Payments;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PaymentsSecurity
 * 
 * Helper for AES-256 encryption/decryption of sensitive API keys.
 */
class PaymentsSecurity
{
    /** @var string Method for encryption */
    private static $cipher_method = 'AES-256-CBC';

    /**
     * Encrypt sensitive data.
     *
     * @param string $data
     * @return string
     */
    public static function encrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $key = self::get_encryption_key();
        $iv_length = openssl_cipher_iv_length(self::$cipher_method);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($data, self::$cipher_method, $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data.
     *
     * @param string $data
     * @return string
     */
    public static function decrypt(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $key = self::get_encryption_key();
        $decoded = base64_decode($data);
        $iv_length = openssl_cipher_iv_length(self::$cipher_method);

        $iv = substr($decoded, 0, $iv_length);
        $encrypted = substr($decoded, $iv_length);

        return openssl_decrypt($encrypted, self::$cipher_method, $key, 0, $iv) ?: '';
    }

    /**
     * Get the encryption key from WordPress salt.
     */
    private static function get_encryption_key(): string
    {
        if (defined('LOGGED_IN_KEY')) {
            return LOGGED_IN_KEY;
        }

        // Fallback salt if LOGGED_IN_KEY is somehow missing
        return 'mbs_secure_salt_fallback_2026';
    }
}
