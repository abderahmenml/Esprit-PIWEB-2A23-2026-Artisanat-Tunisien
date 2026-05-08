<?php
require_once '../../config.php';
require_once '../../Controller/AuthController.php';

$controller = new AuthController();
$controller->resetSuccess();
