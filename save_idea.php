<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$title = '';
if (isset($_POST['title'])) {
    $title = trim($_POST['title']);
}
if ($title === '') {
    header('Location: index.php');
    exit;
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

try {
    $pdo->beginTransaction();

    $insertProject = $pdo->prepare(
        'INSERT INTO projet (titre, budget_min, status, date_creation, categorie, description) VALUES (:titre, :budget_min, :status, CURDATE(), :categorie, :description)'
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

    $insertProject->execute([
        ':titre' => $title,
        ':budget_min' => $budget,
        ':status' => $statusValue,
        ':categorie' => $categoryValue,
        ':description' => $descriptionValue
    ]);

    $projectId = (int)$pdo->lastInsertId();

    $insertSkill = $pdo->prepare('INSERT INTO skills (`nom`, `level`) VALUES (:nom, :level)');
    $linkSkill = $pdo->prepare('INSERT INTO required_skills (id_projet, id_skill) VALUES (:id_projet, :id_skill)');

    $skillsCount = count($skillsNames);
    if (count($skillsLevels) > $skillsCount) {
        $skillsCount = count($skillsLevels);
    }
    for ($i = 0; $i < $skillsCount; $i += 1) {
        $skillName = '';
        if (isset($skillsNames[$i])) {
            $skillName = trim($skillsNames[$i]);
        }

        $skillLevel = '';
        if (isset($skillsLevels[$i])) {
            $skillLevel = trim($skillsLevels[$i]);
        }

        if ($skillName === '' && $skillLevel === '') {
            continue;
        }

        $insertSkill->execute([
            ':nom' => $skillName,
            ':level' => $skillLevel
        ]);

        $skillId = (int)$pdo->lastInsertId();
        $linkSkill->execute([
            ':id_projet' => $projectId,
            ':id_skill' => $skillId
        ]);
    }

    $insertMaterial = $pdo->prepare('INSERT INTO materiel (nom_materiel, description) VALUES (:nom, :description)');
    $linkMaterial = $pdo->prepare(
        'INSERT INTO required_mater (id_projet, id_materiel, quantite, prix_unitaire) VALUES (:id_projet, :id_materiel, :quantite, :prix_unitaire)'
    );

    $materialsCount = count($materialsNames);
    if (count($materialsQty) > $materialsCount) {
        $materialsCount = count($materialsQty);
    }
    if (count($materialsPrice) > $materialsCount) {
        $materialsCount = count($materialsPrice);
    }
    for ($i = 0; $i < $materialsCount; $i += 1) {
        $materialName = '';
        if (isset($materialsNames[$i])) {
            $materialName = trim($materialsNames[$i]);
        }

        $qtyRaw = '';
        if (isset($materialsQty[$i])) {
            $qtyRaw = trim($materialsQty[$i]);
        }

        $priceRaw = '';
        if (isset($materialsPrice[$i])) {
            $priceRaw = trim($materialsPrice[$i]);
        }

        if ($materialName === '') {
            continue;
        }

        $insertMaterial->execute([
            ':nom' => $materialName,
            ':description' => null
        ]);

        $materielId = (int)$pdo->lastInsertId();
        $qtyValue = null;
        if ($qtyRaw !== '') {
            $qtyValue = (int)$qtyRaw;
        }

        $priceValue = null;
        if ($priceRaw !== '') {
            $priceValue = (float)$priceRaw;
        }

        $linkMaterial->execute([
            ':id_projet' => $projectId,
            ':id_materiel' => $materielId,
            ':quantite' => $qtyValue,
            ':prix_unitaire' => $priceValue
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
}

header('Location: index.php');
exit;
