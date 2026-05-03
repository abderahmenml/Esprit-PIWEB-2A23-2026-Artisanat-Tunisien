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

function coerceBool($value)
{
  if (is_bool($value)) {
    return $value;
  }

  if (is_int($value) || is_float($value)) {
    return ((int) $value) === 1;
  }

  $raw = strtolower(trim((string) $value));

  return ($raw === '1' || $raw === 'true' || $raw === 'yes' || $raw === 'oui');
}

function callGroqChat(string $system, string $userMsg, int $tokens = 1200): string
{
  $apiKey = '';

  if (defined('GROQ_API_KEY')) {
    $apiKey = GROQ_API_KEY;
  } elseif (getenv('GROQ_API_KEY') !== false) {
    $apiKey = (string) getenv('GROQ_API_KEY');
  }

  if ($apiKey === '') {
    throw new RuntimeException('GROQ_API_KEY non configurée.');
  }

  $payload = json_encode([
    'model' => 'llama-3.3-70b-versatile',
    'max_tokens' => $tokens,
    'messages' => [
      ['role' => 'system', 'content' => $system],
      ['role' => 'user', 'content' => $userMsg]
    ]
  ]);

  $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_TIMEOUT => 30,
  ]);

  $response = curl_exec($ch);
  $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($response === false || $httpCode !== 200) {
    throw new RuntimeException('Erreur API Groq (HTTP ' . $httpCode . ').');
  }

  $decoded = json_decode($response, true);
  if (!is_array($decoded) || !isset($decoded['choices'][0]['message']['content'])) {
    throw new RuntimeException('Réponse API inattendue.');
  }

  return trim((string) $decoded['choices'][0]['message']['content']);
}

function normalizeGroqQuestionsForInsert(array $questions): array
{
  $result = [];

  foreach ($questions as $question) {
    if (!is_array($question)) {
      continue;
    }

    $questionText = trim((string) ($question['enonce'] ?? $question['text'] ?? ''));
    if ($questionText === '' || strlen($questionText) < 3) {
      throw new RuntimeException('Chaque question du quiz doit contenir au moins 3 caractères.');
    }

    $questionType = trim((string) ($question['type'] ?? 'choix_unique'));
    if ($questionType !== 'choix_unique' && $questionType !== 'choix_multiple' && $questionType !== 'vrai_faux') {
      $questionType = 'choix_unique';
    }

    $questionPoints = 1;
    if (isset($question['points']) && is_numeric($question['points']) && (int) $question['points'] > 0) {
      $questionPoints = (int) $question['points'];
    }

    $rawAnswers = [];
    if (isset($question['reponses']) && is_array($question['reponses'])) {
      $rawAnswers = $question['reponses'];
    } elseif (isset($question['answers']) && is_array($question['answers'])) {
      $rawAnswers = $question['answers'];
    }

    if ($questionType === 'vrai_faux') {
      $correctTrue = null;

      for ($ai = 0; $ai < count($rawAnswers); $ai++) {
        $answerRow = $rawAnswers[$ai];
        if (!is_array($answerRow)) {
          continue;
        }
        $answerTextRaw = trim((string) ($answerRow['texte'] ?? $answerRow['text'] ?? ''));
        $answerText = strtolower($answerTextRaw);
        $isCorrect = false;
        if (array_key_exists('est_correcte', $answerRow)) {
          $isCorrect = coerceBool($answerRow['est_correcte']);
        } elseif (array_key_exists('is_correct', $answerRow)) {
          $isCorrect = coerceBool($answerRow['is_correct']);
        }
        if ($isCorrect) {
          if (strpos($answerText, 'vrai') !== false || strpos($answerText, 'true') !== false) {
            $correctTrue = true;
          } elseif (strpos($answerText, 'faux') !== false || strpos($answerText, 'false') !== false) {
            $correctTrue = false;
          }
        }
      }

      if ($correctTrue === null) {
        $correctTrue = true;
      }

      $result[] = [
        'text' => $questionText,
        'type' => 'vrai_faux',
        'points' => $questionPoints,
        'answers' => [
          ['text' => 'True', 'is_correct' => $correctTrue],
          ['text' => 'False', 'is_correct' => !$correctTrue]
        ]
      ];
      continue;
    }

    $cleanAnswers = [];
    $correctCount = 0;

    for ($ai = 0; $ai < count($rawAnswers); $ai++) {
      $answerRow = $rawAnswers[$ai];
      if (!is_array($answerRow)) {
        continue;
      }
      $answerText = trim((string) ($answerRow['texte'] ?? $answerRow['text'] ?? ''));
      if ($answerText === '') {
        continue;
      }

      $isCorrect = false;
      if (array_key_exists('est_correcte', $answerRow)) {
        $isCorrect = coerceBool($answerRow['est_correcte']);
      } elseif (array_key_exists('is_correct', $answerRow)) {
        $isCorrect = coerceBool($answerRow['is_correct']);
      } elseif (array_key_exists('correct', $answerRow)) {
        $isCorrect = coerceBool($answerRow['correct']);
      }

      if ($isCorrect) {
        $correctCount += 1;
      }

      $cleanAnswers[] = [
        'text' => $answerText,
        'is_correct' => $isCorrect
      ];
    }

    if (count($cleanAnswers) < 2) {
      throw new RuntimeException('Chaque question (hors vrai/faux) doit contenir au moins 2 réponses.');
    }

    if ($correctCount <= 0) {
      throw new RuntimeException('Chaque question doit avoir au moins une bonne réponse.');
    }

    if ($questionType === 'choix_unique' && $correctCount !== 1) {
      throw new RuntimeException('Une question en choix unique doit avoir exactement une seule bonne réponse.');
    }

    $result[] = [
      'text' => $questionText,
      'type' => $questionType,
      'points' => $questionPoints,
      'answers' => $cleanAnswers
    ];
  }

  if (count($result) === 0) {
    throw new RuntimeException('Aucune question valide n\'a été générée.');
  }

  return $result;
}

function buildAutoQuizQuestions(array $formationRow, int $nbQuestions = 5): array
{
  $titre = trim((string) ($formationRow['domaine'] ?? ''));
  if ($titre === '') {
    throw new RuntimeException('Titre de formation introuvable.');
  }

  $niveauRaw = isset($formationRow['niveau']) ? (string) $formationRow['niveau'] : '';
  $niveauNormalized = normalizeNiveau($niveauRaw);
  if ($niveauNormalized === '') {
    $niveauNormalized = 'debutant';
  }
  $niveauLabelValue = niveauLabel($niveauNormalized);

  $description = trim((string) ($formationRow['description'] ?? ''));
  $nb = (int) $nbQuestions;
  if ($nb <= 0) {
    $nb = 5;
  }
  if ($nb > 10) {
    $nb = 10;
  }

  $system = <<<SYS
Tu es un expert en pédagogie artisanale tunisienne pour CraftLink.
Tu génères des questions de quiz pertinentes pour évaluer les connaissances acquises lors d'une formation.
Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown, pas de texte avant ou après).
Format exact :
{
  "questions": [
    {
      "enonce": "Texte de la question ?",
      "type": "choix_unique",
      "points": 1,
      "reponses": [
        {"texte": "Réponse A", "est_correcte": true},
        {"texte": "Réponse B", "est_correcte": false},
        {"texte": "Réponse C", "est_correcte": false}
      ]
    }
  ]
}
Types acceptés : choix_unique, choix_multiple, vrai_faux.
Pour vrai_faux, les deux réponses sont "Vrai" et "Faux".
SYS;

  $descSnippet = $description !== '' ? "\nDescription : {$description}" : '';
  $userMsg = "Formation : « {$titre} »\nNiveau : {$niveauLabelValue}{$descSnippet}\n\nGénère {$nb} questions de quiz variées et pertinentes (mix choix_unique, vrai_faux).";

  $raw = callGroqChat($system, $userMsg, 1200);

  $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
  $raw = preg_replace('/\s*```$/m', '', $raw);
  $raw = trim($raw);

  $parsed = json_decode($raw, true);
  if (!is_array($parsed) || !isset($parsed['questions']) || !is_array($parsed['questions'])) {
    throw new RuntimeException('Format JSON invalide retourné par l\'IA.');
  }

  return normalizeGroqQuestionsForInsert($parsed['questions']);
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
    $sqlFormation = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix, f.video_url,
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
    $sqlFormation = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.date_realisation, f.etat, f.duree, f.certification, f.niveau, f.prix, f.video_url,
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
$hasQuizQuestionsTable = tableExists($pdo, 'questions');
$hasQuizResponsesTable = tableExists($pdo, 'reponses');
$quizQuestionsEnabled = $hasQuizQuestionsTable && $hasQuizResponsesTable;

$erreur = '';
$succes = '';
$afficherFormUpdate = false;
$openFormationModal = false;
$openWorkshopModal = false;
$openQuizModal = false;
$editWorkshopId = 0;
$editQuizId = 0;

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
  'quiz_duree_minutes' => '20',
  'quiz_note_passage' => '60',
  'quiz_nb_tentatives' => '3',
  'formation_id' => 0
];

$workshopForm = [
  'id_workshop' => 0,
  'titre' => '',
  'description' => '',
  'mentor_id' => '',
  'duree' => '',
  'date_atelier' => '',
  'lieu' => '',
  'places_max' => '',
  'prix' => '',
  'certification' => 'non',
  'statut' => 'a_venir',
  'formation_id' => 0
];

$quizForm = [
  'id_quizz' => 0,
  'titre' => '',
  'description' => '',
  'note_passage' => '60',
  'nb_tentatives' => '3',
  'duree_minutes' => '20',
  'formation_id' => 0
];

$oldQuizBuilderQuestions = [];
$nextQuizBuilderQuestionIndex = 0;

function defaultQuizBuilderQuestions()
{
  return [
    [
      'key' => '0',
      'text' => '',
      'type' => 'choix_unique',
      'points' => 1,
      'tf_correct' => 'true',
      'answers' => [
        ['key' => '0', 'text' => '', 'is_correct' => false],
        ['key' => '1', 'text' => '', 'is_correct' => false]
      ]
    ]
  ];
}

function parseQuizBuilderQuestions($post)
{
  $result = [];

  if (!isset($post['quiz_question_text']) || !is_array($post['quiz_question_text'])) {
    return $result;
  }

  $questionTexts = $post['quiz_question_text'];

  foreach ($questionTexts as $questionKey => $questionTextRaw) {
    $questionKeyString = (string) $questionKey;
    $questionText = trim((string) $questionTextRaw);

    $questionType = 'choix_unique';
    if (isset($post['quiz_question_type']) && isset($post['quiz_question_type'][$questionKey])) {
      $typeRaw = trim((string) $post['quiz_question_type'][$questionKey]);
      if ($typeRaw === 'choix_multiple' || $typeRaw === 'vrai_faux') {
        $questionType = $typeRaw;
      }
    }

    $questionPoints = 1;
    if (isset($post['quiz_question_points']) && isset($post['quiz_question_points'][$questionKey])) {
      $pointsRaw = trim((string) $post['quiz_question_points'][$questionKey]);
      if (ctype_digit($pointsRaw) && (int) $pointsRaw > 0) {
        $questionPoints = (int) $pointsRaw;
      }
    }

    $tfCorrect = 'true';
    if (isset($post['quiz_tf_correct']) && isset($post['quiz_tf_correct'][$questionKey])) {
      $tfRaw = trim((string) $post['quiz_tf_correct'][$questionKey]);
      if ($tfRaw === 'false') {
        $tfCorrect = 'false';
      }
    }

    $answers = [];

    if ($questionType === 'vrai_faux') {
      $answers[] = ['key' => '0', 'text' => 'True', 'is_correct' => ($tfCorrect === 'true')];
      $answers[] = ['key' => '1', 'text' => 'False', 'is_correct' => ($tfCorrect === 'false')];
    } else {
      $answerTexts = [];
      if (isset($post['quiz_answer_text']) && isset($post['quiz_answer_text'][$questionKey]) && is_array($post['quiz_answer_text'][$questionKey])) {
        $answerTexts = $post['quiz_answer_text'][$questionKey];
      }

      $correctLookup = [];
      if (isset($post['quiz_answer_correct']) && isset($post['quiz_answer_correct'][$questionKey]) && is_array($post['quiz_answer_correct'][$questionKey])) {
        $correctValues = $post['quiz_answer_correct'][$questionKey];
        for ($i = 0; $i < count($correctValues); $i++) {
          $correctLookup[(string) $correctValues[$i]] = true;
        }
      }

      foreach ($answerTexts as $answerKey => $answerTextRaw) {
        $answerKeyString = (string) $answerKey;
        $answers[] = [
          'key' => $answerKeyString,
          'text' => trim((string) $answerTextRaw),
          'is_correct' => isset($correctLookup[$answerKeyString])
        ];
      }

      if (count($answers) === 0) {
        $answers[] = ['key' => '0', 'text' => '', 'is_correct' => false];
        $answers[] = ['key' => '1', 'text' => '', 'is_correct' => false];
      }
    }

    $result[] = [
      'key' => $questionKeyString,
      'text' => $questionText,
      'type' => $questionType,
      'points' => $questionPoints,
      'tf_correct' => $tfCorrect,
      'answers' => $answers
    ];
  }

  return $result;
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
  $succes = 'Formation mise à jour avec succès.';
}
if (isset($_GET['workshop_updated']) && $_GET['workshop_updated'] === '1') {
  $succes = 'Workshop mis à jour avec succès.';
}
if (isset($_GET['quiz_updated']) && $_GET['quiz_updated'] === '1') {
  $succes = 'Quiz mis à jour avec succès.';
}
if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
  $succes = 'Formation supprimée avec succès.';
}
if (isset($_GET['workshop_deleted']) && $_GET['workshop_deleted'] === '1') {
  $succes = 'Workshop supprimé avec succès.';
}
if (isset($_GET['quiz_deleted']) && $_GET['quiz_deleted'] === '1') {
  $succes = 'Quiz supprimé avec succès.';
}
if (isset($_GET['formation_ok']) && $_GET['formation_ok'] === '1') {
  $succes = 'Formation ajoutée avec succès.';
}
if (isset($_GET['workshop_ok']) && $_GET['workshop_ok'] === '1') {
  $succes = 'Workshop ajouté avec succès.';
}
if (isset($_GET['quiz_ok']) && $_GET['quiz_ok'] === '1') {
  $succes = 'Quiz ajouté avec succès.';
}

