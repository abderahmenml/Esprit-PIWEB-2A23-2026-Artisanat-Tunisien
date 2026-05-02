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

$skillsNames = [];
if (isset($_POST['skills_name']) && is_array($_POST['skills_name'])) {
    $skillsNames = $_POST['skills_name'];
}

$skillsLevels = [];
if (isset($_POST['skills_level']) && is_array($_POST['skills_level'])) {
    $skillsLevels = $_POST['skills_level'];
}

$materialsNames = [];
if (isset($_POST['materials_name']) && is_array($_POST['materials_name'])) {
    $materialsNames = $_POST['materials_name'];
}

$materialsQty = [];
if (isset($_POST['materials_qty']) && is_array($_POST['materials_qty'])) {
    $materialsQty = $_POST['materials_qty'];
}

$materialsPrice = [];
if (isset($_POST['materials_price']) && is_array($_POST['materials_price'])) {
    $materialsPrice = $_POST['materials_price'];
}

if ($id <= 0 || $title === '') {
    header('Location: index.php');
    exit;
}

$idea = new Idea($id, $title, $budget, $status, $category, $description, null);
$ideaC = new IdeaController();
$ideaC->updateIdeaAnyWithDetails($idea, $id, $skillsNames, $skillsLevels, $materialsNames, $materialsQty, $materialsPrice);

header('Location: index.php');
exit;
