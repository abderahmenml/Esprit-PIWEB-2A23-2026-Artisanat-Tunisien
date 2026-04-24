<?php
require_once '../model/config.php';

// Escapes dynamic text for safe HTML rendering.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Checks whether a table exists in the current database schema.
function tableExists($pdo, $tableName)
{
  $sql = 'SELECT COUNT(*) AS total
      FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_name = :table_name';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':table_name', $tableName);
  $stmt->execute();
  $row = $stmt->fetch();

  if ($row && isset($row['total']) && (int) $row['total'] > 0) {
    return true;
  }

  return false;
}

// Checks whether a specific column exists in a given table.
function columnExists($pdo, $tableName, $columnName)
{
  if (!tableExists($pdo, $tableName)) {
    return false;
  }

  $sql = 'SELECT COUNT(*) AS total
      FROM information_schema.columns
      WHERE table_schema = DATABASE()
        AND table_name = :table_name
        AND column_name = :column_name';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':table_name', $tableName);
  $stmt->bindValue(':column_name', $columnName);
  $stmt->execute();
  $row = $stmt->fetch();

  if ($row && isset($row['total']) && (int) $row['total'] > 0) {
    return true;
  }

  return false;
}

// Verifies that a user ID exists in the user table.
function userExistsById($pdo, $idUser)
{
  $userId = (int) $idUser;

  if ($userId <= 0) {
    return false;
  }

  if (!tableExists($pdo, 'user') || !columnExists($pdo, 'user', 'id_user')) {
    return false;
  }

  try {
    $sql = 'SELECT COUNT(*) AS total FROM `user` WHERE id_user = :id_user';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_user', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row && isset($row['total']) && (int) $row['total'] > 0) {
      return true;
    }
  } catch (PDOException $e) {
    return false;
  }

  return false;
}

// Normalizes level labels to canonical internal values.
function normalizeNiveau($niveau)
{
    $n = trim((string) $niveau);

    if ($n === 'Débutant' || $n === 'debutant' || $n === 'débutant') {
        return 'debutant';
    }
    if ($n === 'Intermédiaire' || $n === 'intermediaire' || $n === 'intermediere' || $n === 'intermédiaire') {
        return 'intermediaire';
    }
    if ($n === 'Avancé' || $n === 'avance' || $n === 'avancé') {
        return 'avance';
    }

    return '';
}

// Converts a normalized level value into a display label.
function niveauLabel($niveau)
{
    $n = normalizeNiveau($niveau);

    if ($n === 'debutant') {
        return 'Débutant';
    }
    if ($n === 'intermediaire') {
        return 'Intermédiaire';
    }
    if ($n === 'avance') {
        return 'Avancé';
    }

    return 'Non défini';
}

// Returns the CSS badge class associated with a level.
function niveauBadgeClass($niveau)
{
    $n = normalizeNiveau($niveau);

    if ($n === 'debutant') {
        return 'badge-debutant';
    }
    if ($n === 'intermediaire') {
        return 'badge-inter';
    }
    if ($n === 'avance') {
        return 'badge-avance';
    }

    return 'badge-brouillon';
}

// Returns the CSS badge class associated with a formation status.
function statutBadgeClass($etat)
{
    $s = strtolower(trim((string) $etat));

    if ($s === 'actif') {
        return 'badge-actif';
    }
    if ($s === 'inactif') {
        return 'badge-inactif';
    }

    return 'badge-brouillon';
}

// Converts certification inputs into a boolean yes/no value.
function certifOui($certification)
{
    $c = strtolower(trim((string) $certification));

    if ($c === 'oui' || $c === 'yes' || $c === '1') {
        return true;
    }

    return false;
}

// Cleans a formation title and removes trailing update artifacts.
function normalizeFormationTitle($title)
{
  $value = trim((string) $title);

  // Defensive cleanup: keep the title as entered by the user without trailing "updated" artifacts.
  $value = preg_replace('/\s+updated\s*[0-9]*$/i', '', $value);

  return trim((string) $value);
}

// Fetches one formation and its related quiz metadata by ID.
function getFormationById($pdo, $idFormation)
{
    if ($idFormation <= 0) {
        return null;
    }

  $selectQuizDescriptionExpr = 'NULL AS quiz_description';
  if (columnExists($pdo, 'quizz', 'description')) {
    $selectQuizDescriptionExpr = 'qz.description AS quiz_description';
  }

  $selectQuizNotePassageExpr = 'NULL AS quiz_note_passage';
  if (columnExists($pdo, 'quizz', 'note_passage')) {
    $selectQuizNotePassageExpr = 'qz.note_passage AS quiz_note_passage';
  } elseif (columnExists($pdo, 'quizz', 'score')) {
    $selectQuizNotePassageExpr = 'qz.score AS quiz_note_passage';
  }

  $selectQuizNbTentativesExpr = 'NULL AS quiz_nb_tentatives';
  if (columnExists($pdo, 'quizz', 'nb_tentatives')) {
    $selectQuizNbTentativesExpr = 'qz.nb_tentatives AS quiz_nb_tentatives';
  }

  $selectQuizDureeExpr = 'NULL AS quiz_duree_minutes';
  if (columnExists($pdo, 'quizz', 'duree_minutes')) {
    $selectQuizDureeExpr = 'qz.duree_minutes AS quiz_duree_minutes';
  } elseif (columnExists($pdo, 'quizz', 'duree')) {
    $selectQuizDureeExpr = 'qz.duree AS quiz_duree_minutes';
  }

  $quizLinksParts = [];
  if (columnExists($pdo, 'quizz', 'formation_id')) {
    $quizLinksParts[] = 'SELECT q.formation_id AS id_formation, q.id_quizz
               FROM quizz q
               WHERE q.formation_id IS NOT NULL';
  }
  if (tableExists($pdo, 'quizz_formations')) {
    $quizLinksParts[] = 'SELECT qf.id_formation, qf.id_quizz
               FROM quizz_formations qf';
  }

  $sqlFormation = '';
  if (count($quizLinksParts) > 0) {
    $sqlFormation = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix,
                qz.id_quizz AS quiz_id, qz.titre AS quiz_titre,
                ' . $selectQuizDescriptionExpr . ',
                ' . $selectQuizNotePassageExpr . ',
                ' . $selectQuizNbTentativesExpr . ',
                ' . $selectQuizDureeExpr . '
             FROM formations f
             LEFT JOIN (
               SELECT liens.id_formation, MIN(liens.id_quizz) AS id_quizz
               FROM (
               ' . implode(' UNION ALL ', $quizLinksParts) . '
               ) liens
               GROUP BY liens.id_formation
             ) lq ON lq.id_formation = f.id_formation
             LEFT JOIN quizz qz ON qz.id_quizz = lq.id_quizz
             WHERE f.id_formation = :id';
  } else {
    $sqlFormation = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix,
                NULL AS quiz_id, NULL AS quiz_titre,
                NULL AS quiz_description,
                NULL AS quiz_note_passage,
                NULL AS quiz_nb_tentatives,
                NULL AS quiz_duree_minutes
             FROM formations f
             WHERE f.id_formation = :id';
  }
    $stmtFormation = $pdo->prepare($sqlFormation);
    $stmtFormation->bindValue(':id', $idFormation, PDO::PARAM_INT);
    $stmtFormation->execute();

    return $stmtFormation->fetch();
}

// Retrieves the latest workshop linked to a formation using available relations.
function getWorkshopByFormationId($pdo, $idFormation)
{
    if ($idFormation <= 0 || !tableExists($pdo, 'workshops')) {
        return null;
    }

  $selectWorkshopMentorExpr = 'NULL AS mentor_id';
  if (columnExists($pdo, 'workshops', 'mentor_id')) {
    $selectWorkshopMentorExpr = 'w.mentor_id AS mentor_id';
  }

  $selectWorkshopDateExpr = 'NULL AS date_atelier';
  if (columnExists($pdo, 'workshops', 'date_atelier')) {
    $selectWorkshopDateExpr = 'w.date_atelier AS date_atelier';
  }

  $selectWorkshopStatusExpr = 'NULL AS statut';
  if (columnExists($pdo, 'workshops', 'statut')) {
    $selectWorkshopStatusExpr = 'w.statut AS statut';
  }

  $baseSelect = 'SELECT w.id_workshop, w.titre, w.description,
                        ' . $selectWorkshopMentorExpr . ',
                        w.duree,
                        ' . $selectWorkshopDateExpr . ',
                        w.lieu, w.places_max, w.prix, w.certification,
                        ' . $selectWorkshopStatusExpr . '
                 FROM workshops w';

  if (tableExists($pdo, 'workshops_formation')) {
    $sql = $baseSelect . '
            INNER JOIN workshops_formation wf ON wf.id_workshop = w.id_workshop
            WHERE wf.id_formation = :id
            ORDER BY w.id_workshop DESC
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $idFormation, PDO::PARAM_INT);
    $stmt->execute();
    $workshop = $stmt->fetch();

    if ($workshop) {
      return $workshop;
    }
  }

  if (tableExists($pdo, 'formations_workshops')) {
    $sql = $baseSelect . '
            INNER JOIN formations_workshops fw ON fw.id_workshop = w.id_workshop
            WHERE fw.id_formation = :id
            ORDER BY w.id_workshop DESC
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $idFormation, PDO::PARAM_INT);
    $stmt->execute();
    $workshop = $stmt->fetch();

    if ($workshop) {
      return $workshop;
    }
  }

  if (columnExists($pdo, 'workshops', 'id_formation')) {
    $sql = $baseSelect . '
            WHERE w.id_formation = :id
            ORDER BY w.id_workshop DESC
            LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $idFormation, PDO::PARAM_INT);
    $stmt->execute();
    $workshop = $stmt->fetch();

    if ($workshop) {
      return $workshop;
    }
  }

    return null;
}