if (isset($_GET['open_add_formation']) && $_GET['open_add_formation'] === '1') {
  $openFormationModal = true;
}
if (isset($_GET['open_add_workshop']) && $_GET['open_add_workshop'] === '1') {
  $openWorkshopModal = true;
}
if (isset($_GET['open_add_quiz']) && $_GET['open_add_quiz'] === '1') {
  $openQuizModal = true;
}

$requestMethod = 'GET';
if (isset($_SERVER['REQUEST_METHOD'])) {
  $requestMethod = (string) $_SERVER['REQUEST_METHOD'];
}

if ($requestMethod === 'POST') {
  $action = isset($_POST['action']) ? trim($_POST['action']) : '';

  // UPDATE FORMATION
  if ($action === 'update_formation') {
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
                          video_url = :video_url
                      WHERE id_formation = :id_formation';
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':domaine', $updateForm['titre']);
        $stmtUpdate->bindValue(':formateur', $updateForm['mentor']);
        $stmtUpdate->bindValue(':description', $updateForm['description']);
        $stmtUpdate->bindValue(':video_url', $updateForm['lien_video']);
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
  }
  // ADD FORMATION
  elseif ($action === 'add_formation') {
    $titre = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
    $description = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $mentor = isset($_POST['mentor']) ? trim((string) $_POST['mentor']) : '';
    $niveau = isset($_POST['niveau']) ? trim((string) $_POST['niveau']) : 'debutant';
    $duree = isset($_POST['duree']) ? trim((string) $_POST['duree']) : '';
    $prix = isset($_POST['prix']) ? trim((string) $_POST['prix']) : '';
    $certification = isset($_POST['certification']) ? trim((string) $_POST['certification']) : 'oui';
    $etat = isset($_POST['etat']) ? trim((string) $_POST['etat']) : 'Actif';
    $date_realisation = isset($_POST['date_realisation']) ? trim((string) $_POST['date_realisation']) : '';
    $lien_video = isset($_POST['lien_video']) ? trim((string) $_POST['lien_video']) : '';

    if ($titre === '' || strlen($titre) < 3) {
      $erreur = 'Le titre doit contenir au moins 3 caractères.';
    } elseif ($mentor === '') {
      $erreur = 'Le mentor est obligatoire.';
    } else {
      $niveauDb = normalizeNiveau($niveau);
      if ($niveauDb === '') {
        $erreur = 'Le niveau sélectionné est invalide.';
      } elseif ($duree === '' || !ctype_digit($duree) || (int) $duree <= 0) {
        $erreur = 'La durée doit être un nombre entier supérieur à 0.';
      } elseif (!is_numeric($prix)) {
        $erreur = 'Le prix doit être un nombre valide.';
      } elseif ($certification !== 'oui' && $certification !== 'non') {
        $erreur = 'La certification sélectionnée est invalide.';
      } elseif ($etat !== 'Actif' && $etat !== 'Inactif' && $etat !== 'Brouillon') {
        $erreur = 'Le statut sélectionné est invalide.';
      } elseif ($date_realisation !== '' && strtotime($date_realisation) === false) {
        $erreur = 'La date de réalisation est invalide.';
      } else {
        try {
          $sqlInsert = 'INSERT INTO formations (domaine, formateur, description, date_realisation, etat, duree, certification, niveau, prix, video_url)
                        VALUES (:domaine, :formateur, :description, :date_realisation, :etat, :duree, :certification, :niveau, :prix, :video_url)';
          $stmtInsert = $pdo->prepare($sqlInsert);
          $stmtInsert->bindValue(':domaine', $titre);
          $stmtInsert->bindValue(':formateur', $mentor);
          $stmtInsert->bindValue(':description', $description);
          $stmtInsert->bindValue(':video_url', $lien_video);
          if ($date_realisation === '') {
            $stmtInsert->bindValue(':date_realisation', null, PDO::PARAM_NULL);
          } else {
            $stmtInsert->bindValue(':date_realisation', $date_realisation);
          }
          $stmtInsert->bindValue(':etat', $etat);
          $stmtInsert->bindValue(':duree', (int) $duree, PDO::PARAM_INT);
          $stmtInsert->bindValue(':certification', $certification);
          $stmtInsert->bindValue(':niveau', $niveauDb);
          $stmtInsert->bindValue(':prix', (float) $prix);
          $stmtInsert->execute();

          header('Location: back.php?formation_ok=1');
          exit;
        } catch (PDOException $e) {
          $erreur = 'Erreur base de données pendant l\'ajout.';
        }
      }
    }
    if ($erreur !== '') {
      $openFormationModal = true;
    }
  }
  // UPDATE WORKSHOP
  elseif ($action === 'update_workshop') {
    $workshopForm['id_workshop'] = isset($_POST['id_workshop']) ? (int) $_POST['id_workshop'] : 0;
    $workshopForm['titre'] = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
    $workshopForm['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $workshopForm['mentor_id'] = isset($_POST['mentor_id']) ? trim((string) $_POST['mentor_id']) : '';
    $workshopForm['duree'] = isset($_POST['duree']) ? trim((string) $_POST['duree']) : '';
    $workshopForm['date_atelier'] = isset($_POST['date_atelier']) ? trim((string) $_POST['date_atelier']) : '';
    $workshopForm['lieu'] = isset($_POST['lieu']) ? trim((string) $_POST['lieu']) : '';
    $workshopForm['places_max'] = isset($_POST['places_max']) ? trim((string) $_POST['places_max']) : '';
    $workshopForm['prix'] = isset($_POST['prix']) ? trim((string) $_POST['prix']) : '';
    $workshopForm['certification'] = isset($_POST['certification']) ? trim((string) $_POST['certification']) : 'non';
    $workshopForm['statut'] = isset($_POST['statut']) ? trim((string) $_POST['statut']) : 'a_venir';
    $workshopForm['formation_id'] = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;

    if ($workshopForm['id_workshop'] <= 0) {
      $erreur = 'Identifiant de workshop invalide.';
    }

    if ($erreur === '' && ($workshopForm['titre'] === '' || strlen($workshopForm['titre']) < 3)) {
      $erreur = 'Le titre doit contenir au moins 3 caractères.';
    }

    if ($erreur === '' && ($workshopForm['description'] === '' || strlen($workshopForm['description']) < 10)) {
      $erreur = 'La description doit contenir au moins 10 caractères.';
    }

    if ($erreur === '' && $workshopForm['mentor_id'] !== '' && (!ctype_digit($workshopForm['mentor_id']) || (int) $workshopForm['mentor_id'] <= 0)) {
      $erreur = 'Le mentor_id doit être un entier positif.';
    }

    if ($erreur === '' && $workshopForm['mentor_id'] !== '') {
      $mentorIdWorkshop = (int) $workshopForm['mentor_id'];
      if (!userExistsById($pdo, $mentorIdWorkshop)) {
        $workshopForm['mentor_id'] = '';
      }
    }

    if ($erreur === '' && (!ctype_digit($workshopForm['duree']) || (int) $workshopForm['duree'] <= 0)) {
      $erreur = 'La durée doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && $workshopForm['date_atelier'] !== '' && strtotime($workshopForm['date_atelier']) === false) {
      $erreur = 'La date atelier est invalide.';
    }

    if ($erreur === '' && ($workshopForm['lieu'] === '' || strlen($workshopForm['lieu']) < 2)) {
      $erreur = 'Le lieu est obligatoire (min. 2 caractères).';
    }

    if ($erreur === '' && (!ctype_digit($workshopForm['places_max']) || (int) $workshopForm['places_max'] <= 0)) {
      $erreur = 'Le nombre de places max doit être un entier supérieur à 0.';
    }

    if ($erreur === '' && !is_numeric($workshopForm['prix'])) {
      $erreur = 'Le prix doit être un nombre valide.';
    } elseif ($erreur === '' && is_numeric($workshopForm['prix']) && (float) $workshopForm['prix'] < 0) {
      $erreur = 'Le prix doit être supérieur ou égal à 0.';
    }

    if ($erreur === '' && $workshopForm['certification'] !== 'oui' && $workshopForm['certification'] !== 'non') {
      $erreur = 'La certification est invalide.';
    }

    if ($erreur === '' && $workshopForm['statut'] !== 'a_venir' && $workshopForm['statut'] !== 'en_cours' && $workshopForm['statut'] !== 'termine' && $workshopForm['statut'] !== 'annule') {
      $erreur = 'Le statut est invalide.';
    }

    if ($erreur === '') {
      try {
        $workshopDateAtelierSql = null;
        if ($workshopForm['date_atelier'] !== '') {
          $workshopDateAtelierSql = date('Y-m-d H:i:s', strtotime($workshopForm['date_atelier']));
        }

        $sqlUpdate = 'UPDATE workshops
                      SET titre = :titre,
                          description = :description,
                          mentor_id = :mentor_id,
                          duree = :duree,
                          date_atelier = :date_atelier,
                          lieu = :lieu,
                          places_max = :places_max,
                          prix = :prix,
                          certification = :certification,
                          statut = :statut
                      WHERE id_workshop = :id_workshop';
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->bindValue(':titre', $workshopForm['titre']);
        $stmtUpdate->bindValue(':description', $workshopForm['description']);
        if ($workshopForm['mentor_id'] === '') {
          $stmtUpdate->bindValue(':mentor_id', null, PDO::PARAM_NULL);
        } else {
          $stmtUpdate->bindValue(':mentor_id', (int) $workshopForm['mentor_id'], PDO::PARAM_INT);
        }
        $stmtUpdate->bindValue(':duree', (int) $workshopForm['duree'], PDO::PARAM_INT);
        if ($workshopDateAtelierSql === null) {
          $stmtUpdate->bindValue(':date_atelier', null, PDO::PARAM_NULL);
        } else {
          $stmtUpdate->bindValue(':date_atelier', $workshopDateAtelierSql);
        }
        $stmtUpdate->bindValue(':lieu', $workshopForm['lieu']);
        $stmtUpdate->bindValue(':places_max', (int) $workshopForm['places_max'], PDO::PARAM_INT);
        $stmtUpdate->bindValue(':prix', (float) $workshopForm['prix']);
        $stmtUpdate->bindValue(':certification', $workshopForm['certification']);
        $stmtUpdate->bindValue(':statut', $workshopForm['statut']);
        $stmtUpdate->bindValue(':id_workshop', $workshopForm['id_workshop'], PDO::PARAM_INT);
        $stmtUpdate->execute();

        // Update workshop-formation relationship
        if ($workshopForm['formation_id'] > 0) {
            // First delete existing relationship
            if (tableExists($pdo, 'workshops_formation')) {
                $stmtDel = $pdo->prepare('DELETE FROM workshops_formation WHERE id_workshop = :id_workshop');
                $stmtDel->bindValue(':id_workshop', $workshopForm['id_workshop'], PDO::PARAM_INT);
                $stmtDel->execute();
                
                $stmtLink = $pdo->prepare('INSERT INTO workshops_formation (id_workshop, id_formation) VALUES (:id_workshop, :id_formation)');
                $stmtLink->bindValue(':id_workshop', $workshopForm['id_workshop'], PDO::PARAM_INT);
                $stmtLink->bindValue(':id_formation', $workshopForm['formation_id'], PDO::PARAM_INT);
                $stmtLink->execute();
            } elseif (tableExists($pdo, 'formations_workshops')) {
                $stmtDel = $pdo->prepare('DELETE FROM formations_workshops WHERE id_workshop = :id_workshop');
                $stmtDel->bindValue(':id_workshop', $workshopForm['id_workshop'], PDO::PARAM_INT);
                $stmtDel->execute();
                
                $stmtLink = $pdo->prepare('INSERT INTO formations_workshops (id_workshop, id_formation) VALUES (:id_workshop, :id_formation)');
                $stmtLink->bindValue(':id_workshop', $workshopForm['id_workshop'], PDO::PARAM_INT);
                $stmtLink->bindValue(':id_formation', $workshopForm['formation_id'], PDO::PARAM_INT);
                $stmtLink->execute();
            }
        }

        header('Location: back.php?workshop_updated=1');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant la mise à jour du workshop.';
      }
    }
  }
  // ADD WORKSHOP
  elseif ($action === 'add_workshop') {
    $workshopForm['titre'] = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
    $workshopForm['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $workshopForm['mentor_id'] = isset($_POST['mentor_id']) ? trim((string) $_POST['mentor_id']) : '';
    $workshopForm['duree'] = isset($_POST['duree']) ? trim((string) $_POST['duree']) : '';
    $workshopForm['date_atelier'] = isset($_POST['date_atelier']) ? trim((string) $_POST['date_atelier']) : '';
    $workshopForm['lieu'] = isset($_POST['lieu']) ? trim((string) $_POST['lieu']) : '';
    $workshopForm['places_max'] = isset($_POST['places_max']) ? trim((string) $_POST['places_max']) : '';
    $workshopForm['prix'] = isset($_POST['prix']) ? trim((string) $_POST['prix']) : '';
    $workshopForm['certification'] = isset($_POST['certification']) ? trim((string) $_POST['certification']) : 'non';
    $workshopForm['statut'] = isset($_POST['statut']) ? trim((string) $_POST['statut']) : 'a_venir';
    $workshopForm['formation_id'] = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;

    if ($workshopForm['titre'] === '' || strlen($workshopForm['titre']) < 3) {
      $erreur = 'Le titre doit contenir au moins 3 caractères.';
    }
    if ($erreur === '' && ($workshopForm['description'] === '' || strlen($workshopForm['description']) < 10)) {
      $erreur = 'La description doit contenir au moins 10 caractères.';
    }
    if ($erreur === '' && $workshopForm['mentor_id'] !== '' && (!ctype_digit($workshopForm['mentor_id']) || (int) $workshopForm['mentor_id'] <= 0)) {
      $erreur = 'Le mentor_id doit être un entier positif.';
    }
    if ($erreur === '' && $workshopForm['mentor_id'] !== '') {
      $mentorIdWorkshop = (int) $workshopForm['mentor_id'];
      if (!userExistsById($pdo, $mentorIdWorkshop)) {
        $workshopForm['mentor_id'] = '';
      }
    }
    if ($erreur === '' && (!ctype_digit($workshopForm['duree']) || (int) $workshopForm['duree'] <= 0)) {
      $erreur = 'La durée doit être un entier supérieur à 0.';
    }
    if ($erreur === '' && $workshopForm['date_atelier'] !== '' && strtotime($workshopForm['date_atelier']) === false) {
      $erreur = 'La date atelier est invalide.';
    }
    if ($erreur === '' && ($workshopForm['lieu'] === '' || strlen($workshopForm['lieu']) < 2)) {
      $erreur = 'Le lieu est obligatoire (min. 2 caractères).';
    }
    if ($erreur === '' && (!ctype_digit($workshopForm['places_max']) || (int) $workshopForm['places_max'] <= 0)) {
      $erreur = 'Le nombre de places max doit être un entier supérieur à 0.';
    }
    if ($erreur === '' && !is_numeric($workshopForm['prix'])) {
      $erreur = 'Le prix doit être un nombre valide.';
    }
    if ($erreur === '' && is_numeric($workshopForm['prix']) && (float) $workshopForm['prix'] < 0) {
      $erreur = 'Le prix doit être supérieur ou égal à 0.';
    }
    if ($erreur === '' && $workshopForm['certification'] !== 'oui' && $workshopForm['certification'] !== 'non') {
      $erreur = 'La certification est invalide.';
    }
    if ($erreur === '' && $workshopForm['statut'] !== 'a_venir' && $workshopForm['statut'] !== 'en_cours' && $workshopForm['statut'] !== 'termine' && $workshopForm['statut'] !== 'annule') {
      $erreur = 'Le statut est invalide.';
    }
    if ($erreur === '' && $workshopForm['formation_id'] <= 0) {
      $erreur = 'La formation associée est obligatoire.';
    }
    if ($erreur === '') {
      try {
        $workshopDateAtelierSql = null;
        if ($workshopForm['date_atelier'] !== '') {
          $workshopDateAtelierSql = date('Y-m-d H:i:s', strtotime($workshopForm['date_atelier']));
        }

        $sqlInsert = 'INSERT INTO workshops (titre, description, mentor_id, duree, date_publication, date_atelier, lieu, places_max, places_restantes, prix, certification, statut)
                      VALUES (:titre, :description, :mentor_id, :duree, CURDATE(), :date_atelier, :lieu, :places_max, :places_restantes, :prix, :certification, :statut)';
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->bindValue(':titre', $workshopForm['titre']);
        $stmtInsert->bindValue(':description', $workshopForm['description']);
        if ($workshopForm['mentor_id'] === '') {
          $stmtInsert->bindValue(':mentor_id', null, PDO::PARAM_NULL);
        } else {
          $stmtInsert->bindValue(':mentor_id', (int) $workshopForm['mentor_id'], PDO::PARAM_INT);
        }
        $stmtInsert->bindValue(':duree', (int) $workshopForm['duree'], PDO::PARAM_INT);
        if ($workshopDateAtelierSql === null) {
          $stmtInsert->bindValue(':date_atelier', null, PDO::PARAM_NULL);
        } else {
          $stmtInsert->bindValue(':date_atelier', $workshopDateAtelierSql);
        }
        $stmtInsert->bindValue(':lieu', $workshopForm['lieu']);
        $stmtInsert->bindValue(':places_max', (int) $workshopForm['places_max'], PDO::PARAM_INT);
        $stmtInsert->bindValue(':places_restantes', (int) $workshopForm['places_max'], PDO::PARAM_INT);
        $stmtInsert->bindValue(':prix', (float) $workshopForm['prix']);
        $stmtInsert->bindValue(':certification', $workshopForm['certification']);
        $stmtInsert->bindValue(':statut', $workshopForm['statut']);
        $stmtInsert->execute();
        
        $newWorkshopId = (int) $pdo->lastInsertId();
        
        // Link workshop to formation
        if ($workshopForm['formation_id'] > 0 && $newWorkshopId > 0) {
            if (tableExists($pdo, 'workshops_formation')) {
                $sqlLink = 'INSERT INTO workshops_formation (id_workshop, id_formation) VALUES (:id_workshop, :id_formation)';
                $stmtLink = $pdo->prepare($sqlLink);
                $stmtLink->bindValue(':id_workshop', $newWorkshopId, PDO::PARAM_INT);
                $stmtLink->bindValue(':id_formation', $workshopForm['formation_id'], PDO::PARAM_INT);
                $stmtLink->execute();
            } elseif (tableExists($pdo, 'formations_workshops')) {
                $sqlLink = 'INSERT INTO formations_workshops (id_workshop, id_formation) VALUES (:id_workshop, :id_formation)';
                $stmtLink = $pdo->prepare($sqlLink);
                $stmtLink->bindValue(':id_workshop', $newWorkshopId, PDO::PARAM_INT);
                $stmtLink->bindValue(':id_formation', $workshopForm['formation_id'], PDO::PARAM_INT);
                $stmtLink->execute();
            }
        }

        header('Location: back.php?workshop_ok=1');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant l\'ajout du workshop.';
      }
    }
    if ($erreur !== '') {
      $openWorkshopModal = true;
    }
  }
  // UPDATE QUIZ
  elseif ($action === 'update_quiz') {
    $quizForm['id_quizz'] = isset($_POST['id_quizz']) ? (int) $_POST['id_quizz'] : 0;
    $quizForm['titre'] = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
    $quizForm['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $quizForm['note_passage'] = isset($_POST['note_passage']) ? trim((string) $_POST['note_passage']) : '60';
    $quizForm['nb_tentatives'] = isset($_POST['nb_tentatives']) ? trim((string) $_POST['nb_tentatives']) : '3';
    $quizForm['duree_minutes'] = isset($_POST['duree_minutes']) ? trim((string) $_POST['duree_minutes']) : '20';
    $quizForm['formation_id'] = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;

    $autoGenerateQuiz = !isset($_POST['quiz_question_text']);
    $quizBuilderQuestionsInput = [];
    $quizQuestionsToInsert = [];

    if (!$autoGenerateQuiz) {
      $quizBuilderQuestionsInput = parseQuizBuilderQuestions($_POST);
    }

    if ($quizForm['id_quizz'] <= 0) {
      $erreur = 'Identifiant de quiz invalide.';
    }

    if ($erreur === '' && $quizForm['formation_id'] <= 0) {
      $erreur = 'La formation associée est obligatoire.';
    }

    if ($erreur === '' && !$quizQuestionsEnabled) {
      $erreur = 'Les tables questions/reponses sont nécessaires pour modifier les questions du quiz.';
    }

    if ($erreur === '' && !$autoGenerateQuiz) {
      if ($quizForm['titre'] === '' || strlen($quizForm['titre']) < 3) {
        $erreur = 'Le titre doit contenir au moins 3 caractères.';
      }

      if ($erreur === '' && (!ctype_digit($quizForm['duree_minutes']) || (int) $quizForm['duree_minutes'] <= 0)) {
        $erreur = 'La durée doit être un entier supérieur à 0.';
      }

      if ($erreur === '' && (!ctype_digit($quizForm['note_passage']) || (int) $quizForm['note_passage'] < 0 || (int) $quizForm['note_passage'] > 100)) {
        $erreur = 'La note de passage doit être un entier entre 0 et 100.';
      }

      if ($erreur === '' && (!ctype_digit($quizForm['nb_tentatives']) || (int) $quizForm['nb_tentatives'] <= 0)) {
        $erreur = 'Le nombre de tentatives doit être un entier supérieur à 0.';
      }

      if ($erreur === '' && count($quizBuilderQuestionsInput) === 0) {
        $erreur = 'Ajoutez au moins une question au quiz.';
      }

      if ($erreur === '') {
        for ($q = 0; $q < count($quizBuilderQuestionsInput); $q++) {
          $questionInput = $quizBuilderQuestionsInput[$q];
          $questionText = trim((string) $questionInput['text']);
          $questionType = trim((string) $questionInput['type']);
          $questionPoints = 1;

          if (isset($questionInput['points']) && is_numeric($questionInput['points']) && (int) $questionInput['points'] > 0) {
            $questionPoints = (int) $questionInput['points'];
          }

          if ($questionText === '' || strlen($questionText) < 3) {
            $erreur = 'Chaque question du quiz doit contenir au moins 3 caractères.';
            break;
          }

          if ($questionType !== 'choix_unique' && $questionType !== 'choix_multiple' && $questionType !== 'vrai_faux') {
            $questionType = 'choix_unique';
          }

          if ($questionType === 'vrai_faux') {
            $tfCorrectRaw = 'true';
            if (isset($questionInput['tf_correct']) && trim((string) $questionInput['tf_correct']) === 'false') {
              $tfCorrectRaw = 'false';
            }

            $quizQuestionsToInsert[] = [
              'text' => $questionText,
              'type' => 'vrai_faux',
              'points' => $questionPoints,
              'answers' => [
                ['text' => 'True', 'is_correct' => ($tfCorrectRaw === 'true')],
                ['text' => 'False', 'is_correct' => ($tfCorrectRaw === 'false')]
              ]
            ];
            continue;
          }

          $rawAnswers = [];
          if (isset($questionInput['answers']) && is_array($questionInput['answers'])) {
            $rawAnswers = $questionInput['answers'];
          }

          $cleanAnswers = [];
          $correctCount = 0;

          for ($a = 0; $a < count($rawAnswers); $a++) {
            $answerInput = $rawAnswers[$a];
            $answerText = trim((string) $answerInput['text']);
            if ($answerText === '') {
              continue;
            }

            $isCorrect = isset($answerInput['is_correct']) && $answerInput['is_correct'];
            if ($isCorrect) {
              $correctCount += 1;
            }

            $cleanAnswers[] = [
              'text' => $answerText,
              'is_correct' => $isCorrect
            ];
          }

          if (count($cleanAnswers) < 2) {
            $erreur = 'Chaque question (hors vrai/faux) doit contenir au moins 2 réponses.';
            break;
          }

          if ($correctCount <= 0) {
            $erreur = 'Chaque question doit avoir au moins une bonne réponse.';
            break;
          }

          if ($questionType === 'choix_unique' && $correctCount !== 1) {
            $erreur = 'Une question en choix unique doit avoir exactement une seule bonne réponse.';
            break;
          }

          $quizQuestionsToInsert[] = [
            'text' => $questionText,
            'type' => $questionType,
            'points' => $questionPoints,
            'answers' => $cleanAnswers
          ];
        }
      }
    }

    if ($erreur === '' && $autoGenerateQuiz) {
      try {
        $formationRow = getFormationById($pdo, $quizForm['formation_id']);
        if (!$formationRow) {
          $erreur = 'Formation associée introuvable.';
        } else {
          $formationTitle = trim((string) $formationRow['domaine']);
          $quizForm['titre'] = $formationTitle !== '' ? ('Quiz - ' . $formationTitle) : 'Quiz automatique';
          $quizForm['description'] = '';
          $quizQuestionsToInsert = buildAutoQuizQuestions($formationRow, 5);
        }
      } catch (RuntimeException $e) {
        $erreur = $e->getMessage();
      }
    }

    if ($erreur === '') {
      try {
        $pdo->beginTransaction();

        $quizSetParts = [];

        if ($hasQuizzTitreColumn) {
          $quizSetParts[] = 'titre = :titre';
        }
        if (!$autoGenerateQuiz && $hasQuizzDescriptionColumn) {
          $quizSetParts[] = 'description = :description';
        }
        if ($hasQuizzFormationIdColumn) {
          $quizSetParts[] = 'formation_id = :formation_id';
        }
        if (!$autoGenerateQuiz && $hasQuizzNotePassageColumn) {
          $quizSetParts[] = 'note_passage = :note_passage';
        } elseif (!$autoGenerateQuiz && $hasQuizzScoreColumn) {
          $quizSetParts[] = 'score = :score';
        }
        if (!$autoGenerateQuiz && $hasQuizzNbTentativesColumn) {
          $quizSetParts[] = 'nb_tentatives = :nb_tentatives';
        }
        if (!$autoGenerateQuiz && $hasQuizzDureeMinutesColumn) {
          $quizSetParts[] = 'duree_minutes = :duree_minutes';
        } elseif (!$autoGenerateQuiz && $hasQuizzDureeColumn) {
          $quizSetParts[] = 'duree = :duree';
        }

        if (count($quizSetParts) > 0) {
          $sqlQuizUpdate = 'UPDATE quizz
                            SET ' . implode(",\n                                  ", $quizSetParts) . '
                            WHERE id_quizz = :id_quizz';
          $stmtQuizUpdate = $pdo->prepare($sqlQuizUpdate);

          if ($hasQuizzTitreColumn) {
            $stmtQuizUpdate->bindValue(':titre', $quizForm['titre']);
          }
          if (!$autoGenerateQuiz && $hasQuizzDescriptionColumn) {
            if ($quizForm['description'] === '') {
              $stmtQuizUpdate->bindValue(':description', null, PDO::PARAM_NULL);
            } else {
              $stmtQuizUpdate->bindValue(':description', $quizForm['description']);
            }
          }
          if ($hasQuizzFormationIdColumn) {
            $stmtQuizUpdate->bindValue(':formation_id', $quizForm['formation_id'], PDO::PARAM_INT);
          }
          if (!$autoGenerateQuiz && $hasQuizzNotePassageColumn) {
            $stmtQuizUpdate->bindValue(':note_passage', (int) $quizForm['note_passage'], PDO::PARAM_INT);
          } elseif (!$autoGenerateQuiz && $hasQuizzScoreColumn) {
            $stmtQuizUpdate->bindValue(':score', (int) $quizForm['note_passage'], PDO::PARAM_INT);
          }
          if (!$autoGenerateQuiz && $hasQuizzNbTentativesColumn) {
            $stmtQuizUpdate->bindValue(':nb_tentatives', (int) $quizForm['nb_tentatives'], PDO::PARAM_INT);
          }
          if (!$autoGenerateQuiz && $hasQuizzDureeMinutesColumn) {
            $stmtQuizUpdate->bindValue(':duree_minutes', (int) $quizForm['duree_minutes'], PDO::PARAM_INT);
          } elseif (!$autoGenerateQuiz && $hasQuizzDureeColumn) {
            $stmtQuizUpdate->bindValue(':duree', (int) $quizForm['duree_minutes'], PDO::PARAM_INT);
          }

          $stmtQuizUpdate->bindValue(':id_quizz', $quizForm['id_quizz'], PDO::PARAM_INT);
          $stmtQuizUpdate->execute();
        }

        if ($hasQuizzFormationsTable) {
          $stmtDel = $pdo->prepare('DELETE FROM quizz_formations WHERE id_quizz = :id_quizz');
          $stmtDel->bindValue(':id_quizz', $quizForm['id_quizz'], PDO::PARAM_INT);
          $stmtDel->execute();

          if ($quizForm['formation_id'] > 0) {
            $stmtLink = $pdo->prepare('INSERT INTO quizz_formations (id_formation, id_quizz) VALUES (:id_formation, :id_quizz)');
            $stmtLink->bindValue(':id_formation', $quizForm['formation_id'], PDO::PARAM_INT);
            $stmtLink->bindValue(':id_quizz', $quizForm['id_quizz'], PDO::PARAM_INT);
            $stmtLink->execute();
          }
        }

        // Delete existing questions and answers
        $sqlDeleteQuestions = 'DELETE FROM questions WHERE id_quizz = :id_quizz';
        $stmtDeleteQuestions = $pdo->prepare($sqlDeleteQuestions);
        $stmtDeleteQuestions->bindValue(':id_quizz', $quizForm['id_quizz'], PDO::PARAM_INT);
        $stmtDeleteQuestions->execute();

        // Insert new questions and answers
        $sqlQuestionInsert = 'INSERT INTO questions (id_quizz, enonce, type, points, ordre)
                              VALUES (:id_quizz, :enonce, :type, :points, :ordre)';
        $stmtQuestionInsert = $pdo->prepare($sqlQuestionInsert);

        $sqlAnswerInsert = 'INSERT INTO reponses (id_question, texte, est_correcte)
                            VALUES (:id_question, :texte, :est_correcte)';
        $stmtAnswerInsert = $pdo->prepare($sqlAnswerInsert);

        for ($q = 0; $q < count($quizQuestionsToInsert); $q++) {
          $questionToSave = $quizQuestionsToInsert[$q];
          $order = $q + 1;

          $stmtQuestionInsert->bindValue(':id_quizz', $quizForm['id_quizz'], PDO::PARAM_INT);
          $stmtQuestionInsert->bindValue(':enonce', $questionToSave['text']);
          $stmtQuestionInsert->bindValue(':type', $questionToSave['type']);
          $stmtQuestionInsert->bindValue(':points', (int) $questionToSave['points'], PDO::PARAM_INT);
          $stmtQuestionInsert->bindValue(':ordre', $order, PDO::PARAM_INT);
          $stmtQuestionInsert->execute();

          $idQuestionCree = (int) $pdo->lastInsertId();
          $answersToSave = $questionToSave['answers'];

          for ($a = 0; $a < count($answersToSave); $a++) {
            $answerToSave = $answersToSave[$a];
            $stmtAnswerInsert->bindValue(':id_question', $idQuestionCree, PDO::PARAM_INT);
            $stmtAnswerInsert->bindValue(':texte', $answerToSave['text']);
            $stmtAnswerInsert->bindValue(':est_correcte', ($answerToSave['is_correct'] ? 1 : 0), PDO::PARAM_INT);
            $stmtAnswerInsert->execute();
          }
        }

        $pdo->commit();

        header('Location: back.php?quiz_updated=1&type=quizzes');
        exit;
      } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $erreur = 'Erreur base de données pendant la mise à jour du quiz.';
      }
    }
  }
  // ADD QUIZ
  elseif ($action === 'add_quiz') {
    $quizForm['titre'] = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
    $quizForm['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
    $quizForm['note_passage'] = isset($_POST['note_passage']) ? trim((string) $_POST['note_passage']) : '60';
    $quizForm['nb_tentatives'] = isset($_POST['nb_tentatives']) ? trim((string) $_POST['nb_tentatives']) : '3';
    $quizForm['duree_minutes'] = isset($_POST['duree_minutes']) ? trim((string) $_POST['duree_minutes']) : '20';
    $quizForm['formation_id'] = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;

    $autoGenerateQuiz = !isset($_POST['quiz_question_text']);
    $quizQuestionsToInsert = [];

    if ($erreur === '' && $quizForm['formation_id'] <= 0) {
      $erreur = 'La formation associée est obligatoire.';
    }

    if ($erreur === '' && !$quizQuestionsEnabled) {
      $erreur = 'Les tables questions/reponses sont nécessaires pour ajouter des questions au quiz.';
    }

    if ($erreur === '' && !$autoGenerateQuiz) {
      $quizBuilderQuestionsInput = parseQuizBuilderQuestions($_POST);

      if ($quizForm['titre'] === '' || strlen($quizForm['titre']) < 3) {
        $erreur = 'Le titre doit contenir au moins 3 caractères.';
      } elseif (!ctype_digit($quizForm['duree_minutes']) || (int) $quizForm['duree_minutes'] <= 0) {
        $erreur = 'La durée doit être un entier supérieur à 0.';
      } elseif (!ctype_digit($quizForm['note_passage']) || (int) $quizForm['note_passage'] < 0 || (int) $quizForm['note_passage'] > 100) {
        $erreur = 'La note de passage doit être un entier entre 0 et 100.';
      } elseif (!ctype_digit($quizForm['nb_tentatives']) || (int) $quizForm['nb_tentatives'] <= 0) {
        $erreur = 'Le nombre de tentatives doit être un entier supérieur à 0.';
      } elseif (count($quizBuilderQuestionsInput) === 0) {
        $erreur = 'Ajoutez au moins une question au quiz.';
      } else {
        for ($q = 0; $q < count($quizBuilderQuestionsInput); $q++) {
          $questionInput = $quizBuilderQuestionsInput[$q];
          $questionText = trim((string) $questionInput['text']);
          $questionType = trim((string) $questionInput['type']);
          $questionPoints = 1;

          if (isset($questionInput['points']) && is_numeric($questionInput['points']) && (int) $questionInput['points'] > 0) {
            $questionPoints = (int) $questionInput['points'];
          }

          if ($questionText === '' || strlen($questionText) < 3) {
            $erreur = 'Chaque question du quiz doit contenir au moins 3 caractères.';
            break;
          }

          if ($questionType !== 'choix_unique' && $questionType !== 'choix_multiple' && $questionType !== 'vrai_faux') {
            $questionType = 'choix_unique';
          }

          if ($questionType === 'vrai_faux') {
            $tfCorrectRaw = 'true';
            if (isset($questionInput['tf_correct']) && trim((string) $questionInput['tf_correct']) === 'false') {
              $tfCorrectRaw = 'false';
            }

            $quizQuestionsToInsert[] = [
              'text' => $questionText,
              'type' => 'vrai_faux',
              'points' => $questionPoints,
              'answers' => [
                ['text' => 'True', 'is_correct' => ($tfCorrectRaw === 'true')],
                ['text' => 'False', 'is_correct' => ($tfCorrectRaw === 'false')]
              ]
            ];
            continue;
          }

          $rawAnswers = [];
          if (isset($questionInput['answers']) && is_array($questionInput['answers'])) {
            $rawAnswers = $questionInput['answers'];
          }

          $cleanAnswers = [];
          $correctCount = 0;

          for ($a = 0; $a < count($rawAnswers); $a++) {
            $answerInput = $rawAnswers[$a];
            $answerText = trim((string) $answerInput['text']);
            if ($answerText === '') {
              continue;
            }

            $isCorrect = isset($answerInput['is_correct']) && $answerInput['is_correct'];
            if ($isCorrect) {
              $correctCount += 1;
            }

            $cleanAnswers[] = [
              'text' => $answerText,
              'is_correct' => $isCorrect
            ];
          }

          if (count($cleanAnswers) < 2) {
            $erreur = 'Chaque question (hors vrai/faux) doit contenir au moins 2 réponses.';
            break;
          }

          if ($correctCount <= 0) {
            $erreur = 'Chaque question doit avoir au moins une bonne réponse.';
            break;
          }

          if ($questionType === 'choix_unique' && $correctCount !== 1) {
            $erreur = 'Une question en choix unique doit avoir exactement une seule bonne réponse.';
            break;
          }

          $quizQuestionsToInsert[] = [
            'text' => $questionText,
            'type' => $questionType,
            'points' => $questionPoints,
            'answers' => $cleanAnswers
          ];
        }
      }
    }

    if ($erreur === '' && $autoGenerateQuiz) {
      try {
        $formationRow = getFormationById($pdo, $quizForm['formation_id']);
        if (!$formationRow) {
          $erreur = 'Formation associée introuvable.';
        } else {
          $formationTitle = trim((string) $formationRow['domaine']);
          $quizForm['titre'] = $formationTitle !== '' ? ('Quiz - ' . $formationTitle) : 'Quiz automatique';
          $quizForm['description'] = '';
          $quizQuestionsToInsert = buildAutoQuizQuestions($formationRow, 5);
        }
      } catch (RuntimeException $e) {
        $erreur = $e->getMessage();
      }
    }

    if ($erreur === '') {
      try {
        $pdo->beginTransaction();

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

        if (count($quizInsertColumns) === 0) {
          throw new PDOException('Aucune colonne disponible pour insertion dans quizz.');
        }

        $sqlQuizInsert = 'INSERT INTO quizz (' . implode(', ', $quizInsertColumns) . ')
                          VALUES (' . implode(', ', $quizInsertValues) . ')';
        $stmtQuizInsert = $pdo->prepare($sqlQuizInsert);

        if ($hasQuizzFormationIdColumn) {
          $stmtQuizInsert->bindValue(':formation_id', $quizForm['formation_id'], PDO::PARAM_INT);
        }
        if ($hasQuizzDomaineColumn) {
          $stmtQuizInsert->bindValue(':domaine', '');
        }
        if ($hasQuizzTitreColumn) {
          $stmtQuizInsert->bindValue(':titre', $quizForm['titre']);
        }
        if ($hasQuizzDescriptionColumn) {
          if ($quizForm['description'] === '') {
            $stmtQuizInsert->bindValue(':description', null, PDO::PARAM_NULL);
          } else {
            $stmtQuizInsert->bindValue(':description', $quizForm['description']);
          }
        }
        if ($hasQuizzQuestionColumn) {
          if ($quizForm['description'] === '') {
            $stmtQuizInsert->bindValue(':question', $quizForm['titre']);
          } else {
            $stmtQuizInsert->bindValue(':question', $quizForm['description']);
          }
        }
        if ($hasQuizzNotePassageColumn) {
          $stmtQuizInsert->bindValue(':note_passage', (int) $quizForm['note_passage'], PDO::PARAM_INT);
        } elseif ($hasQuizzScoreColumn) {
          $stmtQuizInsert->bindValue(':score', (int) $quizForm['note_passage'], PDO::PARAM_INT);
        }
        if ($hasQuizzNbTentativesColumn) {
          $stmtQuizInsert->bindValue(':nb_tentatives', (int) $quizForm['nb_tentatives'], PDO::PARAM_INT);
        }
        if ($hasQuizzDureeMinutesColumn) {
          $stmtQuizInsert->bindValue(':duree_minutes', (int) $quizForm['duree_minutes'], PDO::PARAM_INT);
        } elseif ($hasQuizzDureeColumn) {
          $stmtQuizInsert->bindValue(':duree', (int) $quizForm['duree_minutes'], PDO::PARAM_INT);
        }

        $stmtQuizInsert->execute();

        $idQuizCree = (int) $pdo->lastInsertId();

        if ($hasQuizzFormationsTable && $idQuizCree > 0) {
          $sqlLienQuizFormation = 'INSERT INTO quizz_formations (id_formation, id_quizz)
                                   VALUES (:id_formation, :id_quizz)';
          $stmtLienQuizFormation = $pdo->prepare($sqlLienQuizFormation);
          $stmtLienQuizFormation->bindValue(':id_formation', $quizForm['formation_id'], PDO::PARAM_INT);
          $stmtLienQuizFormation->bindValue(':id_quizz', $idQuizCree, PDO::PARAM_INT);
          $stmtLienQuizFormation->execute();
        }

        $sqlQuestionInsert = 'INSERT INTO questions (id_quizz, enonce, type, points, ordre)
                              VALUES (:id_quizz, :enonce, :type, :points, :ordre)';
        $stmtQuestionInsert = $pdo->prepare($sqlQuestionInsert);

        $sqlAnswerInsert = 'INSERT INTO reponses (id_question, texte, est_correcte)
                            VALUES (:id_question, :texte, :est_correcte)';
        $stmtAnswerInsert = $pdo->prepare($sqlAnswerInsert);

        for ($q = 0; $q < count($quizQuestionsToInsert); $q++) {
          $questionToSave = $quizQuestionsToInsert[$q];
          $order = $q + 1;

          $stmtQuestionInsert->bindValue(':id_quizz', $idQuizCree, PDO::PARAM_INT);
          $stmtQuestionInsert->bindValue(':enonce', $questionToSave['text']);
          $stmtQuestionInsert->bindValue(':type', $questionToSave['type']);
          $stmtQuestionInsert->bindValue(':points', (int) $questionToSave['points'], PDO::PARAM_INT);
          $stmtQuestionInsert->bindValue(':ordre', $order, PDO::PARAM_INT);
          $stmtQuestionInsert->execute();

          $idQuestionCree = (int) $pdo->lastInsertId();
          $answersToSave = $questionToSave['answers'];

          for ($a = 0; $a < count($answersToSave); $a++) {
            $answerToSave = $answersToSave[$a];
            $stmtAnswerInsert->bindValue(':id_question', $idQuestionCree, PDO::PARAM_INT);
            $stmtAnswerInsert->bindValue(':texte', $answerToSave['text']);
            $stmtAnswerInsert->bindValue(':est_correcte', ($answerToSave['is_correct'] ? 1 : 0), PDO::PARAM_INT);
            $stmtAnswerInsert->execute();
          }
        }

        $pdo->commit();

        header('Location: back.php?quiz_ok=1&type=quizzes');
        exit;
      } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
          $pdo->rollBack();
        }
        $erreur = 'Erreur base de données pendant l\'ajout du quiz.';
      }
    }
    if ($erreur !== '') {
      $openQuizModal = true;
    }
  }
  // DELETE FORMATION
  elseif ($action === 'delete_formation') {
    $idFormation = isset($_POST['id_formation']) ? (int) $_POST['id_formation'] : 0;

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
  // DELETE WORKSHOP
  elseif ($action === 'delete_workshop') {
    $idWorkshop = isset($_POST['id_workshop']) ? (int) $_POST['id_workshop'] : 0;

    if ($idWorkshop <= 0) {
      $erreur = 'Identifiant de workshop invalide.';
    }

    if ($erreur === '') {
      try {
        $sqlDelete = 'DELETE FROM workshops WHERE id_workshop = :id';
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->bindValue(':id', $idWorkshop, PDO::PARAM_INT);
        $stmtDelete->execute();

        header('Location: back.php?workshop_deleted=1');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant la suppression.';
      }
    }
  }
  // DELETE QUIZ
  elseif ($action === 'delete_quiz') {
    $idQuiz = isset($_POST['id_quiz']) ? (int) $_POST['id_quiz'] : 0;

    if ($idQuiz <= 0) {
      $erreur = 'Identifiant de quiz invalide.';
    }

    if ($erreur === '') {
      try {
        $sqlDelete = 'DELETE FROM quizz WHERE id_quizz = :id';
        $stmtDelete = $pdo->prepare($sqlDelete);
        $stmtDelete->bindValue(':id', $idQuiz, PDO::PARAM_INT);
        $stmtDelete->execute();

        header('Location: back.php?quiz_deleted=1&type=quizzes');
        exit;
      } catch (PDOException $e) {
        $erreur = 'Erreur base de données pendant la suppression.';
      }
    }
  }
}

// Load data for edit mode
if ($afficherFormUpdate === false && isset($_GET['edit_formation']) && is_numeric($_GET['edit_formation'])) {
  $idFormationEditGet = (int) $_GET['edit_formation'];
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
      $updateForm['certification'] = certifOui($formationEdit['certification']) ? 'oui' : 'non';
      $updateForm['etat'] = trim((string) $formationEdit['etat']);
      if ($updateForm['etat'] !== 'Actif' && $updateForm['etat'] !== 'Inactif' && $updateForm['etat'] !== 'Brouillon') {
        $updateForm['etat'] = 'Actif';
      }
      $updateForm['date_realisation'] = trim((string) $formationEdit['date_realisation']);
      $updateForm['lien_video'] = trim((string) ($formationEdit['video_url'] ?? ''));
    } else {
      $erreur = 'Formation introuvable pour modification.';
    }
  } catch (PDOException $e) {
    $erreur = 'Erreur base de données lors du chargement de la formation.';
  }
}

