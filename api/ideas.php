<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function send_json($statusCode, $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize_array($value)
{
    if (is_array($value)) {
        return $value;
    }
    return [];
}

function read_payload()
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return null;
    }

    $payload = json_decode($raw, true);
    if (is_array($payload)) {
        return $payload;
    }
    return null;
}

if ($method === 'GET') {
    $projects = $pdo->query('SELECT id_projet, titre, budget_min, status, date_creation, categorie, description FROM projet ORDER BY id_projet DESC')->fetchAll();

    $skillStmt = $pdo->prepare('SELECT s.nom, s.`level` AS skill_level FROM required_skills rs JOIN skills s ON s.id = rs.id_skill WHERE rs.id_projet = ?');
    $materialStmt = $pdo->prepare('SELECT m.nom_materiel, rm.quantite, rm.prix_unitaire FROM required_mater rm JOIN materiel m ON m.id_materiel = rm.id_materiel WHERE rm.id_projet = ?');

    $ideas = [];
    foreach ($projects as $project) {
        $skillStmt->execute([$project['id_projet']]);
        $skillsRows = $skillStmt->fetchAll();

        $materialStmt->execute([$project['id_projet']]);
        $materialRows = $materialStmt->fetchAll();

        $skills = [];
        foreach ($skillsRows as $row) {
            $skills[] = [
                'name' => $row['nom'],
                'level' => $row['skill_level']
            ];
        }

        $materials = [];
        foreach ($materialRows as $row) {
            $materials[] = [
                'name' => $row['nom_materiel'],
                'qty' => $row['quantite'],
                'price' => $row['prix_unitaire']
            ];
        }

        $descriptionValue = '';
        if (isset($project['description'])) {
            $descriptionValue = $project['description'];
        }

        $ideas[] = [
            'id' => (int)$project['id_projet'],
            'title' => $project['titre'],
            'category' => $project['categorie'],
            'status' => $project['status'],
            'budget' => $project['budget_min'],
            'description' => $descriptionValue,
            'skills' => $skills,
            'materials' => $materials,
            'createdAt' => $project['date_creation']
        ];
    }

    send_json(200, ['ok' => true, 'data' => $ideas]);
}