$hasFormationsWorkshopsTable = tableExists($pdo, 'formations_workshops');
$hasWorkshopsFormationTable = tableExists($pdo, 'workshops_formation');
$hasWorkshopsFormationId = columnExists($pdo, 'workshops', 'id_formation');
$hasWorkshopsTable = tableExists($pdo, 'workshops');
$hasWorkshopsMentorIdColumn = columnExists($pdo, 'workshops', 'mentor_id');
$hasWorkshopsDateAtelierColumn = columnExists($pdo, 'workshops', 'date_atelier');
$hasWorkshopsDatePublicationColumn = columnExists($pdo, 'workshops', 'date_publication');
$hasWorkshopsStatusColumn = columnExists($pdo, 'workshops', 'statut');
$hasWorkshopsPlacesRestantesColumn = columnExists($pdo, 'workshops', 'places_restantes');
$workshopsAssociationEnabled = $hasFormationsWorkshopsTable || $hasWorkshopsFormationTable || $hasWorkshopsFormationId;

$hasQuizzFormationIdColumn = columnExists($pdo, 'quizz', 'formation_id');
$hasQuizzDomaineColumn = columnExists($pdo, 'quizz', 'domaine');
$hasQuizzTitreColumn = columnExists($pdo, 'quizz', 'titre');
$hasQuizzDescriptionColumn = columnExists($pdo, 'quizz', 'description');
$hasQuizzQuestionColumn = columnExists($pdo, 'quizz', 'question');
$hasQuizzNotePassageColumn = columnExists($pdo, 'quizz', 'note_passage');
$hasQuizzScoreColumn = columnExists($pdo, 'quizz', 'score');
$hasQuizzNbTentativesColumn = columnExists($pdo, 'quizz', 'nb_tentatives');
$hasQuizzDureeMinutesColumn = columnExists($pdo, 'quizz', 'duree_minutes');
$hasQuizzDureeColumn = columnExists($pdo, 'quizz', 'duree');
$hasQuizzFormationsTable = tableExists($pdo, 'quizz_formations');

$erreur = '';
$succes = '';
$afficherFormUpdate = false;
$updateForm = [
  'id_formation' => 0,
  'titre' => '',
  'description' => '',
  'mentor' => '',
  'niveau' => 'debutant',
  'duree' => '',
  'prix' => '',
  'certification' => 'oui',
  'lien_video' => '', 
  'etat' => 'Actif',
  'date_realisation' => '',
  'workshop_id' => 0,
  'workshop_titre' => '',
  'workshop_description' => '',
  'workshop_mentor_id' => '',
  'workshop_duree' => '',
  'workshop_date_atelier' => '',
  'workshop_lieu' => '',
  'workshop_places_max' => '',
  'workshop_prix' => '',
  'workshop_certification' => 'non',
  'workshop_statut' => 'a_venir',
  'quiz_id' => 0,
  'quiz_titre' => '',
  'quiz_description' => '',
  'quiz_questions' => '',
  'quiz_duree_minutes' => '20'

];

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
  $succes = 'Formation mise à jour avec succès.';
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
  $succes = 'Formation supprimée avec succès.';
}

$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
  $requestMethod = (string) $_SERVER['REQUEST_METHOD'];
}