if (isset($_GET['edit_workshop']) && is_numeric($_GET['edit_workshop'])) {
  $editWorkshopId = (int) $_GET['edit_workshop'];
  try {
    $sql = 'SELECT w.id_workshop, w.titre, w.description, w.mentor_id, w.duree, w.date_atelier, w.lieu, w.places_max, w.prix, w.certification, w.statut, wf.id_formation
            FROM workshops w
            LEFT JOIN workshops_formation wf ON wf.id_workshop = w.id_workshop
            WHERE w.id_workshop = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $editWorkshopId, PDO::PARAM_INT);
    $stmt->execute();
    $workshopData = $stmt->fetch();
    if ($workshopData) {
      $workshopForm['id_workshop'] = (int) $workshopData['id_workshop'];
      $workshopForm['titre'] = trim((string) $workshopData['titre']);
      $workshopForm['description'] = trim((string) $workshopData['description']);
      $workshopForm['mentor_id'] = $workshopData['mentor_id'] ? (string) $workshopData['mentor_id'] : '';
      $workshopForm['duree'] = (string) ((int) $workshopData['duree']);
      $workshopForm['date_atelier'] = $workshopData['date_atelier'] ? date('Y-m-d\TH:i', strtotime($workshopData['date_atelier'])) : '';
      $workshopForm['lieu'] = trim((string) $workshopData['lieu']);
      $workshopForm['places_max'] = (string) ((int) $workshopData['places_max']);
      $workshopForm['prix'] = (string) $workshopData['prix'];
      $workshopForm['certification'] = certifOui($workshopData['certification']) ? 'oui' : 'non';
      $workshopForm['statut'] = trim((string) $workshopData['statut']);
      $workshopForm['formation_id'] = (int) ($workshopData['id_formation'] ?? 0);
      $openWorkshopModal = true;
    }
  } catch (PDOException $e) {}
}

