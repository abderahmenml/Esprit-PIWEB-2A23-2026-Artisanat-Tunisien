<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Méthode non autorisée.';
    exit;
}

function postValue($key)
{
    if (!isset($_POST[$key])) {
        return '';
    }

    return trim((string) $_POST[$key]);
}

$titre = postValue('titre');
$artisan = postValue('artisan');
$lieu = postValue('lieu');
$prixTexte = postValue('prix');
$dureeType = postValue('duree');
$typeAtelier = postValue('type');
$certif = strtolower(postValue('certif'));
$placesTexte = postValue('places');
$description = postValue('description');

if ($titre === '' || strlen($titre) < 3) {
    http_response_code(400);
    echo 'Le titre est obligatoire (min. 3 caractères).';
    exit;
}

if ($artisan === '' || strlen($artisan) < 2) {
    http_response_code(400);
    echo 'Le nom de l\'artisan est obligatoire.';
    exit;
}

if ($lieu === '' || strlen($lieu) < 2) {
    http_response_code(400);
    echo 'Le lieu est obligatoire.';
    exit;
}

if ($description === '' || strlen($description) < 10) {
    http_response_code(400);
    echo 'La description est obligatoire (min. 10 caractères).';
    exit;
}

if (!is_numeric($prixTexte)) {
    http_response_code(400);
    echo 'Le prix doit être un nombre valide.';
    exit;
}

$prix = (float) $prixTexte;
if ($prix < 0) {
    http_response_code(400);
    echo 'Le prix doit être supérieur ou égal à 0.';
    exit;
}

if (!ctype_digit($placesTexte)) {
    http_response_code(400);
    echo 'Le nombre de places doit être un entier positif.';
    exit;
}

$placesMax = (int) $placesTexte;
if ($placesMax <= 0) {
    http_response_code(400);
    echo 'Le nombre de places doit être supérieur à 0.';
    exit;
}

if ($dureeType !== '2h' && $dureeType !== 'journee') {
    http_response_code(400);
    echo 'La durée sélectionnée est invalide.';
    exit;
}

if ($certif !== 'oui' && $certif !== 'non') {
    http_response_code(400);
    echo 'La valeur de certification est invalide.';
    exit;
}

if ($typeAtelier !== 'famille' && $typeAtelier !== 'adulte') {
    http_response_code(400);
    echo 'Le type d\'atelier est invalide.';
    exit;
}

$dureeHeures = 2;
if ($dureeType === 'journee') {
    $dureeHeures = 5;
}

$descriptionComplete = $description . ' | Type: ' . $typeAtelier . ' | Artisan: ' . $artisan;

try {
    $sqlInsert = 'INSERT INTO workshops (titre, description, mentor_id, duree, date_publication, date_atelier, lieu, places_max, places_restantes, prix, lien_video, certification, statut)
                  VALUES (:titre, :description, NULL, :duree, CURDATE(), NULL, :lieu, :places_max, :places_restantes, :prix, NULL, :certification, :statut)';

    $stmt = $pdo->prepare($sqlInsert);
    $stmt->bindValue(':titre', $titre);
    $stmt->bindValue(':description', $descriptionComplete);
    $stmt->bindValue(':duree', $dureeHeures, PDO::PARAM_INT);
    $stmt->bindValue(':lieu', $lieu);
    $stmt->bindValue(':places_max', $placesMax, PDO::PARAM_INT);
    $stmt->bindValue(':places_restantes', $placesMax, PDO::PARAM_INT);
    $stmt->bindValue(':prix', $prix);
    $stmt->bindValue(':certification', $certif);
    $stmt->bindValue(':statut', 'a_venir');
    $stmt->execute();

    $nouvelId = (int) $pdo->lastInsertId();
    echo 'OK|' . $nouvelId;
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur base de données pendant l\'ajout du workshop.';
}
