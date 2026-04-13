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

$title = '';
if (isset($_POST['title'])) {
    $title = trim($_POST['title']);
}

$category = '';
if (isset($_POST['category'])) {
    $category = trim($_POST['category']);
}

$status = '';
if (isset($_POST['status'])) {
    $status = trim($_POST['status']);
}

$budgetRaw = '';
if (isset($_POST['budget'])) {
    $budgetRaw = trim($_POST['budget']);
}

$budget = null;
if ($budgetRaw !== '') {
    $budget = (float)$budgetRaw;
}

$description = '';
if (isset($_POST['description'])) {
    $description = trim($_POST['description']);
}

if ($id <= 0 || $title === '') {
    header('Location: backoffice.php');
    exit;
}

$stmt = $pdo->prepare(
    'UPDATE projet SET titre = :titre, budget_min = :budget_min, status = :status, categorie = :categorie, description = :description WHERE id_projet = :id'
);
$statusValue = null;
if ($status !== '') {
    $statusValue = $status;
}

$categoryValue = null;
if ($category !== '') {
    $categoryValue = $category;
}

$descriptionValue = null;
if ($description !== '') {
    $descriptionValue = $description;
}

$stmt->execute([
    ':titre' => $title,
    ':budget_min' => $budget,
    ':status' => $statusValue,
    ':categorie' => $categoryValue,
    ':description' => $descriptionValue,
    ':id' => $id
]);

header('Location: backoffice.php');
exit;
