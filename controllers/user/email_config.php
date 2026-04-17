<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'craftlinktunisie@gmail.com');
define('SMTP_PASSWORD', 'wgot tfnb rlei mfuw');  // votre mot de passe d'application
define('MAIL_FROM', 'craftlinktunisie@gmail.com');
define('MAIL_FROM_NAME', 'CraftLink Tunisie');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = isset($_SERVER['SCRIPT_NAME']) ? rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') : '';
if ($basePath === '/' || $basePath === '.') {
	$basePath = '';
}

define('APP_BASE_URL', $scheme . '://' . $host . $basePath);
?>