if ($requestMethod === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';

  if ($action === 'update') {
    $afficherFormUpdate = true;

    $updateForm['id_formation'] = isset($_POST['id_formation']) ? (int) $_POST['id_formation'] : 0;
    $updateForm['titre'] = isset($_POST['titre']) ? normalizeFormationTitle($_POST['titre']) : '';
    $updateForm['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $updateForm['mentor'] = isset($_POST['mentor']) ? trim((string) $_POST['mentor']) : '';
    $updateForm['niveau'] = isset($_POST['niveau']) ? trim((string) $_POST['niveau']) : 'debutant';
    $updateForm['duree'] = isset($_POST['duree']) ? trim((string) $_POST['duree']) : '';
    $updateForm['prix'] = isset($_POST['prix']) ? trim((string) $_POST['prix']) : '';
    $updateForm['certification'] = isset($_POST['certification']) ? trim((string) $_POST['certification']) : 'oui';
    $updateForm['etat'] = isset($_POST['etat']) ? trim((string) $_POST['etat']) : 'Actif';
    $updateForm['lien_video'] = isset($_POST['lien_video']) ? trim((string) $_POST['lien_video']) : '';
    $updateForm['date_realisation'] = isset($_POST['date_realisation']) ? trim((string) $_POST['date_realisation']) : '';
    $updateForm['workshop_id'] = isset($_POST['workshop_id']) ? (int) $_POST['workshop_id'] : 0;
    $updateForm['workshop_titre'] = isset($_POST['workshop_titre']) ? trim((string) $_POST['workshop_titre']) : '';
    $updateForm['workshop_description'] = isset($_POST['workshop_description']) ? trim((string) $_POST['workshop_description']) : '';
    $updateForm['workshop_mentor_id'] = isset($_POST['workshop_mentor_id']) ? trim((string) $_POST['workshop_mentor_id']) : '';
    $updateForm['workshop_duree'] = isset($_POST['workshop_duree']) ? trim((string) $_POST['workshop_duree']) : '';
    $updateForm['workshop_date_atelier'] = isset($_POST['workshop_date_atelier']) ? trim((string) $_POST['workshop_date_atelier']) : '';
    $updateForm['workshop_lieu'] = isset($_POST['workshop_lieu']) ? trim((string) $_POST['workshop_lieu']) : '';
    $updateForm['workshop_places_max'] = isset($_POST['workshop_places_max']) ? trim((string) $_POST['workshop_places_max']) : '';
    $updateForm['workshop_prix'] = isset($_POST['workshop_prix']) ? trim((string) $_POST['workshop_prix']) : '';
    $updateForm['workshop_certification'] = isset($_POST['workshop_certification']) ? trim((string) $_POST['workshop_certification']) : 'non';
    $updateForm['workshop_statut'] = isset($_POST['workshop_statut']) ? trim((string) $_POST['workshop_statut']) : 'a_venir';
    $updateForm['quiz_id'] = isset($_POST['quiz_id']) ? (int) $_POST['quiz_id'] : 0;
    $updateForm['quiz_titre'] = isset($_POST['quiz_titre']) ? trim((string) $_POST['quiz_titre']) : '';
    $updateForm['quiz_description'] = isset($_POST['quiz_description']) ? trim((string) $_POST['quiz_description']) : '';
    $updateForm['quiz_note_passage'] = isset($_POST['quiz_note_passage']) ? trim((string) $_POST['quiz_note_passage']) : '60';
    $updateForm['quiz_questions'] = isset($_POST['quiz_questions']) ? trim((string) $_POST['quiz_questions']) : '';
    $updateForm['quiz_nb_tentatives'] = isset($_POST['quiz_nb_tentatives']) ? trim((string) $_POST['quiz_nb_tentatives']) : '3';
    $updateForm['quiz_duree_minutes'] = isset($_POST['quiz_duree_minutes']) ? trim((string) $_POST['quiz_duree_minutes']) : '20';

    $hasWorkshopUpdateInput = (
      $updateForm['workshop_id'] > 0 ||
      $updateForm['workshop_titre'] !== '' ||
      $updateForm['workshop_description'] !== '' ||
      $updateForm['workshop_mentor_id'] !== '' ||
      $updateForm['workshop_duree'] !== '' ||
      $updateForm['workshop_date_atelier'] !== '' ||
      $updateForm['workshop_lieu'] !== '' ||
      $updateForm['workshop_places_max'] !== '' ||
      $updateForm['workshop_prix'] !== ''
    );

    if ($updateForm['id_formation'] <= 0) {
      $erreur = 'Identifiant de formation invalide.';
    }

    if ($erreur === '' && ($updateForm['titre'] === '' || strlen($updateForm['titre']) < 3)) {
      $erreur = 'Le titre doit contenir au moins 3 caractères.';
    }

    if ($erreur === '' && $updateForm['mentor'] === '') {
      $erreur = 'Le mentor est obligatoire.';
    }

    $niveauDbUpdate = normalizeNiveau($updateForm['niveau']);
    if ($erreur === '' && $niveauDbUpdate === '') {
      $erreur = 'Le niveau sélectionné est invalide.';
    }

    if ($erreur === '' && ($updateForm['duree'] === '' || !ctype_digit($updateForm['duree']) || (int) $updateForm['duree'] <= 0)) {
      $erreur = 'La durée doit être un nombre entier supérieur à 0.';
    }

    if ($erreur === '' && !is_numeric($updateForm['prix'])) {
      $erreur = 'Le prix doit être un nombre valide.';
    }

    if ($erreur === '' && ($updateForm['quiz_titre'] === '' || strlen($updateForm['quiz_titre']) < 3)) {
      $erreur = 'Le titre du quiz doit contenir au moins 3 caractères.';
    }

    if ($erreur === '' && (!ctype_digit($updateForm['quiz_note_passage']) || (int) $updateForm['quiz_note_passage'] < 0 || (int) $updateForm['quiz_note_passage'] > 100)) {
      $erreur = 'La note de passage du quiz doit être un entier entre 0 et 100.';
    }

    if ($erreur === '' && (!ctype_digit($updateForm['quiz_nb_tentatives']) || (int) $updateForm['quiz_nb_tentatives'] <= 0)) {
      $erreur = 'Le nombre de tentatives du quiz doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && (!ctype_digit($updateForm['quiz_duree_minutes']) || (int) $updateForm['quiz_duree_minutes'] <= 0)) {
      $erreur = 'La durée du quiz doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && !$hasWorkshopsTable) {
      $erreur = 'La table workshops est introuvable en base de données.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && (int) $updateForm['workshop_id'] <= 0 && !$workshopsAssociationEnabled) {
      $erreur = 'Aucune liaison formation-workshop n\'est disponible dans la base de données.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && strlen($updateForm['workshop_titre']) < 3) {
      $erreur = 'Le titre du workshop doit contenir au moins 3 caractères.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && ($updateForm['workshop_description'] === '' || strlen($updateForm['workshop_description']) < 10)) {
      $erreur = 'La description du workshop doit contenir au moins 10 caractères.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && $updateForm['workshop_mentor_id'] !== '' && (!ctype_digit($updateForm['workshop_mentor_id']) || (int) $updateForm['workshop_mentor_id'] <= 0)) {
      $erreur = 'Le mentor_id du workshop doit être un entier positif.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && $updateForm['workshop_mentor_id'] !== '') {
      $mentorIdWorkshop = (int) $updateForm['workshop_mentor_id'];

      // Optional field: if id_user does not exist, save NULL to avoid FK errors.
      if (!userExistsById($pdo, $mentorIdWorkshop)) {
        $updateForm['workshop_mentor_id'] = '';
      }
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && (!ctype_digit($updateForm['workshop_duree']) || (int) $updateForm['workshop_duree'] <= 0)) {
      $erreur = 'La durée du workshop doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && $updateForm['workshop_date_atelier'] !== '' && strtotime($updateForm['workshop_date_atelier']) === false) {
      $erreur = 'La date atelier du workshop est invalide.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && ($updateForm['workshop_lieu'] === '' || strlen($updateForm['workshop_lieu']) < 2)) {
      $erreur = 'Le lieu du workshop est obligatoire (min. 2 caractères).';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && (!ctype_digit($updateForm['workshop_places_max']) || (int) $updateForm['workshop_places_max'] <= 0)) {
      $erreur = 'Le nombre de places max du workshop doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && !is_numeric($updateForm['workshop_prix'])) {
      $erreur = 'Le prix du workshop doit être un nombre valide.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && is_numeric($updateForm['workshop_prix']) && (float) $updateForm['workshop_prix'] < 0) {
      $erreur = 'Le prix du workshop doit être supérieur ou égal à 0.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && $updateForm['workshop_certification'] !== 'oui' && $updateForm['workshop_certification'] !== 'non') {
      $erreur = 'La certification du workshop est invalide.';
    }

    if ($erreur === '' && $hasWorkshopUpdateInput && $updateForm['workshop_statut'] !== 'a_venir' && $updateForm['workshop_statut'] !== 'en_cours' && $updateForm['workshop_statut'] !== 'termine' && $updateForm['workshop_statut'] !== 'annule') {
      $erreur = 'Le statut du workshop est invalide.';
    }

    if ($erreur === '' && $updateForm['certification'] !== 'oui' && $updateForm['certification'] !== 'non') {
      $erreur = 'La certification sélectionnée est invalide.';
    }

    if ($erreur === '' && $updateForm['etat'] !== 'Actif' && $updateForm['etat'] !== 'Inactif' && $updateForm['etat'] !== 'Brouillon') {
      $erreur = 'Le statut sélectionné est invalide.';
    }

    if ($erreur === '' && $updateForm['date_realisation'] !== '') {
      $dateUpdate = strtotime($updateForm['date_realisation']);
      if ($dateUpdate === false) {
        $erreur = 'La date de réalisation est invalide.';
      }
    }

    if ($erreur === '') {
      try {
        $pdo->beginTransaction();


        $sqlUpdate = 'UPDATE formations
                      SET domaine = :domaine,
                          formateur = :formateur,
                          description = :description,
                          date_realisation = :date_realisation,
                          etat = :etat,
                          duree = :duree,
                          certification = :certification,
                          niveau = :niveau,
                          prix = :prix,
                          lien_video = :lien_video
                      WHERE id_formation = :id_formation';
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':domaine', $updateForm['titre']);
        $stmtUpdate->bindValue(':formateur', $updateForm['mentor']);
        $stmtUpdate->bindValue(':description', $updateForm['description']);
        $stmtUpdate->bindValue(':lien_video', $updateForm['lien_video']);
        if ($updateForm['date_realisation'] === '') {
          $stmtUpdate->bindValue(':date_realisation', null, PDO::PARAM_NULL);
        } else {
          $stmtUpdate->bindValue(':date_realisation', $updateForm['date_realisation']);
        }
        $stmtUpdate->bindValue(':etat', $updateForm['etat']);
        $stmtUpdate->bindValue(':duree', (int) $updateForm['duree'], PDO::PARAM_INT);
        $stmtUpdate->bindValue(':certification', $updateForm['certification']);
        $stmtUpdate->bindValue(':niveau', $niveauDbUpdate);
        $stmtUpdate->bindValue(':prix', (float) $updateForm['prix']);
        $stmtUpdate->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
        $stmtUpdate->execute();

        $quizIdUpdate = (int) $updateForm['quiz_id'];
        $quizDescriptionUpdate = $updateForm['quiz_description'];
        $quizQuestionUpdate = $quizDescriptionUpdate;
        if ($quizQuestionUpdate === '') {
          $quizQuestionUpdate = $updateForm['quiz_titre'];
        }

        if ($quizIdUpdate > 0) {
          $quizSetParts = [];

          if ($hasQuizzFormationIdColumn) {
            $quizSetParts[] = 'formation_id = :formation_id';
          }
          if ($hasQuizzDomaineColumn) {
            $quizSetParts[] = 'domaine = :domaine';
          }
          if ($hasQuizzTitreColumn) {
            $quizSetParts[] = 'titre = :titre';
          }
          if ($hasQuizzDescriptionColumn) {
            $quizSetParts[] = 'description = :description';
          }
          if ($hasQuizzQuestionColumn) {
            $quizSetParts[] = 'question = :question';
          }
          if ($hasQuizzNotePassageColumn) {
            $quizSetParts[] = 'note_passage = :note_passage';
          } elseif ($hasQuizzScoreColumn) {
            $quizSetParts[] = 'score = :score';
          }
          if ($hasQuizzNbTentativesColumn) {
            $quizSetParts[] = 'nb_tentatives = :nb_tentatives';
          }
          if (columnExists($pdo, 'quizz', 'questions')) {
            $quizSetParts[] = 'questions = :questions';
          }
          if ($hasQuizzDureeMinutesColumn) {
            $quizSetParts[] = 'duree_minutes = :duree_minutes';
          } elseif ($hasQuizzDureeColumn) {
            $quizSetParts[] = 'duree = :duree';
          }

          if (count($quizSetParts) > 0) {
            $sqlQuizUpdate = 'UPDATE quizz
                              SET ' . implode(",\n                                  ", $quizSetParts) . '
                              WHERE id_quizz = :id_quizz';
            $stmtQuizUpdate = $pdo->prepare($sqlQuizUpdate);

            if ($hasQuizzFormationIdColumn) {
              $stmtQuizUpdate->bindValue(':formation_id', $updateForm['id_formation'], PDO::PARAM_INT);
            }
            if ($hasQuizzDomaineColumn) {
              $stmtQuizUpdate->bindValue(':domaine', $updateForm['titre']);
            }
            if ($hasQuizzTitreColumn) {
              $stmtQuizUpdate->bindValue(':titre', $updateForm['quiz_titre']);
            }
            if ($hasQuizzDescriptionColumn) {
              if ($quizDescriptionUpdate === '') {
                $stmtQuizUpdate->bindValue(':description', null, PDO::PARAM_NULL);
              } else {
                $stmtQuizUpdate->bindValue(':description', $quizDescriptionUpdate);
              }
            }
            if ($hasQuizzQuestionColumn) {
              $stmtQuizUpdate->bindValue(':question', $quizQuestionUpdate);
            }
            if ($hasQuizzNotePassageColumn) {
              $stmtQuizUpdate->bindValue(':note_passage', (int) $updateForm['quiz_note_passage'], PDO::PARAM_INT);
            } elseif ($hasQuizzScoreColumn) {
              $stmtQuizUpdate->bindValue(':score', (int) $updateForm['quiz_note_passage'], PDO::PARAM_INT);
            }
            if ($hasQuizzNbTentativesColumn) {
              $stmtQuizUpdate->bindValue(':nb_tentatives', (int) $updateForm['quiz_nb_tentatives'], PDO::PARAM_INT);
            }
            if (columnExists($pdo, 'quizz', 'questions')) {
              $stmtQuizUpdate->bindValue(':questions', $updateForm['quiz_questions']);
            }
            if ($hasQuizzDureeMinutesColumn) {
              $stmtQuizUpdate->bindValue(':duree_minutes', (int) $updateForm['quiz_duree_minutes'], PDO::PARAM_INT);
            } elseif ($hasQuizzDureeColumn) {
              $stmtQuizUpdate->bindValue(':duree', (int) $updateForm['quiz_duree_minutes'], PDO::PARAM_INT);
            }

            $stmtQuizUpdate->bindValue(':id_quizz', $quizIdUpdate, PDO::PARAM_INT);
            $stmtQuizUpdate->execute();
          }
        } else {
          $quizInsertColumns = [];
          $quizInsertValues = [];

          if ($hasQuizzFormationIdColumn) {
            $quizInsertColumns[] = 'formation_id';
            $quizInsertValues[] = ':formation_id';
          }
          if ($hasQuizzDomaineColumn) {
            $quizInsertColumns[] = 'domaine';
            $quizInsertValues[] = ':domaine';
          }
          if ($hasQuizzTitreColumn) {
            $quizInsertColumns[] = 'titre';
            $quizInsertValues[] = ':titre';
          }
          if ($hasQuizzDescriptionColumn) {
            $quizInsertColumns[] = 'description';
            $quizInsertValues[] = ':description';
          }
          if ($hasQuizzQuestionColumn) {
            $quizInsertColumns[] = 'question';
            $quizInsertValues[] = ':question';
          }
          if ($hasQuizzNotePassageColumn) {
            $quizInsertColumns[] = 'note_passage';
            $quizInsertValues[] = ':note_passage';
          } elseif ($hasQuizzScoreColumn) {
            $quizInsertColumns[] = 'score';
            $quizInsertValues[] = ':score';
          }
          if ($hasQuizzNbTentativesColumn) {
            $quizInsertColumns[] = 'nb_tentatives';
            $quizInsertValues[] = ':nb_tentatives';
          }
          if ($hasQuizzDureeMinutesColumn) {
            $quizInsertColumns[] = 'duree_minutes';
            $quizInsertValues[] = ':duree_minutes';
          } elseif ($hasQuizzDureeColumn) {
            $quizInsertColumns[] = 'duree';
            $quizInsertValues[] = ':duree';
          }

          if (count($quizInsertColumns) > 0) {
            $sqlQuizInsert = 'INSERT INTO quizz (' . implode(', ', $quizInsertColumns) . ')
                              VALUES (' . implode(', ', $quizInsertValues) . ')';
            $stmtQuizInsert = $pdo->prepare($sqlQuizInsert);

            if ($hasQuizzFormationIdColumn) {
              $stmtQuizInsert->bindValue(':formation_id', $updateForm['id_formation'], PDO::PARAM_INT);
            }
            if ($hasQuizzDomaineColumn) {
              $stmtQuizInsert->bindValue(':domaine', $updateForm['titre']);
            }
            if ($hasQuizzTitreColumn) {
              $stmtQuizInsert->bindValue(':titre', $updateForm['quiz_titre']);
            }
            if ($hasQuizzDescriptionColumn) {
              if ($quizDescriptionUpdate === '') {
                $stmtQuizInsert->bindValue(':description', null, PDO::PARAM_NULL);
              } else {
                $stmtQuizInsert->bindValue(':description', $quizDescriptionUpdate);
              }
            }
            if ($hasQuizzQuestionColumn) {
              $stmtQuizInsert->bindValue(':question', $quizQuestionUpdate);
            }
            if ($hasQuizzNotePassageColumn) {
              $stmtQuizInsert->bindValue(':note_passage', (int) $updateForm['quiz_note_passage'], PDO::PARAM_INT);
            } elseif ($hasQuizzScoreColumn) {
              $stmtQuizInsert->bindValue(':score', (int) $updateForm['quiz_note_passage'], PDO::PARAM_INT);
            }
            if ($hasQuizzNbTentativesColumn) {
              $stmtQuizInsert->bindValue(':nb_tentatives', (int) $updateForm['quiz_nb_tentatives'], PDO::PARAM_INT);
            }
            if ($hasQuizzDureeMinutesColumn) {
              $stmtQuizInsert->bindValue(':duree_minutes', (int) $updateForm['quiz_duree_minutes'], PDO::PARAM_INT);
            } elseif ($hasQuizzDureeColumn) {
              $stmtQuizInsert->bindValue(':duree', (int) $updateForm['quiz_duree_minutes'], PDO::PARAM_INT);
            }

            $stmtQuizInsert->execute();
            $quizIdUpdate = (int) $pdo->lastInsertId();
          }
        }

        if ($hasQuizzFormationsTable && $quizIdUpdate > 0) {
          $sqlCheckLien = 'SELECT COUNT(*) AS total
                           FROM quizz_formations
                           WHERE id_formation = :id_formation AND id_quizz = :id_quizz';
          $stmtCheckLien = $pdo->prepare($sqlCheckLien);
          $stmtCheckLien->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
          $stmtCheckLien->bindValue(':id_quizz', $quizIdUpdate, PDO::PARAM_INT);
          $stmtCheckLien->execute();
          $rowCheckLien = $stmtCheckLien->fetch();

          $nbLiens = 0;
          if ($rowCheckLien && isset($rowCheckLien['total']) && is_numeric($rowCheckLien['total'])) {
            $nbLiens = (int) $rowCheckLien['total'];
          }

          if ($nbLiens === 0) {
            $sqlInsertLien = 'INSERT INTO quizz_formations (id_formation, id_quizz)
                              VALUES (:id_formation, :id_quizz)';
            $stmtInsertLien = $pdo->prepare($sqlInsertLien);
            $stmtInsertLien->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
            $stmtInsertLien->bindValue(':id_quizz', $quizIdUpdate, PDO::PARAM_INT);
            $stmtInsertLien->execute();
          }
        }

        if ($hasWorkshopUpdateInput) {
          $workshopIdUpdate = (int) $updateForm['workshop_id'];
          $workshopDateAtelierSql = null;
          if ($updateForm['workshop_date_atelier'] !== '') {
            $workshopDateAtelierSql = date('Y-m-d H:i:s', strtotime($updateForm['workshop_date_atelier']));
          }

          if ($workshopIdUpdate > 0) {
            $workshopUpdateColumns = [
              'titre = :workshop_titre',
              'description = :workshop_description',
              'duree = :workshop_duree',
              'lieu = :workshop_lieu',
              'places_max = :workshop_places_max',
              'prix = :workshop_prix',
              'certification = :workshop_certification'
            ];

            if ($hasWorkshopsPlacesRestantesColumn) {
              $workshopUpdateColumns[] = 'places_restantes = :workshop_places_restantes';
            }
            if ($hasWorkshopsMentorIdColumn) {
              $workshopUpdateColumns[] = 'mentor_id = :workshop_mentor_id';
            }
            if ($hasWorkshopsDateAtelierColumn) {
              $workshopUpdateColumns[] = 'date_atelier = :workshop_date_atelier';
            }
            if ($hasWorkshopsStatusColumn) {
              $workshopUpdateColumns[] = 'statut = :workshop_statut';
            }

            $sqlWorkshopUpdate = 'UPDATE workshops
                                  SET ' . implode(",\n                                      ", $workshopUpdateColumns) . '
                                  WHERE id_workshop = :workshop_id';
            $stmtWorkshopUpdate = $pdo->prepare($sqlWorkshopUpdate);
            $stmtWorkshopUpdate->bindValue(':workshop_titre', $updateForm['workshop_titre']);
            $stmtWorkshopUpdate->bindValue(':workshop_description', $updateForm['workshop_description']);
            $stmtWorkshopUpdate->bindValue(':workshop_duree', (int) $updateForm['workshop_duree'], PDO::PARAM_INT);
            $stmtWorkshopUpdate->bindValue(':workshop_lieu', $updateForm['workshop_lieu']);
            $stmtWorkshopUpdate->bindValue(':workshop_places_max', (int) $updateForm['workshop_places_max'], PDO::PARAM_INT);
            $stmtWorkshopUpdate->bindValue(':workshop_prix', (float) $updateForm['workshop_prix']);
            $stmtWorkshopUpdate->bindValue(':workshop_certification', $updateForm['workshop_certification']);

            if ($hasWorkshopsPlacesRestantesColumn) {
              $stmtWorkshopUpdate->bindValue(':workshop_places_restantes', (int) $updateForm['workshop_places_max'], PDO::PARAM_INT);
            }
            if ($hasWorkshopsMentorIdColumn) {
              if ($updateForm['workshop_mentor_id'] === '') {
                $stmtWorkshopUpdate->bindValue(':workshop_mentor_id', null, PDO::PARAM_NULL);
              } else {
                $stmtWorkshopUpdate->bindValue(':workshop_mentor_id', (int) $updateForm['workshop_mentor_id'], PDO::PARAM_INT);
              }
            }
            if ($hasWorkshopsDateAtelierColumn) {
              if ($workshopDateAtelierSql === null) {
                $stmtWorkshopUpdate->bindValue(':workshop_date_atelier', null, PDO::PARAM_NULL);
              } else {
                $stmtWorkshopUpdate->bindValue(':workshop_date_atelier', $workshopDateAtelierSql);
              }
            }
            if ($hasWorkshopsStatusColumn) {
              $stmtWorkshopUpdate->bindValue(':workshop_statut', $updateForm['workshop_statut']);
            }

            $stmtWorkshopUpdate->bindValue(':workshop_id', $workshopIdUpdate, PDO::PARAM_INT);
            $stmtWorkshopUpdate->execute();
          } else {
            $workshopInsertColumns = ['titre', 'description', 'duree', 'lieu', 'places_max', 'prix', 'certification'];
            $workshopInsertValues = [':workshop_titre', ':workshop_description', ':workshop_duree', ':workshop_lieu', ':workshop_places_max', ':workshop_prix', ':workshop_certification'];

            if ($hasWorkshopsMentorIdColumn) {
              $workshopInsertColumns[] = 'mentor_id';
              $workshopInsertValues[] = ':workshop_mentor_id';
            }
            if ($hasWorkshopsDatePublicationColumn) {
              $workshopInsertColumns[] = 'date_publication';
              $workshopInsertValues[] = 'CURDATE()';
            }
            if ($hasWorkshopsDateAtelierColumn) {
              $workshopInsertColumns[] = 'date_atelier';
              $workshopInsertValues[] = ':workshop_date_atelier';
            }
            if ($hasWorkshopsPlacesRestantesColumn) {
              $workshopInsertColumns[] = 'places_restantes';
              $workshopInsertValues[] = ':workshop_places_restantes';
            }
            if ($hasWorkshopsStatusColumn) {
              $workshopInsertColumns[] = 'statut';
              $workshopInsertValues[] = ':workshop_statut';
            }

            $sqlWorkshopInsert = 'INSERT INTO workshops (' . implode(', ', $workshopInsertColumns) . ')
                                  VALUES (' . implode(', ', $workshopInsertValues) . ')';
            $stmtWorkshopInsert = $pdo->prepare($sqlWorkshopInsert);
            $stmtWorkshopInsert->bindValue(':workshop_titre', $updateForm['workshop_titre']);
            $stmtWorkshopInsert->bindValue(':workshop_description', $updateForm['workshop_description']);
            $stmtWorkshopInsert->bindValue(':workshop_duree', (int) $updateForm['workshop_duree'], PDO::PARAM_INT);
            $stmtWorkshopInsert->bindValue(':workshop_lieu', $updateForm['workshop_lieu']);
            $stmtWorkshopInsert->bindValue(':workshop_places_max', (int) $updateForm['workshop_places_max'], PDO::PARAM_INT);
            $stmtWorkshopInsert->bindValue(':workshop_prix', (float) $updateForm['workshop_prix']);
            $stmtWorkshopInsert->bindValue(':workshop_certification', $updateForm['workshop_certification']);

            if ($hasWorkshopsMentorIdColumn) {
              if ($updateForm['workshop_mentor_id'] === '') {
                $stmtWorkshopInsert->bindValue(':workshop_mentor_id', null, PDO::PARAM_NULL);
              } else {
                $stmtWorkshopInsert->bindValue(':workshop_mentor_id', (int) $updateForm['workshop_mentor_id'], PDO::PARAM_INT);
              }
            }
            if ($hasWorkshopsDateAtelierColumn) {
              if ($workshopDateAtelierSql === null) {
                $stmtWorkshopInsert->bindValue(':workshop_date_atelier', null, PDO::PARAM_NULL);
              } else {
                $stmtWorkshopInsert->bindValue(':workshop_date_atelier', $workshopDateAtelierSql);
              }
            }
            if ($hasWorkshopsPlacesRestantesColumn) {
              $stmtWorkshopInsert->bindValue(':workshop_places_restantes', (int) $updateForm['workshop_places_max'], PDO::PARAM_INT);
            }
            if ($hasWorkshopsStatusColumn) {
              $stmtWorkshopInsert->bindValue(':workshop_statut', $updateForm['workshop_statut']);
            }

            $stmtWorkshopInsert->execute();
            $workshopIdUpdate = (int) $pdo->lastInsertId();
          }

          if ($workshopIdUpdate > 0) {
            if ($hasWorkshopsFormationTable) {
              $sqlWorkshopLinkCheck = 'SELECT COUNT(*) AS total
                                       FROM workshops_formation
                                       WHERE id_formation = :id_formation AND id_workshop = :id_workshop';
              $stmtWorkshopLinkCheck = $pdo->prepare($sqlWorkshopLinkCheck);
              $stmtWorkshopLinkCheck->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
              $stmtWorkshopLinkCheck->bindValue(':id_workshop', $workshopIdUpdate, PDO::PARAM_INT);
              $stmtWorkshopLinkCheck->execute();
              $workshopLinkRow = $stmtWorkshopLinkCheck->fetch();

              $workshopLinkCount = 0;
              if ($workshopLinkRow && isset($workshopLinkRow['total']) && is_numeric($workshopLinkRow['total'])) {
                $workshopLinkCount = (int) $workshopLinkRow['total'];
              }

              if ($workshopLinkCount === 0) {
                $sqlWorkshopLinkInsert = 'INSERT INTO workshops_formation (id_formation, id_workshop)
                                          VALUES (:id_formation, :id_workshop)';
                $stmtWorkshopLinkInsert = $pdo->prepare($sqlWorkshopLinkInsert);
                $stmtWorkshopLinkInsert->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
                $stmtWorkshopLinkInsert->bindValue(':id_workshop', $workshopIdUpdate, PDO::PARAM_INT);
                $stmtWorkshopLinkInsert->execute();
              }
            } elseif ($hasFormationsWorkshopsTable) {
              $sqlWorkshopLinkCheck = 'SELECT COUNT(*) AS total
                                       FROM formations_workshops
                                       WHERE id_formation = :id_formation AND id_workshop = :id_workshop';
              $stmtWorkshopLinkCheck = $pdo->prepare($sqlWorkshopLinkCheck);
              $stmtWorkshopLinkCheck->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
              $stmtWorkshopLinkCheck->bindValue(':id_workshop', $workshopIdUpdate, PDO::PARAM_INT);
              $stmtWorkshopLinkCheck->execute();
              $workshopLinkRow = $stmtWorkshopLinkCheck->fetch();

              $workshopLinkCount = 0;
              if ($workshopLinkRow && isset($workshopLinkRow['total']) && is_numeric($workshopLinkRow['total'])) {
                $workshopLinkCount = (int) $workshopLinkRow['total'];
              }

              if ($workshopLinkCount === 0) {
                $sqlWorkshopLinkInsert = 'INSERT INTO formations_workshops (id_formation, id_workshop)
                                          VALUES (:id_formation, :id_workshop)';
                $stmtWorkshopLinkInsert = $pdo->prepare($sqlWorkshopLinkInsert);
                $stmtWorkshopLinkInsert->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
                $stmtWorkshopLinkInsert->bindValue(':id_workshop', $workshopIdUpdate, PDO::PARAM_INT);
                $stmtWorkshopLinkInsert->execute();
              }
            } elseif ($hasWorkshopsFormationId) {
              $sqlWorkshopLinkUpdate = 'UPDATE workshops
                                        SET id_formation = :id_formation
                                        WHERE id_workshop = :id_workshop';
              $stmtWorkshopLinkUpdate = $pdo->prepare($sqlWorkshopLinkUpdate);
              $stmtWorkshopLinkUpdate->bindValue(':id_formation', $updateForm['id_formation'], PDO::PARAM_INT);
              $stmtWorkshopLinkUpdate->bindValue(':id_workshop', $workshopIdUpdate, PDO::PARAM_INT);
              $stmtWorkshopLinkUpdate->execute();
            }
          }
        }

        $pdo->commit();

        header('Location: back.php?updated=1');
        exit;
      } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $erreur = 'Erreur base de données pendant la mise à jour.';
      }
    }
  } elseif ($action === 'delete') {
    $idFormation = 0;
    if (isset($_POST['id_formation'])) {
      $idFormation = (int) $_POST['id_formation'];
    }

    if ($idFormation <= 0) {
      $erreur = 'Identifiant de formation invalide.';
    }

    if ($erreur === '') {
      try {
        $sqlDelete = 'DELETE FROM formations WHERE id_formation = :id';
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->bindValue(':id', $idFormation, PDO::PARAM_INT);
        $stmtDelete->execute();

        header('Location: back.php?deleted=1');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant la suppression.';
      }
    }
  }
}