if (isset($_GET['edit_quiz']) && is_numeric($_GET['edit_quiz'])) {
  $editQuizId = (int) $_GET['edit_quiz'];
  try {
    // Build SELECT dynamically based on available columns
    $editQuizNoteCol = $hasQuizzNotePassageColumn ? 'note_passage' : ($hasQuizzScoreColumn ? 'score AS note_passage' : 'NULL AS note_passage');
    $editQuizTentCol = $hasQuizzNbTentativesColumn ? 'nb_tentatives' : 'NULL AS nb_tentatives';
    $editQuizDureeCol = $hasQuizzDureeMinutesColumn ? 'duree_minutes' : ($hasQuizzDureeColumn ? 'duree AS duree_minutes' : 'NULL AS duree_minutes');
    $editQuizDescCol = $hasQuizzDescriptionColumn ? 'description' : 'NULL AS description';
    $editQuizFormationCol = $hasQuizzFormationIdColumn ? 'formation_id' : 'NULL AS formation_id';

    $sql = 'SELECT id_quizz, titre, ' . $editQuizDescCol . ', ' . $editQuizNoteCol . ', '
         . $editQuizTentCol . ', ' . $editQuizDureeCol . ', ' . $editQuizFormationCol
         . ' FROM quizz WHERE id_quizz = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $editQuizId, PDO::PARAM_INT);
    $stmt->execute();
    $quizData = $stmt->fetch();
    if ($quizData) {
      $quizForm['id_quizz'] = (int) $quizData['id_quizz'];
      $quizForm['titre'] = trim((string) $quizData['titre']);
      $quizForm['description'] = trim((string) $quizData['description']);
      $quizForm['note_passage'] = (string) ((int) $quizData['note_passage']);
      $quizForm['nb_tentatives'] = (string) ((int) $quizData['nb_tentatives']);
      $quizForm['duree_minutes'] = (string) ((int) $quizData['duree_minutes']);
      $quizForm['formation_id'] = (int) $quizData['formation_id'];
      
      // Load existing questions for editing
      $sqlQuestions = 'SELECT id_question, enonce, type, points, ordre FROM questions WHERE id_quizz = :id_quizz ORDER BY ordre ASC';
      $stmtQuestions = $pdo->prepare($sqlQuestions);
      $stmtQuestions->bindValue(':id_quizz', $editQuizId, PDO::PARAM_INT);
      $stmtQuestions->execute();
      $existingQuestions = $stmtQuestions->fetchAll();
      
      $oldQuizBuilderQuestions = [];
      if (count($existingQuestions) > 0) {
        for ($qi = 0; $qi < count($existingQuestions); $qi++) {
          $qData = $existingQuestions[$qi];
          $sqlAnswers = 'SELECT id_reponse, texte, est_correcte FROM reponses WHERE id_question = :id_question';
          $stmtAnswers = $pdo->prepare($sqlAnswers);
          $stmtAnswers->bindValue(':id_question', $qData['id_question'], PDO::PARAM_INT);
          $stmtAnswers->execute();
          $answerRows = $stmtAnswers->fetchAll();
          
          $answers = [];
          for ($ai = 0; $ai < count($answerRows); $ai++) {
            $answers[] = [
              'key' => (string) $answerRows[$ai]['id_reponse'],
              'text' => trim((string) $answerRows[$ai]['texte']),
              'is_correct' => (bool) $answerRows[$ai]['est_correcte']
            ];
          }
          
          $oldQuizBuilderQuestions[] = [
            'key' => (string) $qi,
            'text' => trim((string) $qData['enonce']),
            'type' => trim((string) $qData['type']),
            'points' => (int) $qData['points'],
            'tf_correct' => 'true',
            'answers' => $answers
          ];
        }
        $nextQuizBuilderQuestionIndex = count($oldQuizBuilderQuestions);
      } else {
        $oldQuizBuilderQuestions = defaultQuizBuilderQuestions();
        $nextQuizBuilderQuestionIndex = 1;
      }
      $openQuizModal = true;
    }
  } catch (PDOException $e) {}
}

