<?php
declare(strict_types=1);

namespace App;

final class Recaptcha
{
    public static function isEnabled(): bool
    {
        $environment = strtolower(self::environmentValue('APP_ENV'));

        return self::siteKey() !== '' || self::secretKey() !== '' || $environment === 'production';
    }

    public static function verify(string $token): bool
    {
        $secretKey = self::secretKey();
        if ($secretKey === '' || trim($token) === '') {
            return false;
        }

        $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($curl === false) {
            error_log('reCAPTCHA verification could not initialize cURL.');
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secretKey,
                'response' => trim($token),
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $curlError !== '' || $httpStatus < 200 || $httpStatus >= 300) {
            error_log('reCAPTCHA verification request failed: ' . ($curlError !== '' ? $curlError : 'HTTP ' . $httpStatus));
            return false;
        }

        $result = json_decode($response, true);
        return is_array($result) && ($result['success'] ?? false) === true;
    }

    public static function siteKey(): string
    {
        return self::environmentValue('RECAPTCHA_SITE_KEY');
    }

    public static function hasValidGoogleCsrf(): bool
    {
        $cookieToken = $_COOKIE['g_csrf_token'] ?? '';
        $postToken = $_POST['g_csrf_token'] ?? '';

        return is_string($cookieToken)
            && $cookieToken !== ''
            && is_string($postToken)
            && $postToken !== ''
            && hash_equals($cookieToken, $postToken);
    }

    private static function secretKey(): string
    {
        return self::environmentValue('RECAPTCHA_SECRET_KEY');
    }

    private static function environmentValue(string $key): string
    {
        return trim((string) (
            getenv($key)
            ?: ($_ENV[$key] ?? $_SERVER[$key] ?? '')
        ));
    }
}