if ($afficherFormUpdate === false) {
  $idFormationEditGet = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;

  if ($idFormationEditGet > 0) {
    try {
      $formationEdit = getFormationById($pdo, $idFormationEditGet);

      if ($formationEdit) {
        $niveauEdit = normalizeNiveau((string) $formationEdit['niveau']);
        if ($niveauEdit === '') {
          $niveauEdit = 'debutant';
        }

        $afficherFormUpdate = true;
        $updateForm['id_formation'] = (int) $formationEdit['id_formation'];
        $updateForm['titre'] = trim((string) $formationEdit['domaine']);
        $updateForm['description'] = trim((string) $formationEdit['description']);
        $updateForm['mentor'] = trim((string) $formationEdit['formateur']);
        $updateForm['niveau'] = $niveauEdit;
        $updateForm['duree'] = (string) ((int) $formationEdit['duree']);
        $updateForm['prix'] = (string) $formationEdit['prix'];
        $updateForm['quiz_questions'] = trim((string) ($formationEdit['quiz_questions'] ?? ''));
        $updateForm['certification'] = certifOui($formationEdit['certification']) ? 'oui' : 'non';
        $updateForm['etat'] = trim((string) $formationEdit['etat']);
        if ($updateForm['etat'] !== 'Actif' && $updateForm['etat'] !== 'Inactif' && $updateForm['etat'] !== 'Brouillon') {
          $updateForm['etat'] = 'Actif';
        }
        $updateForm['date_realisation'] = trim((string) $formationEdit['date_realisation']);
        $updateForm['quiz_id'] = 0;
        if (isset($formationEdit['quiz_id']) && is_numeric($formationEdit['quiz_id'])) {
          $updateForm['quiz_id'] = (int) $formationEdit['quiz_id'];
        }
        $updateForm['quiz_titre'] = trim((string) $formationEdit['quiz_titre']);
        $updateForm['quiz_description'] = trim((string) $formationEdit['quiz_description']);
        $updateForm['quiz_note_passage'] = '60';
        if (isset($formationEdit['quiz_note_passage']) && is_numeric($formationEdit['quiz_note_passage'])) {
          $updateForm['quiz_note_passage'] = (string) ((int) $formationEdit['quiz_note_passage']);
        }
        $updateForm['quiz_nb_tentatives'] = '3';
        if (isset($formationEdit['quiz_nb_tentatives']) && is_numeric($formationEdit['quiz_nb_tentatives'])) {
          $updateForm['quiz_nb_tentatives'] = (string) ((int) $formationEdit['quiz_nb_tentatives']);
        }
        $updateForm['quiz_duree_minutes'] = '20';
        if (isset($formationEdit['quiz_duree_minutes']) && is_numeric($formationEdit['quiz_duree_minutes'])) {
          $updateForm['quiz_duree_minutes'] = (string) ((int) $formationEdit['quiz_duree_minutes']);
        }

        $workshopEdit = getWorkshopByFormationId($pdo, $idFormationEditGet);
        if ($workshopEdit) {
          $updateForm['workshop_id'] = (int) $workshopEdit['id_workshop'];
          $updateForm['workshop_titre'] = trim((string) $workshopEdit['titre']);
          $updateForm['workshop_description'] = trim((string) $workshopEdit['description']);
          $updateForm['workshop_mentor_id'] = '';
          if (isset($workshopEdit['mentor_id']) && is_numeric($workshopEdit['mentor_id']) && (int) $workshopEdit['mentor_id'] > 0) {
            $updateForm['workshop_mentor_id'] = (string) ((int) $workshopEdit['mentor_id']);
          }
          $updateForm['workshop_duree'] = '';
          if (isset($workshopEdit['duree']) && is_numeric($workshopEdit['duree']) && (int) $workshopEdit['duree'] > 0) {
            $updateForm['workshop_duree'] = (string) ((int) $workshopEdit['duree']);
          }
          $updateForm['lien_video'] = trim((string) ($formationEdit['lien_video'] ?? ''));
          $updateForm['workshop_date_atelier'] = '';
          $workshopDateAtelier = trim((string) $workshopEdit['date_atelier']);
          if ($workshopDateAtelier !== '') {
            $workshopDateTimestamp = strtotime($workshopDateAtelier);
            if ($workshopDateTimestamp !== false) {
              $updateForm['workshop_date_atelier'] = date('Y-m-d\TH:i', $workshopDateTimestamp);
            }
          }
          $updateForm['workshop_lieu'] = trim((string) $workshopEdit['lieu']);
          $updateForm['workshop_places_max'] = '';
          if (isset($workshopEdit['places_max']) && is_numeric($workshopEdit['places_max']) && (int) $workshopEdit['places_max'] > 0) {
            $updateForm['workshop_places_max'] = (string) ((int) $workshopEdit['places_max']);
          }
          $updateForm['workshop_prix'] = '';
          if (isset($workshopEdit['prix']) && is_numeric($workshopEdit['prix'])) {
            $updateForm['workshop_prix'] = (string) ((float) $workshopEdit['prix']);
          }
          $updateForm['workshop_certification'] = certifOui($workshopEdit['certification']) ? 'oui' : 'non';
          $updateForm['workshop_statut'] = trim((string) $workshopEdit['statut']);
          if ($updateForm['workshop_statut'] !== 'a_venir' && $updateForm['workshop_statut'] !== 'en_cours' && $updateForm['workshop_statut'] !== 'termine' && $updateForm['workshop_statut'] !== 'annule') {
            $updateForm['workshop_statut'] = 'a_venir';
          }
        }
      } else {
        $erreur = 'Formation introuvable pour modification.';
      }
    } catch (PDOException $e) {
      $erreur = 'Erreur base de données lors du chargement de la formation.';
    }
  }
}

