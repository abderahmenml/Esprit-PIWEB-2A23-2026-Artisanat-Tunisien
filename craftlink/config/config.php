<?php
// ── Configuration générale ──────────────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_NAME',     'craftlink_db');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

define('APP_URL',     'http://localhost/craftlink');
define('APP_NAME',    'CraftLink Tunisie');

// SMTP Gmail
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USERNAME',  'craftlinktunisie@gmail.com');
define('SMTP_PASSWORD',  'wgot tfnb rlei mfuw');
define('MAIL_FROM',      'craftlinktunisie@gmail.com');
define('MAIL_FROM_NAME', 'CraftLink Tunisie');

// Rôles autorisés
define('ROLES', ['entrepreneur', 'artisan', 'mentor', 'investisseur']);
