<?php

class RecaptchaV2 {
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function isConfigured(): bool {
        return defined('RECAPTCHA_V2_SITE_KEY')
            && defined('RECAPTCHA_V2_SECRET_KEY')
            && RECAPTCHA_V2_SITE_KEY !== ''
            && RECAPTCHA_V2_SECRET_KEY !== '';
    }

    public static function siteKey(): string {
        return self::isConfigured() ? RECAPTCHA_V2_SITE_KEY : '';
    }

    public static function verify(?string $token, ?string $remoteIp = null): bool {
        if (!self::isConfigured()) {
            return true;
        }

        if ($token === null || trim($token) === '') {
            return false;
        }

        $payload = [
            'secret' => RECAPTCHA_V2_SECRET_KEY,
            'response' => $token,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        $response = self::post(self::VERIFY_URL, $payload);
        if ($response === null) {
            return false;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) && !empty($decoded['success']);
    }

    private static function post(string $url, array $payload): ?string {
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            return is_string($response) ? $response : null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 8,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        return is_string($response) ? $response : null;
    }
}
