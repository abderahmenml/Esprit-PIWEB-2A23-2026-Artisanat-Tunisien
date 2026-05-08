<?php
$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require_once $localConfig;
}

function craftlink_config(string $key, $default = null) {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
}

define('DB_HOST', craftlink_config('DB_HOST', 'localhost'));
define('DB_NAME', craftlink_config('DB_NAME', 'craftlink_db'));
define('DB_USER', craftlink_config('DB_USER', 'root'));
define('DB_PASS', craftlink_config('DB_PASS', ''));
define('DB_CHARSET', craftlink_config('DB_CHARSET', 'utf8mb4'));

define('APP_URL', craftlink_config('APP_URL', 'http://localhost/craftlink'));
define('APP_NAME', craftlink_config('APP_NAME', 'CraftLink Tunisie'));

define('SMTP_HOST', craftlink_config('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) craftlink_config('SMTP_PORT', 587));
define('SMTP_USERNAME', craftlink_config('SMTP_USERNAME', ''));
define('SMTP_PASSWORD', craftlink_config('SMTP_PASSWORD', ''));
define('MAIL_FROM', craftlink_config('MAIL_FROM', SMTP_USERNAME));
define('MAIL_FROM_NAME', craftlink_config('MAIL_FROM_NAME', 'CraftLink Tunisie'));

define('ROLES', ['entrepreneur', 'artisan', 'mentor', 'investisseur', 'admin']);
define('FACE_MATCH_THRESHOLD', (float) craftlink_config('FACE_MATCH_THRESHOLD', 0.52));
define('FACE_API_MODEL_URL', craftlink_config('FACE_API_MODEL_URL', 'https://justadudewhohacks.github.io/face-api.js/models'));
define('RECAPTCHA_V2_SITE_KEY', craftlink_config('RECAPTCHA_V2_SITE_KEY', ''));
define('RECAPTCHA_V2_SECRET_KEY', craftlink_config('RECAPTCHA_V2_SECRET_KEY', ''));

define('SUPABASE_URL', craftlink_config('SUPABASE_URL', ''));
define('SUPABASE_ANON_KEY', craftlink_config('SUPABASE_ANON_KEY', ''));
define('SUPABASE_SECRET_KEY', craftlink_config('SUPABASE_SECRET_KEY', ''));
define('ENABLE_SYNC', filter_var(craftlink_config('ENABLE_SYNC', 'true'), FILTER_VALIDATE_BOOLEAN));
