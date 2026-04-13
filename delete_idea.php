<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: backoffice.php');
    exit;
}

$id = 0;
if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
}
if ($id <= 0) {
    header('Location: backoffice.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM required_skills WHERE id_projet = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM required_mater WHERE id_projet = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM projet WHERE id_projet = :id')->execute([':id' => $id]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}

header('Location: backoffice.php');
exit;
