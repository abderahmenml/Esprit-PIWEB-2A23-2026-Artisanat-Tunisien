<?php
include '../../Controller/IdeaController.php';

if (getCurrentUserId() <= 0) {
    header('Location: ../FrontOffice/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = 0;
if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$ideaC = new IdeaController();
$ideaC->deleteIdeaAny($id);

header('Location: index.php');
exit;
