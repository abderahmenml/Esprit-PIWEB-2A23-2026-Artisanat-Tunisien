<?php
include '../../config.php';

$_SESSION = [];
if (session_id() !== '') {
    session_destroy();
}

header('Location: login.php');
exit;
