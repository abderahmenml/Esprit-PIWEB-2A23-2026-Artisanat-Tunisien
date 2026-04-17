<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/Config.php';
require_role('recruteur');

app_redirect('controllers/offer_emploi/offres.php?compose=1');