if ($method === 'POST') {
    $payload = read_payload();
    if ($payload === null) {
        send_json(400, ['ok' => false, 'message' => 'Invalid JSON']);
    }

    $title = '';
    if (isset($payload['title'])) {
        $title = trim((string)$payload['title']);
    }

    $category = '';
    if (isset($payload['category'])) {
        $category = trim((string)$payload['category']);
    }

    $status = '';
    if (isset($payload['status'])) {
        $status = trim((string)$payload['status']);
    }

    $budget = '';
    if (isset($payload['budget'])) {
        $budget = trim((string)$payload['budget']);
    }

    $description = '';
    if (isset($payload['description'])) {
        $description = trim((string)$payload['description']);
    }

        $skills = [];
        if (isset($payload['skills'])) {
            $skills = normalize_array($payload['skills']);
        }

        $materials = [];
        if (isset($payload['materials'])) {
            $materials = normalize_array($payload['materials']);
        }

    if ($title === '') {
        send_json(422, ['ok' => false, 'message' => 'Le titre est obligatoire']);
    }

    $budgetValue = $budget === '' ? null : (float)$budget;
    $categoryValue = $category === '' ? null : $category;
    $statusValue = $status === '' ? null : $status;

    try {
        $pdo->beginTransaction();

        $insertProject = $pdo->prepare(
            'INSERT INTO projet (titre, budget_min, status, date_creation, categorie, description) VALUES (:titre, :budget_min, :status, CURDATE(), :categorie, :description)'
        );
        $insertProject->execute([
            ':titre' => $title,
            ':budget_min' => $budgetValue,
            ':status' => $statusValue,
            ':categorie' => $categoryValue,
            ':description' => $description
        ]);

        $projectId = (int)$pdo->lastInsertId();

        $insertSkill = $pdo->prepare('INSERT INTO skills (`nom`, `level`) VALUES (:nom, :level)');
        $linkSkill = $pdo->prepare('INSERT INTO required_skills (id_projet, id_skill) VALUES (:id_projet, :id_skill)');

        foreach ($skills as $skill) {
            $skillName = '';
            if (isset($skill['name'])) {
                $skillName = trim((string)$skill['name']);
            }

            $skillLevel = '';
            if (isset($skill['level'])) {
                $skillLevel = trim((string)$skill['level']);
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
        $linkMaterial = $pdo->prepare('INSERT INTO required_mater (id_projet, id_materiel, quantite, prix_unitaire) VALUES (:id_projet, :id_materiel, :quantite, :prix_unitaire)');

        foreach ($materials as $material) {
            $materialName = '';
            if (isset($material['name'])) {
                $materialName = trim((string)$material['name']);
            }

            $qtyValue = null;
            if (isset($material['qty']) && $material['qty'] !== '') {
                $qtyValue = (int)$material['qty'];
            }

            $priceValue = null;
            if (isset($material['price']) && $material['price'] !== '') {
                $priceValue = (float)$material['price'];
            }

            if ($materialName === '') {
                continue;
            }

            $insertMaterial->execute([
                ':nom' => $materialName,
                ':description' => null
            ]);

            $materielId = (int)$pdo->lastInsertId();
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
        send_json(500, ['ok' => false, 'message' => 'Database error']);
    }

    send_json(201, [
        'ok' => true,
        'data' => [
            'id' => $projectId,
            'title' => $title,
            'category' => $categoryValue,
            'status' => $statusValue,
            'budget' => $budgetValue,
            'description' => $description,
            'skills' => $skills,
            'materials' => $materials,
            'createdAt' => date('Y-m-d')
        ]
    ]);
}

if ($method === 'PUT') {
    $payload = read_payload();
    if ($payload === null) {
        send_json(400, ['ok' => false, 'message' => 'Invalid JSON']);
    }

    $id = 0;
    if (isset($payload['id'])) {
        $id = (int)$payload['id'];
    }

    $title = '';
    if (isset($payload['title'])) {
        $title = trim((string)$payload['title']);
    }

    $category = '';
    if (isset($payload['category'])) {
        $category = trim((string)$payload['category']);
    }

    $status = '';
    if (isset($payload['status'])) {
        $status = trim((string)$payload['status']);
    }

    $budget = '';
    if (isset($payload['budget'])) {
        $budget = trim((string)$payload['budget']);
    }

    $description = '';
    if (isset($payload['description'])) {
        $description = trim((string)$payload['description']);
    }

    if ($id <= 0 || $title === '') {
        send_json(422, ['ok' => false, 'message' => 'Donnees invalides']);
    }

    $budgetValue = $budget === '' ? null : (float)$budget;
    $categoryValue = $category === '' ? null : $category;
    $statusValue = $status === '' ? null : $status;

    $stmt = $pdo->prepare(
        'UPDATE projet SET titre = :titre, budget_min = :budget_min, status = :status, categorie = :categorie, description = :description WHERE id_projet = :id'
    );
    $stmt->execute([
        ':titre' => $title,
        ':budget_min' => $budgetValue,
        ':status' => $statusValue,
        ':categorie' => $categoryValue,
        ':description' => $description,
        ':id' => $id
    ]);

    send_json(200, [
        'ok' => true,
        'data' => [
            'id' => $id,
            'title' => $title,
            'category' => $categoryValue,
            'status' => $statusValue,
            'budget' => $budgetValue,
            'description' => $description
        ]
    ]);
}

if ($method === 'DELETE') {
    $payload = read_payload();
    $id = 0;
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
    }
    if ($id <= 0 && $payload && isset($payload['id'])) {
        $id = (int)$payload['id'];
    }

    if ($id <= 0) {
        send_json(422, ['ok' => false, 'message' => 'ID manquant']);
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM required_skills WHERE id_projet = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM required_mater WHERE id_projet = :id')->execute([':id' => $id]);
        $pdo->prepare('DELETE FROM projet WHERE id_projet = :id')->execute([':id' => $id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        send_json(500, ['ok' => false, 'message' => 'Database error']);
    }

    send_json(200, ['ok' => true]);
}

send_json(405, ['ok' => false, 'message' => 'Method not allowed']);
