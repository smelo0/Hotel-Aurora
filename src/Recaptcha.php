<?php
declare(strict_types=1);

namespace App;

final class Recaptcha
{
    public static function isEnabled(): bool
    {
        $siteKey = self::siteKey();
        $secretKey = self::secretKey();

        return $siteKey !== '' && $secretKey !== '';
    }

    public static function verify(string $token): bool
    {
        $secretKey = self::secretKey();
        $token = trim($token);
        if ($secretKey === '' || $token === '') {
            return false;
        }

        // Si se usa la clave global de pruebas oficial de Google (testing), es válida si hay token
        if ($secretKey === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
            return true;
        }

        $environment = strtolower(self::environmentValue('APP_ENV'));
        $isProduction = $environment === 'production';

        $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($curl === false) {
            error_log('reCAPTCHA verification could not initialize cURL.');
            return !$isProduction;
        }

        $curlOptions = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secretKey,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ];

        // En entornos locales/desarrollo evitar fallo por certificados CA de Windows
        if (!$isProduction) {
            $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
            $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $curlError !== '' || $httpStatus < 200 || $httpStatus >= 300) {
            error_log('reCAPTCHA verification request failed: ' . ($curlError !== '' ? $curlError : 'HTTP ' . $httpStatus));
            return !$isProduction;
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