$recherche = isset($_GET['q']) ? trim($_GET['q']) : '';
$niveauFiltre = isset($_GET['niveau']) ? trim($_GET['niveau']) : '';
$niveauFiltreDb = '';
$typeFiltre = isset($_GET['type']) ? trim($_GET['type']) : 'formations';

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

// Load workshops for display
$workshops = [];
try {
    $sqlWorkshops = 'SELECT w.id_workshop, w.titre, w.description, w.mentor_id, w.duree, w.date_atelier, w.lieu, w.places_max, w.places_restantes, w.prix, w.certification, w.statut,
                            TRIM(CONCAT(COALESCE(u.prenom, ""), " ", COALESCE(u.nom, ""))) AS mentor_nom,
                            f.domaine AS formation_titre, wf.id_formation
                     FROM workshops w
                     LEFT JOIN `user` u ON u.id_user = w.mentor_id
                     LEFT JOIN workshops_formation wf ON wf.id_workshop = w.id_workshop
                     LEFT JOIN formations f ON f.id_formation = wf.id_formation
                     ORDER BY w.id_workshop DESC';
    $stmtWorkshops = $pdo->query($sqlWorkshops);
    $workshops = $stmtWorkshops->fetchAll();
} catch (PDOException $e) {}

// Load quizzes for display - using flexible join to support both formation_id column and quizz_formations pivot table
$quizzes = [];
try {
    $quizNotePassageExprList = 'NULL AS note_passage';
    if ($hasQuizzNotePassageColumn) {
        $quizNotePassageExprList = 'q.note_passage';
    } elseif ($hasQuizzScoreColumn) {
        $quizNotePassageExprList = 'q.score AS note_passage';
    }

    $quizNbTentativesExprList = 'NULL AS nb_tentatives';
    if ($hasQuizzNbTentativesColumn) {
        $quizNbTentativesExprList = 'q.nb_tentatives';
    }

    $quizDureeExprList = 'NULL AS duree_minutes';
    if ($hasQuizzDureeMinutesColumn) {
        $quizDureeExprList = 'q.duree_minutes';
    } elseif ($hasQuizzDureeColumn) {
        $quizDureeExprList = 'q.duree AS duree_minutes';
    }

    $quizDescriptionExprList = 'NULL AS description';
    if ($hasQuizzDescriptionColumn) {
        $quizDescriptionExprList = 'q.description';
    }

    $questionCountExprList = '0 AS nb_questions';
    if ($hasQuizQuestionsTable) {
        $questionCountExprList = '(SELECT COUNT(*) FROM questions qq WHERE qq.id_quizz = q.id_quizz) AS nb_questions';
    }

    // Build the formation link expression
    $quizListLinkParts = [];
    if ($hasQuizzFormationIdColumn) {
        $quizListLinkParts[] = 'SELECT q2.id_quizz, q2.formation_id FROM quizz q2 WHERE q2.formation_id IS NOT NULL';
    }
    if ($hasQuizzFormationsTable) {
        $quizListLinkParts[] = 'SELECT qf2.id_quizz, qf2.id_formation AS formation_id FROM quizz_formations qf2';
    }

    if (count($quizListLinkParts) > 0) {
        $sqlQuizzes = 'SELECT q.id_quizz, q.titre,
                              ' . $quizDescriptionExprList . ',
                              ' . $quizNotePassageExprList . ',
                              ' . $quizNbTentativesExprList . ',
                              ' . $quizDureeExprList . ',
                              ' . $questionCountExprList . ',
                              lien.formation_id,
                              f.domaine AS formation_titre
                       FROM quizz q
                       LEFT JOIN (
                           SELECT id_quizz, MIN(formation_id) AS formation_id
                           FROM (' . implode(' UNION ALL ', $quizListLinkParts) . ') merged
                           GROUP BY id_quizz
                       ) lien ON lien.id_quizz = q.id_quizz
                       LEFT JOIN formations f ON f.id_formation = lien.formation_id
                       ORDER BY q.id_quizz DESC';
    } else {
        $sqlQuizzes = 'SELECT q.id_quizz, q.titre,
                              ' . $quizDescriptionExprList . ',
                              ' . $quizNotePassageExprList . ',
                              ' . $quizNbTentativesExprList . ',
                              ' . $quizDureeExprList . ',
                              ' . $questionCountExprList . ',
                              NULL AS formation_id, NULL AS formation_titre
                       FROM quizz q
                       ORDER BY q.id_quizz DESC';
    }

    $stmtQuizzes = $pdo->prepare($sqlQuizzes);
    $stmtQuizzes->execute();
    $quizzes = $stmtQuizzes->fetchAll();
} catch (PDOException $e) {}
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
.admin-tabs {
  display: flex;
  gap: 0;
  margin-bottom: 24px;
  border-bottom: 2px solid var(--caramel);
  flex-wrap: wrap;
}
.admin-tab {
  padding: 10px 20px;
  background: transparent;
  border: none;
  cursor: pointer;
  font-weight: 600;
  color: var(--gris);
  transition: all 0.3s;
  font-size: 1rem;
}
.admin-tab.active {
  color: var(--marron);
  border-bottom: 3px solid var(--marron);
  margin-bottom: -2px;
}
.admin-tab:hover {
  color: var(--brun);
}
.tab-content {
  display: none;
}
.tab-content.active {
  display: block;
}
.table-container {
  overflow-x: auto;
}
.workshop-mentor-cell, .quiz-formation-cell {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
/* Quiz Builder Styles */
.quiz-builder-wrap {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.quiz-builder-question {
  border: 1px solid rgba(196,154,108,0.35);
  border-radius: 8px;
  background: rgba(245,236,215,0.5);
  padding: 10px;
}

.quiz-builder-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.quiz-builder-head strong {
  color: var(--marron);
  font-size: 0.85rem;
}

.quiz-remove-btn {
  padding: 6px 10px;
  font-size: 0.75rem;
  background: #c0392b;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}

.quiz-remove-btn:hover {
  background: #a93226;
}

.quiz-builder-answer-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 6px;
}

.quiz-builder-answer-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 8px;
  align-items: center;
}

.quiz-builder-answer-row input[type="text"] {
  padding: 6px 8px;
  border: 1px solid rgba(196,154,108,0.5);
  border-radius: 4px;
  font-size: 0.85rem;
}

.quiz-builder-answer-row label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: var(--gris);
  white-space: nowrap;
}

