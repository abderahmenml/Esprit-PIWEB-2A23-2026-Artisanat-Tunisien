<?php
require_once '../../Controller/IdeaController.php';
require_once '../../Model/Idea.php';

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
    header('Location: index.php');
    exit;
}

$idea = new Idea($id, $title, $budget, $status, $category, $description, null);
$ideaC = new IdeaController();
$ideaC->updateIdeaAny($idea, $id);

header('Location: index.php');
exit;