$recherche = isset($_GET['q']) ? trim($_GET['q']) : '';
$niveauFiltre = isset($_GET['niveau']) ? trim($_GET['niveau']) : '';
$niveauFiltreDb = '';

if ($niveauFiltre !== '') {
    $niveauFiltreDb = normalizeNiveau($niveauFiltre);
}

$selectQuizNotePassageExpr = 'NULL AS quiz_note_passage';
if (columnExists($pdo, 'quizz', 'note_passage')) {
    $selectQuizNotePassageExpr = 'qz.note_passage AS quiz_note_passage';
} elseif (columnExists($pdo, 'quizz', 'score')) {
    $selectQuizNotePassageExpr = 'qz.score AS quiz_note_passage';
}

$quizLinksParts = [];
if (columnExists($pdo, 'quizz', 'formation_id')) {
    $quizLinksParts[] = 'SELECT q.formation_id AS id_formation, q.id_quizz
                         FROM quizz q
                         WHERE q.formation_id IS NOT NULL';
}
if (tableExists($pdo, 'quizz_formations')) {
    $quizLinksParts[] = 'SELECT qf.id_formation, qf.id_quizz
                         FROM quizz_formations qf';
}

$inscritsExpr = '0 AS inscrits';
if (tableExists($pdo, 'inscriptions')) {
    $inscritsExpr = '(SELECT COUNT(*) FROM inscriptions i WHERE i.id_formation = f.id_formation) AS inscrits';
}