.quiz-builder-actions {
  display: flex;
  justify-content: flex-start;
  margin-top: 4px;
}

.btn-inscrit.vert {
  background: #27ae60;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.8rem;
  transition: background 0.2s;
}

.btn-inscrit.vert:hover {
  background: #219a52;
}

.quiz-builder-hidden {
  display: none;
}

.front-form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.front-form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.btn-cancel {
  border: 1px solid rgba(196,154,108,0.5);
  color: var(--gris);
  background: transparent;
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-cancel:hover {
  background: rgba(196,154,108,0.1);
}

.btn-save {
  border: none;
  color: var(--creme);
  background: var(--marron);
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-save:hover {
  background: var(--brun);
}

@media (max-width: 800px) {
  .front-form-row-2,
  .front-form-row-3 {
    grid-template-columns: 1fr;
  }
  .admin-tab {
    padding: 8px 12px;
    font-size: 0.85rem;
  }
}
.front-form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
.front-form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 12px;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}
@media (max-width: 800px) {
  .front-form-row-2,
  .front-form-row-3 {
    grid-template-columns: 1fr;
  }
  .admin-tab {
    padding: 8px 12px;
    font-size: 0.85rem;
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
    <a class="nav-item active" href="back.php"><span class="icon">📜</span> BackOffice</a>
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
      Modules &rsaquo; <strong>BackOffice</strong>
    </div>
    <div class="topbar-actions simple-actions">
      <a class="btn-primary btn-link" href="back.php?open_add_formation=1">＋ Ajouter une formation</a>
      <a class="btn-primary btn-link" href="back.php?open_add_workshop=1">＋ Ajouter un workshop</a>
      <a class="btn-primary btn-link" href="back.php?open_add_quiz=1">＋ Ajouter un quiz</a>
      <a class="btn-primary btn-link" href="front.php">Voir le front</a>
    </div>
  </div>

  <div class="content">
    <?php if ($erreur !== ''): ?>
      <div class="inline-alert error"><?php echo e($erreur); ?></div>
    <?php endif; ?>

    <?php if ($succes !== ''): ?>
      <div class="inline-alert success"><?php echo e($succes); ?></div>
    <?php endif; ?>

    <!-- UPDATE FORMATION FORM (appears when editing a formation) -->
    <?php if ($afficherFormUpdate): ?>
      <div class="simple-panel">
        <h3>Modifier la formation #<?php echo e((string) $updateForm['id_formation']); ?></h3>
        <form method="post" action="back.php" id="formBackUpdateFormation" novalidate>
          <input type="hidden" name="action" value="update_formation">
          <input type="hidden" name="id_formation" value="<?php echo e((string) $updateForm['id_formation']); ?>">
          <div class="form-feedback" data-form-message></div>

          <div class="front-form-row-2">
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

          <div class="front-form-row-3">
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

          <div class="front-form-row-3">
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
            </div>
            <div class="form-group">
              <label>Lien vidéo (optionnel)</label>
              <input type="url" name="lien_video" value="<?php echo e($updateForm['lien_video']); ?>" placeholder="https://...">
            </div>
          </div>

          <div class="form-group">
            <label>Date de réalisation</label>
            <input type="date" name="date_realisation" value="<?php echo e($updateForm['date_realisation']); ?>">
          </div>

          <div class="simple-actions">
            <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            <a href="back.php" class="btn-primary btn-link">Annuler</a>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <!-- Admin Tabs -->
    <div class="admin-tabs">
      <button class="admin-tab <?php echo ($typeFiltre === 'formations' ? 'active' : ''); ?>" data-tab="formations">📜 Formations</button>
      <button class="admin-tab <?php echo ($typeFiltre === 'workshops' ? 'active' : ''); ?>" data-tab="workshops">🔧 Workshops</button>
      <button class="admin-tab <?php echo ($typeFiltre === 'quizzes' ? 'active' : ''); ?>" data-tab="quizzes">📝 Quiz</button>
    </div>

    <!-- FORMATIONS TAB -->
    <div id="tab-formations" class="tab-content <?php echo ($typeFiltre === 'formations' ? 'active' : ''); ?>">
      <div class="section-head">
        <h2>Liste des formations</h2>
      </div>

      <form method="get" action="back.php" class="toolbar" id="formBackToolbarFilters" novalidate>
        <input type="hidden" name="type" value="formations">
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
        <a href="back.php?type=formations" class="btn-primary btn-link">Réinitialiser</a>
      </form>

      <div class="table-container">
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
                        <input type="hidden" name="edit_formation" value="<?php echo e((string) $f['id_formation']); ?>">
                        <button type="submit" class="btn-action btn-edit">Modifier</button>
                      </form>
                      <form method="post" action="back.php" onsubmit="return confirm('Supprimer cette formation ?');">
                        <input type="hidden" name="action" value="delete_formation">
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
      </div>
    </div>

    <!-- WORKSHOPS TAB -->
    <div id="tab-workshops" class="tab-content <?php echo ($typeFiltre === 'workshops' ? 'active' : ''); ?>">
      <div class="section-head">
        <h2>Liste des workshops</h2>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Description</th>
              <th>Mentor</th>
              <th>Formation associée</th>
              <th>Durée</th>
              <th>Date</th>
              <th>Lieu</th>
              <th>Places</th>
              <th>Prix</th>
              <th>Certif.</th>
              <th>Statut</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($workshops) === 0): ?>
              <tr>
                <td colspan="13" style="text-align:center;padding:20px;">Aucun workshop trouvé.</td>
              </tr>
            <?php else: ?>
              <?php for ($i = 0; $i < count($workshops); $i++): ?>
                <?php $w = $workshops[$i]; ?>
                <tr>
                  <td>#<?php echo e($w['id_workshop']); ?></td>
                  <td><strong><?php echo e($w['titre']); ?></strong></td>
                  <td class="workshop-mentor-cell" title="<?php echo e($w['description']); ?>"><?php echo e(mb_substr($w['description'], 0, 50)); ?>...</td>
                  <td><?php echo e($w['mentor_nom'] ?: ($w['mentor_id'] ? 'ID: ' . $w['mentor_id'] : '—')); ?></td>
                  <td><?php echo e($w['formation_titre'] ?: ($w['id_formation'] ? 'ID: ' . $w['id_formation'] : '—')); ?></td>
                  <td><?php echo e((string) $w['duree']); ?>h</td>
                  <td><?php echo e($w['date_atelier'] ? date('d/m/Y H:i', strtotime($w['date_atelier'])) : '—'); ?></td>
                  <td><?php echo e($w['lieu']); ?></td>
                  <td><?php echo e((string) $w['places_max']); ?></td>
                  <td><?php echo e((string) $w['prix']); ?> TND</td>
                  <td><?php echo (certifOui($w['certification']) ? '✅' : '—'); ?></td>
                  <td>
                    <span class="badge <?php echo e(statutBadgeClass($w['statut'])); ?>"><?php echo e($w['statut']); ?></span>
                  </td>
                  <td>
                    <div class="action-cell">
                      <form method="get" action="back.php">
                        <input type="hidden" name="edit_workshop" value="<?php echo e((string) $w['id_workshop']); ?>">
                        <button type="submit" class="btn-action btn-edit">Modifier</button>
                      </form>
                      <form method="post" action="back.php" onsubmit="return confirm('Supprimer ce workshop ?');">
                        <input type="hidden" name="action" value="delete_workshop">
                        <input type="hidden" name="id_workshop" value="<?php echo e((string) $w['id_workshop']); ?>">
                        <button type="submit" class="btn-action btn-suppr">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endfor; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- QUIZZES TAB -->
    <div id="tab-quizzes" class="tab-content <?php echo ($typeFiltre === 'quizzes' ? 'active' : ''); ?>">
      <div class="section-head">
        <h2>Liste des quiz</h2>
      </div>

      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Description</th>
              <th>Formation associée</th>
              <th>Note de passage</th>
              <th>Tentatives</th>
              <th>Durée</th>
              <th>Questions</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($quizzes) === 0): ?>
              <tr>
                <td colspan="9" style="text-align:center;padding:20px;">Aucun quiz trouvé.</td>
              </tr>
            <?php else: ?>
              <?php for ($i = 0; $i < count($quizzes); $i++): ?>
                <?php $quizItem = $quizzes[$i]; ?>
                <tr>
                  <td>#<?php echo e($quizItem['id_quizz']); ?></td>
                  <td><strong><?php echo e($quizItem['titre']); ?></strong></td>
                  <td class="workshop-mentor-cell" title="<?php echo e((string) $quizItem['description']); ?>">
                    <?php
                      $quizDesc = trim((string) $quizItem['description']);
                      echo $quizDesc !== '' ? e(mb_substr($quizDesc, 0, 55)) . (mb_strlen($quizDesc) > 55 ? '…' : '') : '<span style="color:var(--gris);">—</span>';
                    ?>
                  </td>
                  <td class="quiz-formation-cell">
                    <?php if (trim((string) $quizItem['formation_titre']) !== ''): ?>
                      <strong><?php echo e($quizItem['formation_titre']); ?></strong>
                      <br><span style="color:var(--gris);font-size:0.75rem;">ID: <?php echo e((string) $quizItem['formation_id']); ?></span>
                    <?php elseif ($quizItem['formation_id']): ?>
                      <span style="color:var(--gris);">ID: <?php echo e((string) $quizItem['formation_id']); ?></span>
                    <?php else: ?>
                      <span style="color:var(--gris);">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $np = $quizItem['note_passage']; ?>
                    <?php if (is_numeric($np)): ?>
                      <span class="badge badge-actif"><?php echo e((string) (int) $np); ?>%</span>
                    <?php else: ?>
                      <span style="color:var(--gris);">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php $nt = $quizItem['nb_tentatives']; ?>
                    <?php echo is_numeric($nt) ? e((string) (int) $nt) : '<span style="color:var(--gris);">—</span>'; ?>
                  </td>
                  <td>
                    <?php $dm = $quizItem['duree_minutes']; ?>
                    <?php echo is_numeric($dm) && (int) $dm > 0 ? e((string) (int) $dm) . ' min' : '<span style="color:var(--gris);">—</span>'; ?>
                  </td>
                  <td>
                    <?php $nbQ = (int) ($quizItem['nb_questions'] ?? 0); ?>
                    <?php if ($nbQ > 0): ?>
                      <span class="badge badge-inter"><?php echo e((string) $nbQ); ?> question<?php echo $nbQ > 1 ? 's' : ''; ?></span>
                    <?php else: ?>
                      <span style="color:var(--gris);">Aucune</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="action-cell">
                      <form method="get" action="back.php">
                        <input type="hidden" name="edit_quiz" value="<?php echo e((string) $quizItem['id_quizz']); ?>">
                        <input type="hidden" name="type" value="quizzes">
                        <button type="submit" class="btn-action btn-edit">Modifier</button>
                      </form>
                      <form method="post" action="back.php" onsubmit="return confirm('Supprimer ce quiz ?');">
                        <input type="hidden" name="action" value="delete_quiz">
                        <input type="hidden" name="id_quiz" value="<?php echo e((string) $quizItem['id_quizz']); ?>">
                        <button type="submit" class="btn-action btn-suppr">Supprimer</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endfor; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AJOUT FORMATION -->
<div class="modal-overlay<?php echo ($openFormationModal ? ' open' : ''); ?>" id="modalAjoutFormation" onclick="fermerModalSiExterieur(event, 'modalAjoutFormation')">
  <div class="modal">
    <div class="modal-header">
      <div>
        <h2>Ajouter une formation</h2>
        <p>Les données seront enregistrées dans la base</p>
      </div>
      <button class="modal-close" type="button" onclick="fermerModal('modalAjoutFormation')">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" action="back.php" id="formAjoutFormation" novalidate>
        <input type="hidden" name="action" value="add_formation">
        <div class="form-feedback" data-form-message></div>

        <div class="front-form-row-2">
          <div class="form-group">
            <label>Titre *</label>
            <input type="text" name="titre" value="" placeholder="Ex: Initiation à la poterie" required>
          </div>
          <div class="form-group">
            <label>Mentor *</label>
            <input type="text" name="mentor" value="" placeholder="Ex: Fatma Ayari" required>
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3" placeholder="Description de la formation"></textarea>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Niveau *</label>
            <select name="niveau">
              <option value="debutant">Débutant</option>
              <option value="intermediaire">Intermédiaire</option>
              <option value="avance">Avancé</option>
            </select>
          </div>
          <div class="form-group">
            <label>Durée (heures) *</label>
            <input type="text" name="duree" placeholder="Ex: 24" required>
          </div>
          <div class="form-group">
            <label>Prix (TND) *</label>
            <input type="text" name="prix" placeholder="Ex: 180" required>
          </div>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Certification</label>
            <select name="certification">
              <option value="oui">Oui</option>
              <option value="non">Non</option>
            </select>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select name="etat">
              <option value="Actif">Actif</option>
              <option value="Inactif">Inactif</option>
              <option value="Brouillon">Brouillon</option>
            </select>
          </div>
          <div class="form-group">
            <label>Date de réalisation</label>
            <input type="date" name="date_realisation">
          </div>
        </div>

        <div class="form-group">
          <label>Lien vidéo (optionnel)</label>
          <input type="url" name="lien_video" placeholder="https://...">
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="fermerModal('modalAjoutFormation')">Annuler</button>
          <button type="submit" class="btn-save">Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL AJOUT WORKSHOP -->
<div class="modal-overlay<?php echo ($openWorkshopModal ? ' open' : ''); ?>" id="modalAjoutWorkshop" onclick="fermerModalSiExterieur(event, 'modalAjoutWorkshop')">
  <div class="modal">
    <div class="modal-header">
      <div>
        <h2><?php echo ($editWorkshopId > 0 ? 'Modifier' : 'Ajouter'); ?> un workshop</h2>
        <p>Les données seront enregistrées dans la base</p>
      </div>
      <button class="modal-close" type="button" onclick="fermerModal('modalAjoutWorkshop')">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" action="back.php" id="formAjoutWorkshop" novalidate>
        <input type="hidden" name="action" value="<?php echo ($editWorkshopId > 0 ? 'update_workshop' : 'add_workshop'); ?>">
        <?php if ($editWorkshopId > 0): ?>
          <input type="hidden" name="id_workshop" value="<?php echo e((string) $workshopForm['id_workshop']); ?>">
        <?php endif; ?>
        <div class="form-feedback" data-form-message></div>

        <div class="form-group">
          <label>Titre workshop *</label>
          <input type="text" name="titre" value="<?php echo e($workshopForm['titre']); ?>" placeholder="Ex: Atelier pratique de poterie" required>
        </div>

        <div class="form-group">
          <label>Mentor ID (optionnel)</label>
          <input type="number" min="1" step="1" name="mentor_id" value="<?php echo e($workshopForm['mentor_id']); ?>" placeholder="Ex: 3">
        </div>

        <div class="form-group">
          <label>Formation associée *</label>
          <select name="formation_id" required>
            <option value="">-- Sélectionnez une formation --</option>
            <?php for ($fi = 0; $fi < count($formations); $fi++): ?>
              <?php $f = $formations[$fi]; ?>
              <option value="<?php echo e((string) $f['id_formation']); ?>" <?php echo ($workshopForm['formation_id'] == $f['id_formation'] ? 'selected' : ''); ?>>
                <?php echo e($f['domaine']); ?> (ID: <?php echo e((string) $f['id_formation']); ?>)
              </option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group">
          <label>Description workshop *</label>
          <textarea name="description" rows="3" placeholder="Description du workshop" required><?php echo e($workshopForm['description']); ?></textarea>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Durée workshop (heures) *</label>
            <input type="number" min="1" step="1" name="duree" value="<?php echo e($workshopForm['duree']); ?>" placeholder="Ex: 2" required>
          </div>
          <div class="form-group">
            <label>Date atelier (optionnel)</label>
            <input type="datetime-local" name="date_atelier" value="<?php echo e($workshopForm['date_atelier']); ?>">
          </div>
          <div class="form-group">
            <label>Lieu workshop *</label>
            <input type="text" name="lieu" value="<?php echo e($workshopForm['lieu']); ?>" placeholder="Ex: Tunis" required>
          </div>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Places max workshop *</label>
            <input type="number" min="1" step="1" name="places_max" value="<?php echo e($workshopForm['places_max']); ?>" placeholder="Ex: 20" required>
          </div>
          <div class="form-group">
            <label>Prix workshop (TND) *</label>
            <input type="number" min="0" step="0.01" name="prix" value="<?php echo e($workshopForm['prix']); ?>" placeholder="Ex: 120" required>
          </div>
          <div class="form-group">
            <label>Certification workshop</label>
            <select name="certification">
              <option value="oui" <?php echo ($workshopForm['certification'] === 'oui' ? 'selected' : ''); ?>>Oui</option>
              <option value="non" <?php echo ($workshopForm['certification'] === 'non' ? 'selected' : ''); ?>>Non</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Statut workshop</label>
          <select name="statut">
            <option value="a_venir" <?php echo ($workshopForm['statut'] === 'a_venir' ? 'selected' : ''); ?>>A venir</option>
            <option value="en_cours" <?php echo ($workshopForm['statut'] === 'en_cours' ? 'selected' : ''); ?>>En cours</option>
            <option value="termine" <?php echo ($workshopForm['statut'] === 'termine' ? 'selected' : ''); ?>>Termine</option>
            <option value="annule" <?php echo ($workshopForm['statut'] === 'annule' ? 'selected' : ''); ?>>Annule</option>
          </select>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="fermerModal('modalAjoutWorkshop')">Annuler</button>
          <button type="submit" class="btn-save"><?php echo ($editWorkshopId > 0 ? 'Mettre à jour' : 'Ajouter'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL AJOUT/MODIFICATION QUIZ -->
<div class="modal-overlay<?php echo ($openQuizModal ? ' open' : ''); ?>" id="modalAjoutQuiz" onclick="fermerModalSiExterieur(event, 'modalAjoutQuiz')">
  <div class="modal">
    <div class="modal-header">
      <div>
        <h2><?php echo ($editQuizId > 0 ? 'Modifier' : 'Ajouter'); ?> un quiz</h2>
        <p>Les données seront enregistrées dans la base</p>
      </div>
      <button class="modal-close" type="button" onclick="fermerModal('modalAjoutQuiz')">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" action="back.php" id="formAjoutQuiz" novalidate>
        <input type="hidden" name="action" value="<?php echo ($editQuizId > 0 ? 'update_quiz' : 'add_quiz'); ?>">
        <?php if ($editQuizId > 0): ?>
          <input type="hidden" name="id_quizz" value="<?php echo e((string) $quizForm['id_quizz']); ?>">
        <?php endif; ?>
        <div class="form-feedback" data-form-message></div>

        <div class="form-group">
          <label>Formation associée *</label>
          <select name="formation_id" required>
            <option value="">-- Sélectionnez une formation --</option>
            <?php for ($fi = 0; $fi < count($formations); $fi++): ?>
              <?php $f = $formations[$fi]; ?>
              <option value="<?php echo e((string) $f['id_formation']); ?>" <?php echo ($quizForm['formation_id'] == $f['id_formation'] ? 'selected' : ''); ?>>
                <?php echo e($f['domaine']); ?> (ID: <?php echo e((string) $f['id_formation']); ?>)
              </option>
            <?php endfor; ?>
          </select>
        </div>
        <p class="form-note">Le quiz est généré automatiquement via Groq à partir de la formation sélectionnée (5 questions). La validation régénère les questions.</p>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="fermerModal('modalAjoutQuiz')">Annuler</button>
          <button type="submit" class="btn-save"><?php echo ($editQuizId > 0 ? 'Regenerer' : 'Generer'); ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.admin-tab').forEach(function(tab) {
  tab.addEventListener('click', function() {
    var tabName = this.getAttribute('data-tab');
    document.querySelectorAll('.admin-tab').forEach(function(t) { t.classList.remove('active'); });
    this.classList.add('active');
    document.querySelectorAll('.tab-content').forEach(function(content) { content.classList.remove('active'); });
    document.getElementById('tab-' + tabName).classList.add('active');
    
    var url = new URL(window.location.href);
    url.searchParams.set('type', tabName);
    url.searchParams.delete('edit_formation');
    url.searchParams.delete('edit_workshop');
    url.searchParams.delete('edit_quiz');
    window.history.replaceState({}, '', url);
  });
});

