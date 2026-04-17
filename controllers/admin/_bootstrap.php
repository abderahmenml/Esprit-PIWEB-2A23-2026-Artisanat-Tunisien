<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/Config.php';
require_auth();

if ((string)($_SESSION['role'] ?? '') !== 'admin') {
    app_redirect('controllers/home.php');
}

require_once dirname(__DIR__, 2) . '/models/AdminOfferRepository.php';

function admin_controller_url(string $path = ''): string
{
    return app_base_url() . 'controllers/admin/' . ltrim($path, '/');
}

function admin_asset_url(string $path = ''): string
{
    return app_base_url() . 'public/assets/admin/' . ltrim($path, '/');
}