$hasQuizJoin = count($quizLinksParts) > 0;

$sql = '';
if ($hasQuizJoin) {
    $sql = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix,
                   qz.id_quizz AS quiz_id, qz.titre AS quiz_titre, ' . $selectQuizNotePassageExpr . ',
                   ' . $inscritsExpr . '
            FROM formations f
            LEFT JOIN (
              SELECT liens.id_formation, MIN(liens.id_quizz) AS id_quizz
              FROM (
                ' . implode(' UNION ALL ', $quizLinksParts) . '
              ) liens
              GROUP BY liens.id_formation
            ) lq ON lq.id_formation = f.id_formation
            LEFT JOIN quizz qz ON qz.id_quizz = lq.id_quizz';
} else {
    $sql = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix,
                   NULL AS quiz_id, NULL AS quiz_titre, NULL AS quiz_note_passage,
                   ' . $inscritsExpr . '
            FROM formations f';
}

$conditions = [];
$params = [];

if ($recherche !== '') {
  if ($hasQuizJoin) {
    $conditions[] = '(f.domaine LIKE :q OR f.formateur LIKE :q OR f.description LIKE :q OR qz.titre LIKE :q)';
  } else {
    $conditions[] = '(f.domaine LIKE :q OR f.formateur LIKE :q OR f.description LIKE :q)';
  }
    $params[':q'] = '%' . $recherche . '%';
}

if ($niveauFiltreDb !== '') {
    $conditions[] = 'f.niveau = :niveau';
    $params[':niveau'] = $niveauFiltreDb;
}