function fermerModal(modalId) {
  var modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.remove('open');
  }
  var url = new URL(window.location.href);
  url.searchParams.delete('open_add_formation');
  url.searchParams.delete('open_add_workshop');
  url.searchParams.delete('open_add_quiz');
  url.searchParams.delete('edit_formation');
  url.searchParams.delete('edit_workshop');
  url.searchParams.delete('edit_quiz');
  window.history.replaceState({}, '', url);
}

function fermerModalSiExterieur(event, modalId) {
  if (event.target && event.target.classList.contains('modal-overlay')) {
    fermerModal(modalId);
  }
}

// Quiz Builder Functions
function quizBuilderEscapeHtml(value) {
  var text = String(value);
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#39;');
}

function quizBuilderAnswerRowMarkup(questionIndex, answerIndex, answerText, isCorrect) {
  var checked = isCorrect ? ' checked' : '';
  return '<div class="quiz-builder-answer-row"><input type="text" name="quiz_answer_text[' + questionIndex + '][' + answerIndex + ']" value="' + quizBuilderEscapeHtml(answerText) + '" placeholder="Texte de la réponse"><label><input type="checkbox" name="quiz_answer_correct[' + questionIndex + '][]" value="' + answerIndex + '"' + checked + '>Bonne réponse</label></div>';
}

function refreshQuizBuilderQuestionLabels() {
  var container = document.getElementById('quizBuilderQuestions');
  if (!container) return;
  var cards = container.querySelectorAll('.quiz-builder-question');
  for (var i = 0; i < cards.length; i++) {
    var label = cards[i].querySelector('.quiz-builder-number');
    if (label) label.textContent = String(i + 1);
  }
}

function onQuizBuilderTypeChange(selectElement, questionIndex) {
  var tfBox = document.getElementById('quizTrueFalseBox' + String(questionIndex));
  var answersBox = document.getElementById('quizAnswersBox' + String(questionIndex));
  if (!tfBox || !answersBox) return;
  if (selectElement.value === 'vrai_faux') {
    tfBox.classList.remove('quiz-builder-hidden');
    answersBox.classList.add('quiz-builder-hidden');
  } else {
    tfBox.classList.add('quiz-builder-hidden');
    answersBox.classList.remove('quiz-builder-hidden');
  }
}

function addQuizBuilderAnswer(questionIndex) {
  var questionCard = document.querySelector('.quiz-builder-question[data-question-index="' + String(questionIndex) + '"]');
  if (!questionCard) return;
  var answerList = document.getElementById('quizAnswerList' + String(questionIndex));
  if (!answerList) return;
  var nextAnswerIndex = parseInt(questionCard.getAttribute('data-next-answer-index'), 10);
  if (isNaN(nextAnswerIndex) || nextAnswerIndex < 0) {
    nextAnswerIndex = answerList.children.length;
  }
  answerList.insertAdjacentHTML('beforeend', quizBuilderAnswerRowMarkup(questionIndex, nextAnswerIndex, '', false));
  questionCard.setAttribute('data-next-answer-index', String(nextAnswerIndex + 1));
}

function removeQuizBuilderQuestion(buttonElement) {
  var card = buttonElement.closest('.quiz-builder-question');
  if (!card) return;
  var container = document.getElementById('quizBuilderQuestions');
  card.remove();
  refreshQuizBuilderQuestionLabels();
  if (container && container.querySelectorAll('.quiz-builder-question').length === 0) {
    addQuizBuilderQuestion();
  }
}

function addQuizBuilderQuestion() {
  var container = document.getElementById('quizBuilderQuestions');
  if (!container) return;
  var nextQuestionIndex = parseInt(container.getAttribute('data-next-question-index'), 10);
  if (isNaN(nextQuestionIndex) || nextQuestionIndex < 0) {
    nextQuestionIndex = container.querySelectorAll('.quiz-builder-question').length;
  }
  var questionIndex = String(nextQuestionIndex);
  container.setAttribute('data-next-question-index', String(nextQuestionIndex + 1));
  var html = '<div class="quiz-builder-question" data-question-index="' + questionIndex + '" data-next-answer-index="2"><div class="quiz-builder-head"><strong>Question <span class="quiz-builder-number">0</span></strong><button type="button" class="btn-cancel quiz-remove-btn" onclick="removeQuizBuilderQuestion(this)">Supprimer</button></div><div class="form-group"><label>Texte de la question *</label><input type="text" name="quiz_question_text[' + questionIndex + ']" placeholder="Ex: Quelle matière est utilisée en vannerie ?"></div><div class="front-form-row-3"><div class="form-group"><label>Type</label><select class="quiz-builder-type-select" data-question-index="' + questionIndex + '" name="quiz_question_type[' + questionIndex + ']" onchange="onQuizBuilderTypeChange(this, \'' + questionIndex + '\')"><option value="choix_unique">Choix unique</option><option value="choix_multiple">Choix multiple</option><option value="vrai_faux">Vrai / Faux</option></select></div><div class="form-group"><label>Points</label><input type="number" min="1" step="1" name="quiz_question_points[' + questionIndex + ']" value="1"></div><div class="form-group quiz-builder-hidden" id="quizTrueFalseBox' + questionIndex + '"><label>Bonne réponse (Vrai/Faux)</label><select name="quiz_tf_correct[' + questionIndex + ']"><option value="true">True</option><option value="false">False</option></select></div></div><div class="form-group" id="quizAnswersBox' + questionIndex + '"><label>Réponses *</label><div class="quiz-builder-answer-list" id="quizAnswerList' + questionIndex + '">' + quizBuilderAnswerRowMarkup(questionIndex, 0, '', false) + quizBuilderAnswerRowMarkup(questionIndex, 1, '', false) + '</div><button type="button" class="btn-inscrit vert" onclick="addQuizBuilderAnswer(\'' + questionIndex + '\')">+ Ajouter une réponse</button></div></div>';
  container.insertAdjacentHTML('beforeend', html);
  refreshQuizBuilderQuestionLabels();
}

// Form validation and message functions
function ensureFormMessageNode(formElement) {
  var inlineNode = formElement.querySelector('[data-form-message]');
  if (inlineNode) return inlineNode;
  var formId = formElement.getAttribute('id');
  if (formId !== null && formId !== '') {
    var linkedNode = document.querySelector('[data-form-message-for="' + formId + '"]');
    if (linkedNode) return linkedNode;
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

function clearFormMessage(formElement) {
  var messageNode = ensureFormMessageNode(formElement);
  messageNode.textContent = '';
  messageNode.classList.remove('show', 'error', 'success');
}

function showFormMessage(formElement, status, message) {
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

function clearInputErrors(formElement) {
  var fields = formElement.querySelectorAll('.input-error');
  for (var i = 0; i < fields.length; i++) {
    fields[i].classList.remove('input-error');
  }
}

function isAllowedOption(value, allowedValues) {
  for (var i = 0; i < allowedValues.length; i++) {
    if (value === allowedValues[i]) return true;
  }
  return false;
}

function isValidDateValue(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  var parts = value.split('-');
  var year = parseInt(parts[0], 10);
  var month = parseInt(parts[1], 10);
  var day = parseInt(parts[2], 10);
  var dateObj = new Date(year, month - 1, day);
  return dateObj.getFullYear() === year && (dateObj.getMonth() + 1) === month && dateObj.getDate() === day;
}

function validateBackUpdateForm(formElement) {
  clearFormMessage(formElement);
  clearInputErrors(formElement);
  var integerPattern = /^\d+$/;
  var isValid = true;
  var firstError = '';

  function registerError(fieldElement, message) {
    if (fieldElement) fieldElement.classList.add('input-error');
    isValid = false;
    if (firstError === '') firstError = message;
  }

  var titreField = formElement.querySelector('[name="titre"]');
  var mentorField = formElement.querySelector('[name="mentor"]');
  var niveauField = formElement.querySelector('[name="niveau"]');
  var dureeField = formElement.querySelector('[name="duree"]');
  var prixField = formElement.querySelector('[name="prix"]');
  var certificationField = formElement.querySelector('[name="certification"]');
  var etatField = formElement.querySelector('[name="etat"]');
  var dateField = formElement.querySelector('[name="date_realisation"]');

  var titreValue = titreField ? titreField.value.trim() : '';
  if (titreValue === '' || titreValue.length < 3) registerError(titreField, 'Le titre doit contenir au moins 3 caracteres.');
  var mentorValue = mentorField ? mentorField.value.trim() : '';
  if (mentorValue === '') registerError(mentorField, 'Le mentor est obligatoire.');
  if (niveauField && !isAllowedOption(niveauField.value, ['debutant', 'intermediaire', 'avance'])) registerError(niveauField, 'Le niveau selectionne est invalide.');
  var dureeValue = dureeField ? dureeField.value.trim() : '';
  if (!integerPattern.test(dureeValue) || parseInt(dureeValue, 10) <= 0) registerError(dureeField, 'La duree doit etre un entier superieur a 0.');
  var prixValue = prixField ? prixField.value.trim().replace(',', '.') : '';
  if (prixValue === '' || isNaN(parseFloat(prixValue))) registerError(prixField, 'Le prix doit etre un nombre valide.');
  if (certificationField && !isAllowedOption(certificationField.value, ['oui', 'non'])) registerError(certificationField, 'La certification selectionnee est invalide.');
  if (etatField && !isAllowedOption(etatField.value, ['Actif', 'Inactif', 'Brouillon'])) registerError(etatField, 'Le statut selectionne est invalide.');
  var dateValue = dateField ? dateField.value.trim() : '';
  if (dateValue !== '' && !isValidDateValue(dateValue)) registerError(dateField, 'La date de realisation est invalide.');

  if (!isValid) showFormMessage(formElement, 'error', firstError);
  return isValid;
}

document.addEventListener('DOMContentLoaded', function() {
  var updateForm = document.getElementById('formBackUpdateFormation');
  if (updateForm) {
    updateForm.addEventListener('submit', function(event) {
      if (!validateBackUpdateForm(updateForm)) event.preventDefault();
    });
    var updateFields = updateForm.querySelectorAll('input, textarea, select');
    for (var j = 0; j < updateFields.length; j++) {
      (function(fieldElement) {
        var eventName = fieldElement.tagName === 'SELECT' ? 'change' : 'input';
        fieldElement.addEventListener(eventName, function() {
          fieldElement.classList.remove('input-error');
          clearFormMessage(updateForm);
        });
      })(updateFields[j]);
    }
  }
  
  var typeSelects = document.querySelectorAll('.quiz-builder-type-select');
  for (var ts = 0; ts < typeSelects.length; ts++) {
    var typeSelect = typeSelects[ts];
    var questionIndex = typeSelect.getAttribute('data-question-index');
    if (questionIndex !== null && questionIndex !== '') {
      onQuizBuilderTypeChange(typeSelect, questionIndex);
    }
  }
  
  refreshQuizBuilderQuestionLabels();
});
</script>
</body>
</html>