if (count($conditions) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY f.id_formation DESC';

$formations = [];

try {
    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $formations = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Erreur lors du chargement des formations.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BackOffice Formations | CraftLink</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Calibri:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../view/backoffice.css">
<style>
.inline-alert {
  margin-bottom: 16px;
  padding: 10px 12px;
  border-radius: 6px;
  font-size: 0.9rem;
}
.inline-alert.error {
  background: rgba(192, 57, 43, 0.12);
  color: #7b1f15;
  border: 1px solid rgba(192, 57, 43, 0.35);
}
.inline-alert.success {
  background: rgba(46, 107, 62, 0.12);
  color: #1f4d2b;
  border: 1px solid rgba(46, 107, 62, 0.35);
}
.simple-panel {
  background: var(--blanc);
  border: 1px solid rgba(196,154,108,0.2);
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 18px;
  box-shadow: 0 1px 8px rgba(59,35,20,0.05);
}
.simple-panel h3 {
  font-family: 'Playfair Display', serif;
  margin-bottom: 14px;
}
.simple-actions {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.btn-link {
  text-decoration: none;
}
.form-subtitle {
  margin: 8px 0 8px;
  color: var(--marron);
  font-weight: 700;
  font-size: 0.9rem;
}
.form-note {
  margin-top: 6px;
  font-size: 0.78rem;
  color: var(--gris);
}
.action-cell {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}
.action-cell form {
  margin: 0;
}
.form-feedback {
  display: none;
  margin: 10px 0;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 0.8rem;
}
.form-feedback.show {
  display: block;
}
.form-feedback.error {
  background: rgba(192, 57, 43, 0.12);
  color: #7b1f15;
  border: 1px solid rgba(192, 57, 43, 0.35);
}
.form-feedback.success {
  background: rgba(46, 107, 62, 0.12);
  color: #1f4d2b;
  border: 1px solid rgba(46, 107, 62, 0.35);
}
.simple-panel .input-error,
.toolbar .input-error {
  border-color: #c0392b !important;
  background: rgba(192, 57, 43, 0.06);
}
@media (max-width: 800px) {
  .form-row-2,
  .form-row-3 {
    grid-template-columns: 1fr;
  }
}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo">
    <h1>ح CraftLink</h1>
    <p>Administration</p>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section-label">Modules</div>
    <a class="nav-item active" href="back.php"><span class="icon">📜</span> Formations</a>
    <a class="nav-item" href="front.php"><span class="icon">🌐</span> Front Formations</a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-info">
      <div class="admin-avatar">AD</div>
      <div>
        <div class="admin-name">Admin CraftLink</div>
        <div class="admin-role">Super Administrateur</div>
      </div>
    </div>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="breadcrumb">
      Modules &rsaquo; <strong>Formations </strong>
    </div>
    <div class="topbar-actions simple-actions">
      <a class="btn-primary btn-link" href="front.php?open_add=1">＋ Ajouter une formation</a>
      <a class="btn-primary btn-link" href="front.php">Return</a>
    </div>
  </div>

  <div class="content">
    <?php if ($erreur !== ''): ?>
      <div class="inline-alert error"><?php echo e($erreur); ?></div>
    <?php endif; ?>

    <?php if ($succes !== ''): ?>
      <div class="inline-alert success"><?php echo e($succes); ?></div>
    <?php endif; ?>

    <?php if ($afficherFormUpdate): ?>
      <div class="simple-panel">
        <h3>Modifier la formation #<?php echo e((string) $updateForm['id_formation']); ?></h3>
        <form method="post" action="back.php" id="formBackUpdateFormation" novalidate>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id_formation" value="<?php echo e((string) $updateForm['id_formation']); ?>">
          <input type="hidden" name="workshop_id" value="<?php echo e((string) $updateForm['workshop_id']); ?>">
          <input type="hidden" name="quiz_id" value="<?php echo e((string) $updateForm['quiz_id']); ?>">
          <div class="form-feedback" data-form-message></div>

          <div class="form-row-2">
            <div class="form-group">
              <label>Titre *</label>
              <input type="text" name="titre" value="<?php echo e($updateForm['titre']); ?>" placeholder="Ex: Initiation à la poterie" required>
            </div>
            <div class="form-group">
              <label>Mentor *</label>
              <input type="text" name="mentor" value="<?php echo e($updateForm['mentor']); ?>" placeholder="Ex: Fatma Ayari" required>
            </div>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="3" placeholder="Description de la formation"><?php echo e($updateForm['description']); ?></textarea>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Niveau *</label>
              <select name="niveau">
                <option value="debutant" <?php echo ($updateForm['niveau'] === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
                <option value="intermediaire" <?php echo ($updateForm['niveau'] === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
                <option value="avance" <?php echo ($updateForm['niveau'] === 'avance' ? 'selected' : ''); ?>>Avancé</option>
              </select>
            </div>
            <div class="form-group">
              <label>Durée (heures) *</label>
              <input type="text" name="duree" value="<?php echo e($updateForm['duree']); ?>" placeholder="Ex: 24" required>
            </div>
            <div class="form-group">
              <label>Prix (TND) *</label>
              <input type="text" name="prix" value="<?php echo e($updateForm['prix']); ?>" placeholder="Ex: 180" required>
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Certification</label>
              <select name="certification">
                <option value="oui" <?php echo ($updateForm['certification'] === 'oui' ? 'selected' : ''); ?>>Oui</option>
                <option value="non" <?php echo ($updateForm['certification'] === 'non' ? 'selected' : ''); ?>>Non</option>
              </select>
            </div>
            <div class="form-group">
              <label>Statut</label>
              <select name="etat">
                <option value="Actif" <?php echo ($updateForm['etat'] === 'Actif' ? 'selected' : ''); ?>>Actif</option>
                <option value="Inactif" <?php echo ($updateForm['etat'] === 'Inactif' ? 'selected' : ''); ?>>Inactif</option>
                <option value="Brouillon" <?php echo ($updateForm['etat'] === 'Brouillon' ? 'selected' : ''); ?>>Brouillon</option>
              </select>
              <div class="form-group">
                <label>Lien vidéo (optionnel)</label>
                <input type="url" name="lien_video" value="<?php echo e($updateForm['lien_video']); ?>" placeholder="https://...">
              </div>
            </div>
            <div class="form-group">
              <label>Date de réalisation</label>
              <input type="date" name="date_realisation" value="<?php echo e($updateForm['date_realisation']); ?>">
            </div>
          </div>

          <div class="form-subtitle">Workshop lié à la formation (optionnel)</div>
          
          <div class="form-row-2">
            <div class="form-group">
              <label>Titre workshop</label>
              <input type="text" name="workshop_titre" value="<?php echo e($updateForm['workshop_titre']); ?>" placeholder="Ex: Atelier pratique de poterie">
            </div>
            <div class="form-group">
              <label>Mentor ID (optionnel)</label>
              <input type="number" min="1" step="1" name="workshop_mentor_id" value="<?php echo e($updateForm['workshop_mentor_id']); ?>" placeholder="Ex: 3">
            </div>
          </div>

          <div class="form-group">
            <label>Description workshop</label>
            <textarea name="workshop_description" rows="2" placeholder="Description du workshop"><?php echo e($updateForm['workshop_description']); ?></textarea>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Durée workshop (heures)</label>
              <input type="number" min="1" step="1" name="workshop_duree" value="<?php echo e($updateForm['workshop_duree']); ?>" placeholder="Ex: 2">
            </div>
            <div class="form-group">
              <label>Date atelier (optionnel)</label>
              <input type="datetime-local" name="workshop_date_atelier" value="<?php echo e($updateForm['workshop_date_atelier']); ?>">
            </div>
            <div class="form-group">
              <label>Lieu workshop</label>
              <input type="text" name="workshop_lieu" value="<?php echo e($updateForm['workshop_lieu']); ?>" placeholder="Ex: Tunis">
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-group">
              <label>Places max workshop</label>
              <input type="number" min="1" step="1" name="workshop_places_max" value="<?php echo e($updateForm['workshop_places_max']); ?>" placeholder="Ex: 20">
            </div>
            <div class="form-group">
              <label>Prix workshop (TND)</label>
              <input type="number" min="0" step="0.01" name="workshop_prix" value="<?php echo e($updateForm['workshop_prix']); ?>" placeholder="Ex: 120">
            </div>
            <div class="form-group">
              <label>Certification workshop</label>
              <select name="workshop_certification">
                <option value="oui" <?php echo ($updateForm['workshop_certification'] === 'oui' ? 'selected' : ''); ?>>Oui</option>
                <option value="non" <?php echo ($updateForm['workshop_certification'] === 'non' ? 'selected' : ''); ?>>Non</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label>Statut workshop</label>
              <select name="workshop_statut">
                <option value="a_venir" <?php echo ($updateForm['workshop_statut'] === 'a_venir' ? 'selected' : ''); ?>>A venir</option>
                <option value="en_cours" <?php echo ($updateForm['workshop_statut'] === 'en_cours' ? 'selected' : ''); ?>>En cours</option>
                <option value="termine" <?php echo ($updateForm['workshop_statut'] === 'termine' ? 'selected' : ''); ?>>Termine</option>
                <option value="annule" <?php echo ($updateForm['workshop_statut'] === 'annule' ? 'selected' : ''); ?>>Annule</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Questions du quiz</label>
            <textarea name="quiz_questions" rows="5" placeholder="Entrez les questions du quiz..."><?php echo e($updateForm['quiz_questions']); ?></textarea>
          </div>

          <div class="form-subtitle">Quiz obligatoire de la formation</div>

          <div class="form-group">
            <label>Titre du quiz *</label>
            <input type="text" name="quiz_titre" value="<?php echo e($updateForm['quiz_titre']); ?>" placeholder="Ex: Quiz final - Initiation à la poterie" required>
          </div>

          <div class="form-group">
            <label>Description du quiz</label>
            <textarea name="quiz_description" rows="3" placeholder="Description du quiz (optionnel)"><?php echo e($updateForm['quiz_description']); ?></textarea>
          </div>

          <input type="hidden" name="quiz_note_passage" value="<?php echo e($updateForm['quiz_note_passage']); ?>">
          <input type="hidden" name="quiz_nb_tentatives" value="<?php echo e($updateForm['quiz_nb_tentatives']); ?>">

          <div class="form-row-3">
            <div class="form-group">
              <label>Durée du quiz (minutes) *</label>
              <input type="number" min="1" step="1" name="quiz_duree_minutes" value="<?php echo e($updateForm['quiz_duree_minutes']); ?>" required>
            </div>
          </div>

          <div class="simple-actions">
            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            <a href="back.php" class="btn-primary btn-link">Annuler</a>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <div class="section-head">
      <h2>Liste des formations</h2>
    </div>

    <form method="get" action="back.php" class="toolbar" id="formBackToolbarFilters" novalidate>
      <div class="toolbar-search">
        <input type="text" name="q" value="<?php echo e($recherche); ?>" placeholder="Rechercher par titre, mentor, description...">
      </div>
      <select name="niveau">
        <option value="">Tous niveaux</option>
        <option value="debutant" <?php echo ($niveauFiltreDb === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
        <option value="intermediaire" <?php echo ($niveauFiltreDb === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
        <option value="avance" <?php echo ($niveauFiltreDb === 'avance' ? 'selected' : ''); ?>>Avancé</option>
      </select>
      <button type="submit" class="btn-primary">Filtrer</button>
      <a href="back.php" class="btn-primary btn-link">Réinitialiser</a>
    </form>
    <div class="form-feedback" id="formBackToolbarFiltersMessage" data-form-message-for="formBackToolbarFilters"></div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Titre</th>
            <th>Mentor</th>
            <th>Niveau</th>
            <th>Durée</th>
            <th>Inscrits</th>
            <th>Certif.</th>
            <th>Statut</th>
            <th>Prix</th>
            <th>Quiz requis</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($formations) === 0): ?>
            <tr>
              <td colspan="11" style="text-align:center;padding:20px;">Aucune formation trouvée.</td>
            </tr>
          <?php else: ?>
            <?php for ($i = 0; $i < count($formations); $i++): ?>
              <?php $f = $formations[$i]; ?>
              <tr>
                <td>#<?php echo e($f['id_formation']); ?></td>
                <td><strong><?php echo e($f['domaine']); ?></strong></td>
                <td><?php echo e($f['formateur']); ?></td>
                <td>
                  <span class="badge <?php echo e(niveauBadgeClass($f['niveau'])); ?>"><?php echo e(niveauLabel($f['niveau'])); ?></span>
                </td>
                <td><?php echo e((string) $f['duree']); ?>h</td>
                <td><?php echo e((string) $f['inscrits']); ?></td>
                <td><?php echo (certifOui($f['certification']) ? '✅' : '—'); ?></td>
                <td>
                  <span class="badge <?php echo e(statutBadgeClass($f['etat'])); ?>"><?php echo e((string) $f['etat']); ?></span>
                </td>
                <td><?php echo e((string) $f['prix']); ?> TND</td>
                <td>
                  <?php if (trim((string) $f['quiz_titre']) === ''): ?>
                    <span style="color:var(--gris);">Non configuré</span>
                  <?php else: ?>
                    <strong><?php echo e((string) $f['quiz_titre']); ?></strong><br>
                    <span style="color:var(--gris);font-size:0.75rem;">Seuil: <?php echo e((string) (is_numeric($f['quiz_note_passage']) ? (int) $f['quiz_note_passage'] : 60)); ?>%</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-cell">
                    <form method="get" action="back.php">
                      <input type="hidden" name="edit" value="<?php echo e((string) $f['id_formation']); ?>">
                      <button type="submit" class="btn-action btn-edit">Modifier</button>
                    </form>
                    <form method="post" action="back.php" onsubmit="return confirm('Supprimer cette formation ?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id_formation" value="<?php echo e((string) $f['id_formation']); ?>">
                      <button type="submit" class="btn-action btn-suppr">Supprimer</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endfor; ?>
          <?php endif; ?>
        </tbody>
      </table>
      <div class="pagination">
        <span><?php echo e((string) count($formations)); ?> formation(s)</span>
      </div>
    </div>
  </div>
</div>
<script>
// Returns or creates the message container for a form.
function ensureFormMessageNode(formElement)
{
  var inlineNode = formElement.querySelector('[data-form-message]');
  if (inlineNode) {
    return inlineNode;
  }

  var formId = formElement.getAttribute('id');
  if (formId !== null && formId !== '') {
    var linkedNode = document.querySelector('[data-form-message-for="' + formId + '"]');
    if (linkedNode) {
      return linkedNode;
    }
  }

  var messageNode = document.createElement('div');
  messageNode.className = 'form-feedback';
  messageNode.setAttribute('data-form-message', '1');

  if (formId !== null && formId !== '') {
    messageNode.setAttribute('data-form-message-for', formId);
  }

  formElement.insertAdjacentElement('afterend', messageNode);
  return messageNode;
}

// Clears a form-level message.
function clearFormMessage(formElement)
{
  var messageNode = ensureFormMessageNode(formElement);
  messageNode.textContent = '';
  messageNode.classList.remove('show', 'error', 'success');
}

// Displays a form-level message.
function showFormMessage(formElement, status, message)
{
  var messageNode = ensureFormMessageNode(formElement);
  messageNode.textContent = message;
  messageNode.classList.remove('error', 'success');
  messageNode.classList.add('show');

  if (status === 'success') {
    messageNode.classList.add('success');
  } else {
    messageNode.classList.add('error');
  }
}

// Removes all visual field errors from a form.
function clearInputErrors(formElement)
{
  var fields = formElement.querySelectorAll('.input-error');
  for (var i = 0; i < fields.length; i++) {
    fields[i].classList.remove('input-error');
  }
}

// Returns true when value exists in the allowed list.
function isAllowedOption(value, allowedValues)
{
  for (var i = 0; i < allowedValues.length; i++) {
    if (value === allowedValues[i]) {
      return true;
    }
  }

  return false;
}

// Returns true when a YYYY-MM-DD value is a valid date.
function isValidDateValue(value)
{
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return false;
  }

  var parts = value.split('-');
  var year = parseInt(parts[0], 10);
  var month = parseInt(parts[1], 10);
  var day = parseInt(parts[2], 10);
  var dateObj = new Date(year, month - 1, day);

  return dateObj.getFullYear() === year
    && (dateObj.getMonth() + 1) === month
    && dateObj.getDate() === day;
}

// Returns true when datetime-local value is valid.
function isValidDateTimeValue(value)
{
  if (value === '') {
    return true;
  }

  return !isNaN(Date.parse(value));
}

// Validates filter form values.
function validateBackFilterForm(formElement)
{
  clearFormMessage(formElement);
  clearInputErrors(formElement);

  var isValid = true;
  var firstError = '';
  var queryField = formElement.querySelector('[name="q"]');
  var niveauField = formElement.querySelector('[name="niveau"]');

  if (queryField) {
    var queryValue = queryField.value.trim();
    if (queryValue !== '' && queryValue.length < 2) {
      queryField.classList.add('input-error');
      isValid = false;
      firstError = 'Saisissez au moins 2 caracteres pour la recherche.';
    }
  }

  if (niveauField && !isAllowedOption(niveauField.value, ['', 'debutant', 'intermediaire', 'avance'])) {
    niveauField.classList.add('input-error');
    isValid = false;
    if (firstError === '') {
      firstError = 'Le niveau selectionne est invalide.';
    }
  }

  if (!isValid) {
    showFormMessage(formElement, 'error', firstError);
  }

  return isValid;
}

// Validates update form values.
function validateBackUpdateForm(formElement)
{
  clearFormMessage(formElement);
  clearInputErrors(formElement);

  var integerPattern = /^\d+$/;
  var isValid = true;
  var firstError = '';

  function registerError(fieldElement, message)
  {
    if (fieldElement) {
      fieldElement.classList.add('input-error');
    }

    isValid = false;
    if (firstError === '') {
      firstError = message;
    }
  }

  var titreField = formElement.querySelector('[name="titre"]');
  var mentorField = formElement.querySelector('[name="mentor"]');
  var niveauField = formElement.querySelector('[name="niveau"]');
  var dureeField = formElement.querySelector('[name="duree"]');
  var prixField = formElement.querySelector('[name="prix"]');
  var certificationField = formElement.querySelector('[name="certification"]');
  var etatField = formElement.querySelector('[name="etat"]');
  var dateField = formElement.querySelector('[name="date_realisation"]');
  var quizTitreField = formElement.querySelector('[name="quiz_titre"]');
  var quizDureeField = formElement.querySelector('[name="quiz_duree_minutes"]');

  var titreValue = titreField ? titreField.value.trim() : '';
  if (titreValue === '' || titreValue.length < 3) {
    registerError(titreField, 'Le titre doit contenir au moins 3 caracteres.');
  }

  var mentorValue = mentorField ? mentorField.value.trim() : '';
  if (mentorValue === '') {
    registerError(mentorField, 'Le mentor est obligatoire.');
  }

  if (niveauField && !isAllowedOption(niveauField.value, ['debutant', 'intermediaire', 'avance'])) {
    registerError(niveauField, 'Le niveau selectionne est invalide.');
  }

  var dureeValue = dureeField ? dureeField.value.trim() : '';
  if (!integerPattern.test(dureeValue) || parseInt(dureeValue, 10) <= 0) {
    registerError(dureeField, 'La duree doit etre un entier superieur a 0.');
  }

  var prixValue = prixField ? prixField.value.trim().replace(',', '.') : '';
  if (prixValue === '' || isNaN(parseFloat(prixValue))) {
    registerError(prixField, 'Le prix doit etre un nombre valide.');
  }

  if (certificationField && !isAllowedOption(certificationField.value, ['oui', 'non'])) {
    registerError(certificationField, 'La certification selectionnee est invalide.');
  }

  if (etatField && !isAllowedOption(etatField.value, ['Actif', 'Inactif', 'Brouillon'])) {
    registerError(etatField, 'Le statut selectionne est invalide.');
  }

  var dateValue = dateField ? dateField.value.trim() : '';
  if (dateValue !== '' && !isValidDateValue(dateValue)) {
    registerError(dateField, 'La date de realisation est invalide.');
  }

  var quizTitreValue = quizTitreField ? quizTitreField.value.trim() : '';
  if (quizTitreValue === '' || quizTitreValue.length < 3) {
    registerError(quizTitreField, 'Le titre du quiz doit contenir au moins 3 caracteres.');
  }

  var quizDureeValue = quizDureeField ? quizDureeField.value.trim() : '';
  if (!integerPattern.test(quizDureeValue) || parseInt(quizDureeValue, 10) <= 0) {
    registerError(quizDureeField, 'La duree du quiz doit etre un entier superieur a 0.');
  }

  var workshopTitreField = formElement.querySelector('[name="workshop_titre"]');
  var workshopDescriptionField = formElement.querySelector('[name="workshop_description"]');
  var workshopMentorField = formElement.querySelector('[name="workshop_mentor_id"]');
  var workshopDureeField = formElement.querySelector('[name="workshop_duree"]');
  var workshopDateField = formElement.querySelector('[name="workshop_date_atelier"]');
  var workshopLieuField = formElement.querySelector('[name="workshop_lieu"]');
  var workshopPlacesField = formElement.querySelector('[name="workshop_places_max"]');
  var workshopPrixField = formElement.querySelector('[name="workshop_prix"]');
  var workshopCertificationField = formElement.querySelector('[name="workshop_certification"]');
  var workshopStatutField = formElement.querySelector('[name="workshop_statut"]');

  var workshopTitre = workshopTitreField ? workshopTitreField.value.trim() : '';
  var workshopDescription = workshopDescriptionField ? workshopDescriptionField.value.trim() : '';
  var workshopMentor = workshopMentorField ? workshopMentorField.value.trim() : '';
  var workshopDuree = workshopDureeField ? workshopDureeField.value.trim() : '';
  var workshopDate = workshopDateField ? workshopDateField.value.trim() : '';
  var workshopLieu = workshopLieuField ? workshopLieuField.value.trim() : '';
  var workshopPlaces = workshopPlacesField ? workshopPlacesField.value.trim() : '';
  var workshopPrix = workshopPrixField ? workshopPrixField.value.trim() : '';
  var workshopCertification = workshopCertificationField ? workshopCertificationField.value : 'non';
  var workshopStatut = workshopStatutField ? workshopStatutField.value : 'a_venir';

  var hasWorkshopInput = (
    workshopTitre !== '' ||
    workshopDescription !== '' ||
    workshopMentor !== '' ||
    workshopDuree !== '' ||
    workshopDate !== '' ||
    workshopLieu !== '' ||
    workshopPlaces !== '' ||
    workshopPrix !== ''
  );

  if (hasWorkshopInput) {
    if (workshopTitre === '' || workshopTitre.length < 3) {
      registerError(workshopTitreField, 'Le titre du workshop doit contenir au moins 3 caracteres.');
    }

    if (workshopDescription === '' || workshopDescription.length < 10) {
      registerError(workshopDescriptionField, 'La description du workshop doit contenir au moins 10 caracteres.');
    }

    if (workshopMentor !== '' && (!integerPattern.test(workshopMentor) || parseInt(workshopMentor, 10) <= 0)) {
      registerError(workshopMentorField, 'Le mentor ID du workshop doit etre un entier positif.');
    }

    if (!integerPattern.test(workshopDuree) || parseInt(workshopDuree, 10) <= 0) {
      registerError(workshopDureeField, 'La duree du workshop doit etre un entier superieur a 0.');
    }

    if (!isValidDateTimeValue(workshopDate)) {
      registerError(workshopDateField, 'La date atelier du workshop est invalide.');
    }

    if (workshopLieu === '' || workshopLieu.length < 2) {
      registerError(workshopLieuField, 'Le lieu du workshop est obligatoire (min. 2 caracteres).');
    }

    if (!integerPattern.test(workshopPlaces) || parseInt(workshopPlaces, 10) <= 0) {
      registerError(workshopPlacesField, 'Le nombre de places max du workshop doit etre un entier superieur a 0.');
    }

    var workshopPrixNormalized = workshopPrix.replace(',', '.');
    if (workshopPrixNormalized === '' || isNaN(parseFloat(workshopPrixNormalized))) {
      registerError(workshopPrixField, 'Le prix du workshop doit etre un nombre valide.');
    } else if (parseFloat(workshopPrixNormalized) < 0) {
      registerError(workshopPrixField, 'Le prix du workshop doit etre superieur ou egal a 0.');
    }

    if (!isAllowedOption(workshopCertification, ['oui', 'non'])) {
      registerError(workshopCertificationField, 'La certification du workshop est invalide.');
    }

    if (!isAllowedOption(workshopStatut, ['a_venir', 'en_cours', 'termine', 'annule'])) {
      registerError(workshopStatutField, 'Le statut du workshop est invalide.');
    }
  }

  if (!isValid) {
    showFormMessage(formElement, 'error', firstError);
  }

  return isValid;
}

document.addEventListener('DOMContentLoaded', function () {
  var filterForm = document.getElementById('formBackToolbarFilters');
  if (filterForm) {
    filterForm.addEventListener('submit', function (event) {
      if (!validateBackFilterForm(filterForm)) {
        event.preventDefault();
      }
    });

    var filterFields = filterForm.querySelectorAll('input, select');
    for (var i = 0; i < filterFields.length; i++) {
      (function (fieldElement) {
        var eventName = fieldElement.tagName === 'SELECT' ? 'change' : 'input';
        fieldElement.addEventListener(eventName, function () {
          fieldElement.classList.remove('input-error');
          clearFormMessage(filterForm);
        });
      })(filterFields[i]);
    }
  }

  var updateForm = document.getElementById('formBackUpdateFormation');
  if (updateForm) {
    updateForm.addEventListener('submit', function (event) {
      if (!validateBackUpdateForm(updateForm)) {
        event.preventDefault();
      }
    });

    var updateFields = updateForm.querySelectorAll('input, textarea, select');
    for (var j = 0; j < updateFields.length; j++) {
      (function (fieldElement) {
        var eventName = 'input';
        if (fieldElement.tagName === 'SELECT' || fieldElement.type === 'checkbox' || fieldElement.type === 'radio') {
          eventName = 'change';
        }

        fieldElement.addEventListener(eventName, function () {
          fieldElement.classList.remove('input-error');
          clearFormMessage(updateForm);
        });
      })(updateFields[j]);
    }
  }
});
</script>
</body>
</html>
