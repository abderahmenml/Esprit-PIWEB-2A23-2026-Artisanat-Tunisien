<?php
require_once '../model/config.php';
require_once 'craftlink_darija_voice.php';
require_once 'craftlink_english_voice.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escapes dynamic text for safe HTML output.
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Normalizes level values to the expected internal format.
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

// Converts certification input into a boolean value.
function certifOui($certification)
{
    $c = strtolower(trim((string) $certification));

    if ($c === 'oui' || $c === 'yes' || $c === '1') {
        return true;
    }

    return false;
}

// ── TRADUCTION IA (Groq) ────────────────────────────────────────────────────

// Calls the Groq API to translate a text into AR and EN, returns ['ar'=>..., 'en'=>...].
function translateWithGroq($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return ['ar' => '', 'en' => ''];
    }

    $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if ($apiKey === '') {
        return ['ar' => '', 'en' => ''];
    }

    $prompt = 'Translate the following French text about Tunisian craftsmanship into Arabic and English.'
            . ' Reply ONLY with a valid JSON object, no markdown, no backticks, no explanation.'
            . ' Format: {"ar":"...","en":"..."}'
            . ' Text: "' . addslashes($text) . '"';

    $body = json_encode([
        'model'    => 'llama3-8b-8192',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 512,
        'temperature' => 0.2
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\nAuthorization: Bearer " . $apiKey,
            'content'       => $body,
            'timeout'       => 15,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $ctx);
    if ($response === false) {
        return ['ar' => '', 'en' => ''];
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['choices'][0]['message']['content'])) {
        return ['ar' => '', 'en' => ''];
    }

    $raw = trim((string) $data['choices'][0]['message']['content']);
    // Strip optional markdown fences
    $raw = preg_replace('/^```[a-z]*\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);
    $parsed = json_decode($raw, true);

    if (!$parsed || !isset($parsed['ar']) || !isset($parsed['en'])) {
        return ['ar' => '', 'en' => ''];
    }

    return [
        'ar' => trim((string) $parsed['ar']),
        'en' => trim((string) $parsed['en'])
    ];
}

// Returns the translated field value for the active language, falling back to French.
function getTranslatedField($row, $field, $lang)
{
    if ($lang !== 'fr') {
        $col = $field . '_' . $lang;
        if (isset($row[$col]) && trim((string) $row[$col]) !== '') {
            return trim((string) $row[$col]);
        }
    }
    return isset($row[$field]) ? trim((string) $row[$field]) : '';
}

// Ensures translation columns exist in the formations table (runs once per request if missing).
function ensureTranslationColumns($pdo)
{
    $cols = ['domaine_ar', 'domaine_en', 'description_ar', 'description_en'];
    foreach ($cols as $col) {
        if (!columnExists($pdo, 'formations', $col)) {
            try {
                $pdo->exec("ALTER TABLE formations ADD COLUMN `{$col}` TEXT DEFAULT NULL");
            } catch (PDOException $e) {
                // Ignore if it fails (e.g. no ALTER privilege)
            }
        }
    }
}

// ── Backfill traductions pour les formations existantes ──────────────────────
// Translates up to $limit formations that still have empty translation columns.
// Runs silently and never blocks the page — errors are swallowed.
function batchTranslateMissingFormations($pdo, $limit = 3)
{
    try {
        $stmt = $pdo->prepare(
            'SELECT id_formation, domaine, description
             FROM formations
             WHERE (domaine_ar IS NULL OR domaine_ar = \'\')
                OR (domaine_en IS NULL OR domaine_en = \'\')
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        if (count($rows) === 0) {
            return;
        }

        $sqlUpd = 'UPDATE formations
                   SET domaine_ar = :da, domaine_en = :de,
                       description_ar = :dsa, description_en = :dse
                   WHERE id_formation = :id';
        $stmtUpd = $pdo->prepare($sqlUpd);

        foreach ($rows as $row) {
            $td   = translateWithGroq(trim((string) $row['domaine']));
            $tDesc = translateWithGroq(trim((string) $row['description']));

            $stmtUpd->bindValue(':da',  $td['ar']    !== '' ? $td['ar']    : null, $td['ar']    !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpd->bindValue(':de',  $td['en']    !== '' ? $td['en']    : null, $td['en']    !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpd->bindValue(':dsa', $tDesc['ar'] !== '' ? $tDesc['ar'] : null, $tDesc['ar'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpd->bindValue(':dse', $tDesc['en'] !== '' ? $tDesc['en'] : null, $tDesc['en'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmtUpd->bindValue(':id',  (int) $row['id_formation'], PDO::PARAM_INT);
            $stmtUpd->execute();
        }
    } catch (PDOException $eBatch) {
        // Non-blocking — the page still renders even if Groq is unreachable.
    }
}
// ── FIN TRADUCTION ───────────────────────────────────────────────────────────

// Builds initials from a full name for avatar display.
function initials($name)
{
    $clean = trim((string) $name);
    if ($clean === '') {
        return '??';
    }

    $parts = explode(' ', $clean);
    $letters = '';

    for ($i = 0; $i < count($parts); $i++) {
        $part = trim((string) $parts[$i]);
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
            if (strlen($letters) === 2) {
                break;
            }
        }
    }

    if ($letters === '') {
        $letters = strtoupper(substr($clean, 0, 2));
    }

    return $letters;
}

// Checks whether a database table exists in the current schema.
function tableExists($pdo, $tableName)
{
    try {
        $sql = 'SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':table_name', $tableName);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row && isset($row['total']) && (int) $row['total'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Checks whether a column exists in the specified table.
function columnExists($pdo, $tableName, $columnName)
{
    try {
        $sql = 'SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name';
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':table_name', $tableName);
        $stmt->bindValue(':column_name', $columnName);
        $stmt->execute();
        $row = $stmt->fetch();

        return $row && isset($row['total']) && (int) $row['total'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

  // Checks whether a user record exists for the provided ID.
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

// Converts a video URL into an embeddable player URL.
function videoEmbedUrl($videoUrl)
{
    $url = trim((string) $videoUrl);

    if ($url === '') {
        return '';
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return '';
    }

    $host = '';
    if (isset($parts['host'])) {
        $host = strtolower((string) $parts['host']);
    }

    $path = '';
    if (isset($parts['path'])) {
        $path = (string) $parts['path'];
    }

    $query = [];
    if (isset($parts['query'])) {
        parse_str((string) $parts['query'], $query);
    }

    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtu.be') !== false) {
        $videoId = '';

        if (isset($query['v'])) {
            $videoId = trim((string) $query['v']);
        } elseif (strpos($path, '/embed/') === 0) {
            $videoId = trim((string) substr($path, 7));
        } elseif (strpos($host, 'youtu.be') !== false) {
            $videoId = trim((string) ltrim($path, '/'));
        }

        if ($videoId !== '') {
            return 'https://www.youtube.com/embed/' . rawurlencode($videoId);
        }
    }

    if (strpos($host, 'vimeo.com') !== false) {
        if (preg_match('/\/([0-9]+)/', $path, $matches) === 1) {
            return 'https://player.vimeo.com/video/' . $matches[1];
        }
    }

    return '';
}

  // Extracts the first valid URL found in the provided text.
  function extractFirstUrlFromText($text)
  {
    $source = trim((string) $text);

    if ($source === '') {
      return '';
    }

    if (preg_match('/https?:\/\/[^\s"\'<>]+/i', $source, $matches) === 1) {
      $url = trim((string) $matches[0]);

      if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
        return $url;
      }
    }

    return '';
  }

// Formats values for consistent UI display.
function workshopDurationLabel($duree)
{
    if (!is_numeric($duree)) {
        return 'Durée à confirmer';
    }

    $heures = (int) $duree;

    if ($heures <= 0) {
        return 'Durée à confirmer';
    }

    return (string) $heures . 'h';
}

// Formats values for consistent UI display.
function workshopSeatsLabel($placesRestantes, $placesMax)
{
    $restantesValides = is_numeric($placesRestantes);
    $maxValides = is_numeric($placesMax);

    if ($restantesValides && $maxValides) {
        return (string) ((int) $placesRestantes) . ' / ' . (string) ((int) $placesMax) . ' places';
    }

    if ($restantesValides) {
        return (string) ((int) $placesRestantes) . ' places restantes';
    }

    if ($maxValides) {
        return (string) ((int) $placesMax) . ' places max';
    }

    return 'Places à confirmer';
}

  // Formats values for consistent UI display.
  function workshopDateLabel($dateAtelier)
  {
    $raw = trim((string) $dateAtelier);

    if ($raw === '') {
      return 'Date à confirmer';
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
      return 'Date à confirmer';
    }

    return date('d/m/Y H:i', $timestamp);
  }

// Formats values for consistent UI display.
function formatPrix($prix)
{
    if (!is_numeric($prix)) {
        return '0';
    }

    $p = (float) $prix;

    if ((float) ((int) $p) === $p) {
        return (string) ((int) $p);
    }

    return number_format($p, 2, '.', '');
}

// Stores the first validation error message for a specific form field.
function setFormFieldError(&$errors, $field, $message)
{
  if (!isset($errors[$field])) {
    $errors[$field] = $message;
  }
}

// Returns true when a value contains only letters and spaces.
function isLettersAndSpaces($value)
{
  return preg_match('/^[\p{L} ]+$/u', (string) $value) === 1;
}

// Returns default values used to initialize form state.
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

// Parses submitted values into a normalized structure.
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

// Returns default values used to initialize form state.
function defaultWorkshopBuilderItems()
{
  return [
    [
      'key' => '0',
      'titre' => '',
      'description' => '',
      'mentor_id' => '',
      'duree' => '',
      'date_atelier' => '',
      'lieu' => '',
      'places_max' => '',
      'prix' => '',
      'certification' => 'non',
      'statut' => 'a_venir'
    ]
  ];
}

// Parses submitted values into a normalized structure.
function parseWorkshopBuilderItems($post)
{
  $result = [];

  $fieldNames = [
    'workshop_titre',
    'workshop_description',
    'workshop_mentor_id',
    'workshop_duree',
    'workshop_date_atelier',
    'workshop_lieu',
    'workshop_places_max',
    'workshop_prix',
    'workshop_certification',
    'workshop_statut'
  ];

  $fieldValues = [];
  $keys = [];

  for ($i = 0; $i < count($fieldNames); $i++) {
    $fieldName = $fieldNames[$i];

    if (isset($post[$fieldName]) && is_array($post[$fieldName])) {
      $fieldValues[$fieldName] = $post[$fieldName];

      foreach ($post[$fieldName] as $key => $value) {
        $keys[(string) $key] = true;
      }
    } else {
      $fieldValues[$fieldName] = [];
    }
  }

  if (count($keys) === 0) {
    return defaultWorkshopBuilderItems();
  }

  foreach ($keys as $key => $unused) {
    $certification = 'non';
    if (isset($fieldValues['workshop_certification'][$key])) {
      $certification = trim((string) $fieldValues['workshop_certification'][$key]);
    }

    $statut = 'a_venir';
    if (isset($fieldValues['workshop_statut'][$key])) {
      $statut = trim((string) $fieldValues['workshop_statut'][$key]);
    }

    $result[] = [
      'key' => (string) $key,
      'titre' => isset($fieldValues['workshop_titre'][$key]) ? trim((string) $fieldValues['workshop_titre'][$key]) : '',
      'description' => isset($fieldValues['workshop_description'][$key]) ? trim((string) $fieldValues['workshop_description'][$key]) : '',
      'mentor_id' => isset($fieldValues['workshop_mentor_id'][$key]) ? trim((string) $fieldValues['workshop_mentor_id'][$key]) : '',
      'duree' => isset($fieldValues['workshop_duree'][$key]) ? trim((string) $fieldValues['workshop_duree'][$key]) : '',
      'date_atelier' => isset($fieldValues['workshop_date_atelier'][$key]) ? trim((string) $fieldValues['workshop_date_atelier'][$key]) : '',
      'lieu' => isset($fieldValues['workshop_lieu'][$key]) ? trim((string) $fieldValues['workshop_lieu'][$key]) : '',
      'places_max' => isset($fieldValues['workshop_places_max'][$key]) ? trim((string) $fieldValues['workshop_places_max'][$key]) : '',
      'prix' => isset($fieldValues['workshop_prix'][$key]) ? trim((string) $fieldValues['workshop_prix'][$key]) : '',
      'certification' => $certification,
      'statut' => $statut
    ];
  }

  if (count($result) === 0) {
    return defaultWorkshopBuilderItems();
  }

  return $result;
}

$hasFormationsVideoUrl = columnExists($pdo, 'formations', 'video_url');
$hasFormationsMeetLink = columnExists($pdo, 'formations', 'meet_link');
$hasFormationsBookingUrl = columnExists($pdo, 'formations', 'booking_url');

$formationMeetColumn = null;
if ($hasFormationsBookingUrl) {
    $formationMeetColumn = 'booking_url';
} elseif ($hasFormationsMeetLink) {
    $formationMeetColumn = 'meet_link';
}

$hasFormationsWorkshopsTable = tableExists($pdo, 'formations_workshops');
$hasWorkshopsFormationTable = tableExists($pdo, 'workshops_formation');
$hasWorkshopsFormationId = columnExists($pdo, 'workshops', 'id_formation');
$hasWorkshopsTable = tableExists($pdo, 'workshops');
$hasWorkshopsMentorIdColumn = columnExists($pdo, 'workshops', 'mentor_id');
$hasWorkshopsDateAtelierColumn = columnExists($pdo, 'workshops', 'date_atelier');
$hasWorkshopsVideoColumn = columnExists($pdo, 'workshops', 'lien_video');
$hasWorkshopsStatusColumn = columnExists($pdo, 'workshops', 'statut');
$hasWorkshopsCertificationColumn = columnExists($pdo, 'workshops', 'certification');
$hasWorkshopsPriceColumn = columnExists($pdo, 'workshops', 'prix');
$hasWorkshopsProgramDetails = columnExists($pdo, 'workshops', 'program_details');
$hasWorkshopsMeetLink = columnExists($pdo, 'workshops', 'meet_link');
$hasWorkshopsBookingUrl = columnExists($pdo, 'workshops', 'booking_url');
$hasQuizQuestionsTable = tableExists($pdo, 'questions');
$hasQuizResponsesTable = tableExists($pdo, 'reponses');
$quizQuestionsEnabled = $hasQuizQuestionsTable && $hasQuizResponsesTable;
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

$selectQuizDescriptionExpr = 'NULL AS quiz_description';
if ($hasQuizzDescriptionColumn) {
  $selectQuizDescriptionExpr = 'qz.description AS quiz_description';
}

$selectQuizNotePassageExpr = 'NULL AS quiz_note_passage';
if ($hasQuizzNotePassageColumn) {
  $selectQuizNotePassageExpr = 'qz.note_passage AS quiz_note_passage';
} elseif ($hasQuizzScoreColumn) {
  $selectQuizNotePassageExpr = 'qz.score AS quiz_note_passage';
}

$selectQuizNbTentativesExpr = 'NULL AS quiz_nb_tentatives';
if ($hasQuizzNbTentativesColumn) {
  $selectQuizNbTentativesExpr = 'qz.nb_tentatives AS quiz_nb_tentatives';
}

$selectQuizDureeMinutesExpr = 'NULL AS quiz_duree_minutes';
if ($hasQuizzDureeMinutesColumn) {
  $selectQuizDureeMinutesExpr = 'qz.duree_minutes AS quiz_duree_minutes';
} elseif ($hasQuizzDureeColumn) {
  $selectQuizDureeMinutesExpr = 'qz.duree AS quiz_duree_minutes';
}

$workshopMeetColumn = null;
if ($hasWorkshopsBookingUrl) {
    $workshopMeetColumn = 'booking_url';
} elseif ($hasWorkshopsMeetLink) {
    $workshopMeetColumn = 'meet_link';
}

$workshopsAssociationEnabled = $hasFormationsWorkshopsTable || $hasWorkshopsFormationTable || $hasWorkshopsFormationId;

// ── Langue active ────────────────────────────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'ar', 'en'], true)) {
    $_SESSION['craftlink_lang'] = $_GET['lang'];
}
$activeLang = isset($_SESSION['craftlink_lang']) ? $_SESSION['craftlink_lang'] : 'fr';

// Ensure translation columns exist (idempotent, auto-migrates the DB).
ensureTranslationColumns($pdo);
$hasTranslationCols = columnExists($pdo, 'formations', 'domaine_ar');

// Auto-backfill translations for existing formations (up to 3 per request).
// This silently populates domaine_ar/en and description_ar/en via Groq.
if ($hasTranslationCols) {
    batchTranslateMissingFormations($pdo, 3);
}
// ── Fin Langue ───────────────────────────────────────────────────────────────

$erreur = '';
$succes = '';
$openFormModal = false;
$formErrors = [];
$workshopsLoadMessage = '';
$quizQuestionsLoadMessage = '';
$selectedFormationId = 0;
$quizFeedback = [
    'formation_id' => 0,
    'status' => '',
  'message' => '',
  'is_final_score' => false
];

$old = [
  'titre' => '',
  'description' => '',
  'mentor' => '',
  'niveau' => 'debutant',
  'duree' => '',
  'prix' => '',
  'certification' => 'oui',
  'etat' => 'Actif',
  'date_realisation' => '',
  'video_url' => '',
  'meet_link' => '',
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
  'quiz_titre' => '',
  'quiz_description' => '',
  'quiz_note_passage' => '60',
  'quiz_nb_tentatives' => '3',
  'quiz_duree_minutes' => '20'
];

$oldQuizBuilderQuestions = defaultQuizBuilderQuestions();
$oldWorkshopBuilderItems = defaultWorkshopBuilderItems();

if (isset($_GET['ok']) && $_GET['ok'] === '1') {
  $succes = 'Formation ajoutée avec succès.';
}

if (isset($_GET['open_add']) && $_GET['open_add'] === '1') {
  $openFormModal = true;
}

if (isset($_GET['detail']) && is_numeric($_GET['detail'])) {
    $selectedFormationId = (int) $_GET['detail'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = 'add_formation';
  if (isset($_POST['action']) && trim((string) $_POST['action']) !== '') {
      $action = trim((string) $_POST['action']);
  }

    if ($action === 'submit_quiz') {
      $selectedFormationId = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;
      $quizFeedback['formation_id'] = $selectedFormationId;

      $answersRaw = [];
      if (isset($_POST['answers']) && is_array($_POST['answers'])) {
        $answersRaw = $_POST['answers'];
      }

      if ($selectedFormationId <= 0) {
        $quizFeedback['status'] = 'error';
        $quizFeedback['message'] = 'Formation invalide pour le quiz.';
      }

      if ($quizFeedback['message'] === '' && !$quizQuestionsEnabled) {
        $quizFeedback['status'] = 'error';
        $quizFeedback['message'] = 'Le quiz ne peut pas être affiché: tables questions/reponses indisponibles.';
      }

      if ($quizFeedback['message'] === '') {
        try {
          $sqlQuiz = 'SELECT f.id_formation,
                   qz.id_quizz AS quiz_id,
                   qz.titre AS quiz_titre,
                   ' . $selectQuizNotePassageExpr . ',
                   ' . $selectQuizNbTentativesExpr . ',
                   ' . $selectQuizDureeMinutesExpr . '
                FROM formations f
                LEFT JOIN (
                SELECT liens.id_formation, MIN(liens.id_quizz) AS id_quizz
                FROM (
                  SELECT q.formation_id AS id_formation, q.id_quizz
                  FROM quizz q
                  WHERE q.formation_id IS NOT NULL
                  UNION ALL
                  SELECT qf.id_formation, qf.id_quizz
                  FROM quizz_formations qf
                ) liens
                GROUP BY liens.id_formation
                ) lq ON lq.id_formation = f.id_formation
                LEFT JOIN quizz qz ON qz.id_quizz = lq.id_quizz
                WHERE f.id_formation = :id_formation';

          $stmtQuiz = $pdo->prepare($sqlQuiz);
          $stmtQuiz->bindValue(':id_formation', $selectedFormationId, PDO::PARAM_INT);
          $stmtQuiz->execute();
          $quizRow = $stmtQuiz->fetch();

          if (!$quizRow || !isset($quizRow['quiz_id']) || !is_numeric($quizRow['quiz_id']) || (int) $quizRow['quiz_id'] <= 0) {
            $quizFeedback['status'] = 'error';
            $quizFeedback['message'] = 'Aucun quiz configuré pour cette formation.';
          } else {
            $quizId = (int) $quizRow['quiz_id'];
            $notePassage = 60;
            if (isset($quizRow['quiz_note_passage']) && is_numeric($quizRow['quiz_note_passage'])) {
              $notePassage = (int) $quizRow['quiz_note_passage'];
            }

            $nbTentatives = 3;
            if (isset($quizRow['quiz_nb_tentatives']) && is_numeric($quizRow['quiz_nb_tentatives'])) {
              $nbTentatives = (int) $quizRow['quiz_nb_tentatives'];
            }
            if ($nbTentatives <= 0) {
              $nbTentatives = 1;
            }

            if (!isset($_SESSION['quiz_attempts'])) {
              $_SESSION['quiz_attempts'] = [];
            }

            $sessionKey = 'quiz_' . (string) $quizId;
            $attemptsUsed = 0;
            if (isset($_SESSION['quiz_attempts'][$sessionKey]) && is_numeric($_SESSION['quiz_attempts'][$sessionKey])) {
              $attemptsUsed = (int) $_SESSION['quiz_attempts'][$sessionKey];
            }

            if ($attemptsUsed >= $nbTentatives) {
              $quizFeedback['status'] = 'error';
              $quizFeedback['message'] = 'Nombre maximal de tentatives atteint pour ce quiz.';
            } else {
              $sqlQuestions = 'SELECT q.id_question, q.type, q.points,
                          r.id_reponse, r.est_correcte
                       FROM questions q
                       LEFT JOIN reponses r ON r.id_question = q.id_question
                       WHERE q.id_quizz = :id_quizz
                       ORDER BY q.ordre ASC, q.id_question ASC, r.id_reponse ASC';
              $stmtQuestions = $pdo->prepare($sqlQuestions);
              $stmtQuestions->bindValue(':id_quizz', $quizId, PDO::PARAM_INT);
              $stmtQuestions->execute();
              $questionRows = $stmtQuestions->fetchAll();

              if (count($questionRows) === 0) {
                $quizFeedback['status'] = 'error';
                $quizFeedback['message'] = 'Ce quiz ne contient aucune question.';
              } else {
                $quizMap = [];

                for ($i = 0; $i < count($questionRows); $i++) {
                  $rowQuestion = $questionRows[$i];
                  $questionId = 0;

                  if (isset($rowQuestion['id_question']) && is_numeric($rowQuestion['id_question'])) {
                    $questionId = (int) $rowQuestion['id_question'];
                  }

                  if ($questionId <= 0) {
                    continue;
                  }

                  if (!isset($quizMap[$questionId])) {
                    $points = 1;
                    if (isset($rowQuestion['points']) && is_numeric($rowQuestion['points']) && (int) $rowQuestion['points'] > 0) {
                      $points = (int) $rowQuestion['points'];
                    }

                    $quizMap[$questionId] = [
                      'points' => $points,
                      'correct_ids' => []
                    ];
                  }

                  if (isset($rowQuestion['id_reponse']) && is_numeric($rowQuestion['id_reponse'])) {
                    $responseId = (int) $rowQuestion['id_reponse'];
                    $isCorrect = isset($rowQuestion['est_correcte']) && (int) $rowQuestion['est_correcte'] === 1;

                    if ($responseId > 0 && $isCorrect && !in_array($responseId, $quizMap[$questionId]['correct_ids'], true)) {
                      $quizMap[$questionId]['correct_ids'][] = $responseId;
                    }
                  }
                }

                if (count($quizMap) === 0) {
                  $quizFeedback['status'] = 'error';
                  $quizFeedback['message'] = 'Ce quiz ne contient pas de questions valides.';
                } else {
                  $answeredCount = 0;
                  $totalPoints = 0;
                  $earnedPoints = 0;

                  foreach ($quizMap as $questionId => $questionData) {
                    $points = (int) $questionData['points'];
                    if ($points <= 0) {
                      $points = 1;
                    }

                    $totalPoints += $points;

                    $selectedIds = [];
                    if (isset($answersRaw[$questionId])) {
                      $rawValue = $answersRaw[$questionId];

                      if (is_array($rawValue)) {
                        for ($j = 0; $j < count($rawValue); $j++) {
                          $candidate = $rawValue[$j];
                          if (is_numeric($candidate) && (int) $candidate > 0) {
                            $selectedIds[] = (int) $candidate;
                          }
                        }
                      } else {
                        if (is_numeric($rawValue) && (int) $rawValue > 0) {
                          $selectedIds[] = (int) $rawValue;
                        }
                      }
                    }

                    $selectedIds = array_values(array_unique($selectedIds));
                    sort($selectedIds);

                    if (count($selectedIds) > 0) {
                      $answeredCount += 1;
                    }

                    $correctIds = $questionData['correct_ids'];
                    sort($correctIds);

                    if (count($correctIds) > 0 && $selectedIds === $correctIds) {
                      $earnedPoints += $points;
                    }
                  }

                  if ($answeredCount <= 0) {
                    $quizFeedback['status'] = 'error';
                    $quizFeedback['message'] = 'Sélectionnez au moins une réponse avant de valider le quiz.';
                  } else {
                    if ($totalPoints <= 0) {
                      $totalPoints = 1;
                    }

                    $score = ($earnedPoints * 100) / $totalPoints;
                    $attemptsUsed += 1;
                    $_SESSION['quiz_attempts'][$sessionKey] = $attemptsUsed;

                    $reussi = $score >= $notePassage;
                    $restantes = $nbTentatives - $attemptsUsed;
                    if ($restantes < 0) {
                      $restantes = 0;
                    }

                    if ($reussi) {
                      $quizFeedback['status'] = 'success';
                      $quizFeedback['message'] = 'Score: ' . formatPrix($score) . '%';
                      $quizFeedback['is_final_score'] = true;
                    } else {
                      $quizFeedback['status'] = 'error';
                      $quizFeedback['message'] = 'Score: ' . formatPrix($score) . '%';
                      $quizFeedback['is_final_score'] = true;
                    }
                  }
                }
              }
            }
          }
        } catch (PDOException $e) {
          $quizFeedback['status'] = 'error';
          $quizFeedback['message'] = 'Erreur pendant la validation du quiz.';
        }
      }
    } else {
      $openFormModal = true;

      $old['titre'] = isset($_POST['titre']) ? trim((string) $_POST['titre']) : '';
      $old['description'] = isset($_POST['description']) ? trim((string) $_POST['description']) : '';
      $old['mentor'] = isset($_POST['mentor']) ? trim((string) $_POST['mentor']) : '';
      $old['niveau'] = isset($_POST['niveau']) ? trim((string) $_POST['niveau']) : 'debutant';
      $old['duree'] = isset($_POST['duree']) ? trim((string) $_POST['duree']) : '';
      $old['prix'] = isset($_POST['prix']) ? trim((string) $_POST['prix']) : '';
      $old['certification'] = isset($_POST['certification']) ? trim((string) $_POST['certification']) : 'oui';
      $old['etat'] = isset($_POST['etat']) ? trim((string) $_POST['etat']) : 'Actif';
      $old['date_realisation'] = isset($_POST['date_realisation']) ? trim((string) $_POST['date_realisation']) : '';
      $old['video_url'] = isset($_POST['video_url']) ? trim((string) $_POST['video_url']) : '';
      $old['meet_link'] = isset($_POST['meet_link']) ? trim((string) $_POST['meet_link']) : '';
      $old['quiz_titre'] = isset($_POST['quiz_titre']) ? trim((string) $_POST['quiz_titre']) : '';
      $old['quiz_description'] = isset($_POST['quiz_description']) ? trim((string) $_POST['quiz_description']) : '';
      $old['quiz_note_passage'] = isset($_POST['quiz_note_passage']) ? trim((string) $_POST['quiz_note_passage']) : '60';
      $old['quiz_nb_tentatives'] = isset($_POST['quiz_nb_tentatives']) ? trim((string) $_POST['quiz_nb_tentatives']) : '3';
      $old['quiz_duree_minutes'] = isset($_POST['quiz_duree_minutes']) ? trim((string) $_POST['quiz_duree_minutes']) : '20';

      $oldWorkshopBuilderItems = parseWorkshopBuilderItems($_POST);
      if (count($oldWorkshopBuilderItems) === 0) {
        $oldWorkshopBuilderItems = defaultWorkshopBuilderItems();
      }

      $workshopsToCreate = [];
      for ($w = 0; $w < count($oldWorkshopBuilderItems); $w++) {
        $workshopItem = $oldWorkshopBuilderItems[$w];

        $workshopKey = '';
        if (isset($workshopItem['key'])) {
          $workshopKey = (string) $workshopItem['key'];
        }
        if ($workshopKey === '') {
          $workshopKey = (string) $w;
        }

        $workshopTitre = isset($workshopItem['titre']) ? trim((string) $workshopItem['titre']) : '';
        $workshopDescription = isset($workshopItem['description']) ? trim((string) $workshopItem['description']) : '';
        $workshopMentorId = isset($workshopItem['mentor_id']) ? trim((string) $workshopItem['mentor_id']) : '';
        $workshopDuree = isset($workshopItem['duree']) ? trim((string) $workshopItem['duree']) : '';
        $workshopDateAtelier = isset($workshopItem['date_atelier']) ? trim((string) $workshopItem['date_atelier']) : '';
        $workshopLieu = isset($workshopItem['lieu']) ? trim((string) $workshopItem['lieu']) : '';
        $workshopPlacesMax = isset($workshopItem['places_max']) ? trim((string) $workshopItem['places_max']) : '';
        $workshopPrix = isset($workshopItem['prix']) ? trim((string) $workshopItem['prix']) : '';

        $workshopCertification = 'non';
        if (isset($workshopItem['certification'])) {
          $workshopCertification = trim((string) $workshopItem['certification']);
        }
        if ($workshopCertification !== 'oui' && $workshopCertification !== 'non') {
          $workshopCertification = 'non';
        }

        $workshopStatut = 'a_venir';
        if (isset($workshopItem['statut'])) {
          $workshopStatut = trim((string) $workshopItem['statut']);
        }
        if ($workshopStatut !== 'a_venir' && $workshopStatut !== 'en_cours' && $workshopStatut !== 'termine' && $workshopStatut !== 'annule') {
          $workshopStatut = 'a_venir';
        }

        $oldWorkshopBuilderItems[$w] = [
          'key' => $workshopKey,
          'titre' => $workshopTitre,
          'description' => $workshopDescription,
          'mentor_id' => $workshopMentorId,
          'duree' => $workshopDuree,
          'date_atelier' => $workshopDateAtelier,
          'lieu' => $workshopLieu,
          'places_max' => $workshopPlacesMax,
          'prix' => $workshopPrix,
          'certification' => $workshopCertification,
          'statut' => $workshopStatut
        ];

        $hasWorkshopInput = (
          $workshopTitre !== '' ||
          $workshopDescription !== '' ||
          $workshopMentorId !== '' ||
          $workshopDuree !== '' ||
          $workshopDateAtelier !== '' ||
          $workshopLieu !== '' ||
          $workshopPlacesMax !== '' ||
          $workshopPrix !== ''
        );

        if (!$hasWorkshopInput) {
          continue;
        }

        $workshopsToCreate[] = $oldWorkshopBuilderItems[$w];
      }

      $quizBuilderQuestionsInput = parseQuizBuilderQuestions($_POST);
      if (count($quizBuilderQuestionsInput) === 0) {
        $quizBuilderQuestionsInput = defaultQuizBuilderQuestions();
      }
      $oldQuizBuilderQuestions = $quizBuilderQuestionsInput;
      $quizQuestionsToInsert = [];

      $formErrors = [];

      if ($old['titre'] === '') {
        setFormFieldError($formErrors, 'titre', 'Le titre est obligatoire.');
      } elseif (strlen($old['titre']) < 3) {
        setFormFieldError($formErrors, 'titre', 'Le titre doit contenir au moins 3 caractères.');
      } elseif (strlen($old['titre']) > 100) {
        setFormFieldError($formErrors, 'titre', 'Le titre ne doit pas dépasser 100 caractères.');
      } elseif (!isLettersAndSpaces($old['titre'])) {
        setFormFieldError($formErrors, 'titre', 'Le titre doit contenir uniquement des lettres.');
      }

      if ($old['mentor'] === '') {
        setFormFieldError($formErrors, 'mentor', 'Le mentor est obligatoire.');
      } elseif (strlen($old['mentor']) > 100) {
        setFormFieldError($formErrors, 'mentor', 'Le mentor ne doit pas dépasser 100 caractères.');
      } elseif (!isLettersAndSpaces($old['mentor'])) {
        setFormFieldError($formErrors, 'mentor', 'Le mentor doit contenir uniquement des lettres.');
      }

      $niveauDb = normalizeNiveau($old['niveau']);
      if ($niveauDb === '') {
        setFormFieldError($formErrors, 'niveau', 'Le niveau sélectionné est invalide.');
      }

      if ($old['duree'] === '' || !ctype_digit($old['duree']) || (int) $old['duree'] <= 0) {
        setFormFieldError($formErrors, 'duree', 'La durée doit être un nombre entier supérieur à 0.');
      }

      if ($old['prix'] === '' || !is_numeric($old['prix'])) {
        setFormFieldError($formErrors, 'prix', 'Le prix doit être un nombre valide.');
      } elseif ((float) $old['prix'] < 0) {
        setFormFieldError($formErrors, 'prix', 'Le prix doit être supérieur ou égal à 0.');
      }

      if ($old['certification'] !== 'oui' && $old['certification'] !== 'non') {
        setFormFieldError($formErrors, 'certification', 'La certification sélectionnée est invalide.');
      }

      if ($old['etat'] !== 'Actif' && $old['etat'] !== 'Inactif' && $old['etat'] !== 'Brouillon') {
        setFormFieldError($formErrors, 'etat', 'Le statut sélectionné est invalide.');
      }

      if ($old['date_realisation'] !== '') {
        $dateRealisationObj = DateTime::createFromFormat('Y-m-d', $old['date_realisation']);
        $dateRealisationOk = (
          $dateRealisationObj instanceof DateTime
          && $dateRealisationObj->format('Y-m-d') === $old['date_realisation']
        );

        if (!$dateRealisationOk) {
          setFormFieldError($formErrors, 'date_realisation', 'La date de réalisation est invalide.');
        }
      }

      if ($old['video_url'] !== '') {
        if (strlen($old['video_url']) > 1024) {
          setFormFieldError($formErrors, 'video_url', 'Le lien vidéo ne doit pas dépasser 1024 caractères.');
        } elseif (filter_var($old['video_url'], FILTER_VALIDATE_URL) === false) {
          setFormFieldError($formErrors, 'video_url', 'Le lien vidéo doit être une URL valide.');
        }
      }

      if ($old['meet_link'] !== '' && filter_var($old['meet_link'], FILTER_VALIDATE_URL) === false) {
        setFormFieldError($formErrors, 'meet_link', 'Le lien de rencontre doit être une URL valide.');
      }

      if ($old['quiz_titre'] === '') {
        setFormFieldError($formErrors, 'quiz_titre', 'Le titre du quiz est obligatoire.');
      } elseif (strlen($old['quiz_titre']) < 3) {
        setFormFieldError($formErrors, 'quiz_titre', 'Le titre du quiz doit contenir au moins 3 caractères.');
      } elseif (strlen($old['quiz_titre']) > 255) {
        setFormFieldError($formErrors, 'quiz_titre', 'Le titre du quiz ne doit pas dépasser 255 caractères.');
      }

      if (!ctype_digit($old['quiz_duree_minutes']) || (int) $old['quiz_duree_minutes'] <= 0) {
        setFormFieldError($formErrors, 'quiz_duree_minutes', 'La durée du quiz doit être un entier supérieur à 0.');
      }

      if (count($formErrors) > 0) {
        $erreur = 'Veuillez corriger les champs en erreur dans le formulaire.';
      }

      if ($erreur === '' && count($workshopsToCreate) > 0 && !$hasWorkshopsTable) {
        $erreur = 'La table workshops est introuvable en base de données.';
      }

      if ($erreur === '') {
        for ($w = 0; $w < count($workshopsToCreate); $w++) {
          $workshopInput = $workshopsToCreate[$w];
          $workshopLabel = 'Workshop #' . (string) ($w + 1);

          if ($workshopInput['titre'] === '') {
            $erreur = $workshopLabel . ' : renseignez le titre ou supprimez cette ligne.';
            break;
          }

          if (strlen($workshopInput['titre']) < 3) {
            $erreur = $workshopLabel . ' : le titre doit contenir au moins 3 caractères.';
            break;
          }

          if (strlen($workshopInput['titre']) > 200) {
            $erreur = $workshopLabel . ' : le titre ne doit pas dépasser 200 caractères.';
            break;
          }

          if (!isLettersAndSpaces($workshopInput['titre'])) {
            $erreur = $workshopLabel . ' : le titre doit contenir uniquement des lettres.';
            break;
          }

          if ($workshopInput['description'] === '' || strlen($workshopInput['description']) < 10) {
            $erreur = $workshopLabel . ' : la description doit contenir au moins 10 caractères.';
            break;
          }

          if ($workshopInput['mentor_id'] !== '' && (!ctype_digit($workshopInput['mentor_id']) || (int) $workshopInput['mentor_id'] <= 0)) {
            $erreur = $workshopLabel . ' : mentor ID doit être un entier positif.';
            break;
          }

          if ($workshopInput['mentor_id'] !== '') {
            $mentorIdWorkshop = (int) $workshopInput['mentor_id'];

            // Optional field: if id_user does not exist, keep NULL to avoid FK errors.
            if (!userExistsById($pdo, $mentorIdWorkshop)) {
              $workshopsToCreate[$w]['mentor_id'] = '';
            }
          }

          if (!ctype_digit($workshopInput['duree']) || (int) $workshopInput['duree'] <= 0) {
            $erreur = $workshopLabel . ' : la durée doit être un entier supérieur à 0.';
            break;
          }

          if ($workshopInput['date_atelier'] !== '' && strtotime($workshopInput['date_atelier']) === false) {
            $erreur = $workshopLabel . ' : la date d\'atelier est invalide.';
            break;
          }

          if ($workshopInput['lieu'] === '' || strlen($workshopInput['lieu']) < 2) {
            $erreur = $workshopLabel . ' : le lieu est obligatoire (min. 2 caractères).';
            break;
          }

          if (strlen($workshopInput['lieu']) > 255) {
            $erreur = $workshopLabel . ' : le lieu ne doit pas dépasser 255 caractères.';
            break;
          }

          if (!isLettersAndSpaces($workshopInput['lieu'])) {
            $erreur = $workshopLabel . ' : le lieu doit contenir uniquement des lettres.';
            break;
          }

          if (!ctype_digit($workshopInput['places_max']) || (int) $workshopInput['places_max'] <= 0) {
            $erreur = $workshopLabel . ' : le nombre de places max doit être un entier supérieur à 0.';
            break;
          }

          if (!is_numeric($workshopInput['prix'])) {
            $erreur = $workshopLabel . ' : le prix doit être un nombre valide.';
            break;
          }

          if ((float) $workshopInput['prix'] < 0) {
            $erreur = $workshopLabel . ' : le prix doit être supérieur ou égal à 0.';
            break;
          }

          if ($workshopInput['certification'] !== 'oui' && $workshopInput['certification'] !== 'non') {
            $erreur = $workshopLabel . ' : la certification est invalide.';
            break;
          }

          if ($workshopInput['statut'] !== 'a_venir' && $workshopInput['statut'] !== 'en_cours' && $workshopInput['statut'] !== 'termine' && $workshopInput['statut'] !== 'annule') {
            $erreur = $workshopLabel . ' : le statut est invalide.';
            break;
          }
        }
      }

      if ($erreur === '' && (!ctype_digit($old['quiz_note_passage']) || (int) $old['quiz_note_passage'] < 0 || (int) $old['quiz_note_passage'] > 100)) {
        $erreur = 'La note de passage du quiz doit être un entier entre 0 et 100.';
      }

      if ($erreur === '' && (!ctype_digit($old['quiz_nb_tentatives']) || (int) $old['quiz_nb_tentatives'] <= 0)) {
        $erreur = 'Le nombre de tentatives du quiz doit être un entier supérieur à 0.';
      }

      if ($erreur === '' && !$quizQuestionsEnabled) {
        $erreur = 'Les tables questions/reponses sont nécessaires pour ajouter des questions au quiz.';
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

      if ($erreur === '') {
        $dateRealisation = null;
        if ($old['date_realisation'] !== '') {
          $dateRealisation = $old['date_realisation'];
        }

        try {
          $pdo->beginTransaction();

          $insertColumns = ['domaine', 'formateur', 'description', 'date_realisation', 'etat', 'duree', 'certification', 'niveau', 'prix'];
          $insertPlaceholders = [':domaine', ':formateur', ':description', ':date_realisation', ':etat', ':duree', ':certification', ':niveau', ':prix'];

          if ($hasFormationsVideoUrl) {
              $insertColumns[] = 'video_url';
              $insertPlaceholders[] = ':video_url';
          }

          if ($formationMeetColumn !== null) {
              $insertColumns[] = $formationMeetColumn;
              $insertPlaceholders[] = ':meet_link';
          }

          $sqlInsert = 'INSERT INTO formations (' . implode(', ', $insertColumns) . ')
                       VALUES (' . implode(', ', $insertPlaceholders) . ')';

          $stmtInsert = $pdo->prepare($sqlInsert);
          $stmtInsert->bindValue(':domaine', $old['titre']);
          $stmtInsert->bindValue(':formateur', $old['mentor']);
          $stmtInsert->bindValue(':description', $old['description']);

          if ($dateRealisation === null) {
            $stmtInsert->bindValue(':date_realisation', null, PDO::PARAM_NULL);
          } else {
            $stmtInsert->bindValue(':date_realisation', $dateRealisation);
          }

          $stmtInsert->bindValue(':etat', $old['etat']);
          $stmtInsert->bindValue(':duree', (int) $old['duree'], PDO::PARAM_INT);
          $stmtInsert->bindValue(':certification', $old['certification']);
          $stmtInsert->bindValue(':niveau', $niveauDb);
          $stmtInsert->bindValue(':prix', (float) $old['prix']);

          if ($hasFormationsVideoUrl) {
              if ($old['video_url'] === '') {
                  $stmtInsert->bindValue(':video_url', null, PDO::PARAM_NULL);
              } else {
                  $stmtInsert->bindValue(':video_url', $old['video_url']);
              }
          }

          if ($formationMeetColumn !== null) {
              if ($old['meet_link'] === '') {
                  $stmtInsert->bindValue(':meet_link', null, PDO::PARAM_NULL);
              } else {
                  $stmtInsert->bindValue(':meet_link', $old['meet_link']);
              }
          }

          $stmtInsert->execute();

          $idFormationCree = (int) $pdo->lastInsertId();

          // ── Traduction IA automatique ────────────────────────────────────
          if ($hasTranslationCols) {
              $tradDomaine     = translateWithGroq($old['titre']);
              $tradDescription = translateWithGroq($old['description']);
              if ($tradDomaine['ar'] !== '' || $tradDomaine['en'] !== '' || $tradDescription['ar'] !== '' || $tradDescription['en'] !== '') {
                  try {
                      $sqlTrad = 'UPDATE formations SET domaine_ar = :da, domaine_en = :de, description_ar = :dsa, description_en = :dse WHERE id_formation = :id';
                      $stmtTrad = $pdo->prepare($sqlTrad);
                      $stmtTrad->bindValue(':da',  $tradDomaine['ar']     !== '' ? $tradDomaine['ar']     : null, $tradDomaine['ar']     !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                      $stmtTrad->bindValue(':de',  $tradDomaine['en']     !== '' ? $tradDomaine['en']     : null, $tradDomaine['en']     !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                      $stmtTrad->bindValue(':dsa', $tradDescription['ar'] !== '' ? $tradDescription['ar'] : null, $tradDescription['ar'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                      $stmtTrad->bindValue(':dse', $tradDescription['en'] !== '' ? $tradDescription['en'] : null, $tradDescription['en'] !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                      $stmtTrad->bindValue(':id',  $idFormationCree, PDO::PARAM_INT);
                      $stmtTrad->execute();
                  } catch (PDOException $eTrad) {
                      // Traduction non bloquante — la formation est déjà créée
                  }
              }
          }
          // ── Fin traduction ───────────────────────────────────────────────

          $quizTitre = $old['quiz_titre'];
          $quizDescription = $old['quiz_description'];

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
            $stmtQuizInsert->bindValue(':formation_id', $idFormationCree, PDO::PARAM_INT);
          }
          if ($hasQuizzDomaineColumn) {
            $stmtQuizInsert->bindValue(':domaine', $old['titre']);
          }
          if ($hasQuizzTitreColumn) {
            $stmtQuizInsert->bindValue(':titre', $quizTitre);
          }
          if ($hasQuizzDescriptionColumn) {
            if ($quizDescription === '') {
              $stmtQuizInsert->bindValue(':description', null, PDO::PARAM_NULL);
            } else {
              $stmtQuizInsert->bindValue(':description', $quizDescription);
            }
          }
          if ($hasQuizzQuestionColumn) {
            if ($quizDescription === '') {
              $stmtQuizInsert->bindValue(':question', $quizTitre);
            } else {
              $stmtQuizInsert->bindValue(':question', $quizDescription);
            }
          }
          if ($hasQuizzNotePassageColumn) {
            $stmtQuizInsert->bindValue(':note_passage', (int) $old['quiz_note_passage'], PDO::PARAM_INT);
          } elseif ($hasQuizzScoreColumn) {
            $stmtQuizInsert->bindValue(':score', (int) $old['quiz_note_passage'], PDO::PARAM_INT);
          }
          if ($hasQuizzNbTentativesColumn) {
            $stmtQuizInsert->bindValue(':nb_tentatives', (int) $old['quiz_nb_tentatives'], PDO::PARAM_INT);
          }
          if ($hasQuizzDureeMinutesColumn) {
            $stmtQuizInsert->bindValue(':duree_minutes', (int) $old['quiz_duree_minutes'], PDO::PARAM_INT);
          } elseif ($hasQuizzDureeColumn) {
            $stmtQuizInsert->bindValue(':duree', (int) $old['quiz_duree_minutes'], PDO::PARAM_INT);
          }

          $stmtQuizInsert->execute();

          $idQuizCree = (int) $pdo->lastInsertId();

          if ($hasQuizzFormationsTable && $idQuizCree > 0) {
            $sqlLienQuizFormation = 'INSERT INTO quizz_formations (id_formation, id_quizz)
                                     VALUES (:id_formation, :id_quizz)';
            $stmtLienQuizFormation = $pdo->prepare($sqlLienQuizFormation);
            $stmtLienQuizFormation->bindValue(':id_formation', $idFormationCree, PDO::PARAM_INT);
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

          if (count($workshopsToCreate) > 0) {
            $sqlWorkshopInsert = 'INSERT INTO workshops (titre, description, mentor_id, duree, date_publication, date_atelier, lieu, places_max, places_restantes, prix, certification, statut)
                                  VALUES (:titre, :description, :mentor_id, :duree, CURDATE(), :date_atelier, :lieu, :places_max, :places_restantes, :prix, :certification, :statut)';
            $stmtWorkshopInsert = $pdo->prepare($sqlWorkshopInsert);
            for ($w = 0; $w < count($workshopsToCreate); $w++) {
              $workshopToCreate = $workshopsToCreate[$w];

              $stmtWorkshopInsert->bindValue(':titre', $workshopToCreate['titre']);
              $stmtWorkshopInsert->bindValue(':description', $workshopToCreate['description']);

              if ($hasWorkshopsMentorIdColumn && $workshopToCreate['mentor_id'] !== '') {
                $stmtWorkshopInsert->bindValue(':mentor_id', (int) $workshopToCreate['mentor_id'], PDO::PARAM_INT);
              } else {
                $stmtWorkshopInsert->bindValue(':mentor_id', null, PDO::PARAM_NULL);
              }

              $stmtWorkshopInsert->bindValue(':duree', (int) $workshopToCreate['duree'], PDO::PARAM_INT);

              if ($hasWorkshopsDateAtelierColumn && $workshopToCreate['date_atelier'] !== '') {
                $workshopDateAtelier = date('Y-m-d H:i:s', strtotime($workshopToCreate['date_atelier']));
                $stmtWorkshopInsert->bindValue(':date_atelier', $workshopDateAtelier);
              } else {
                $stmtWorkshopInsert->bindValue(':date_atelier', null, PDO::PARAM_NULL);
              }

              $stmtWorkshopInsert->bindValue(':lieu', $workshopToCreate['lieu']);
              $stmtWorkshopInsert->bindValue(':places_max', (int) $workshopToCreate['places_max'], PDO::PARAM_INT);
              $stmtWorkshopInsert->bindValue(':places_restantes', (int) $workshopToCreate['places_max'], PDO::PARAM_INT);
              $stmtWorkshopInsert->bindValue(':prix', (float) $workshopToCreate['prix']);

              $stmtWorkshopInsert->bindValue(':certification', $workshopToCreate['certification']);

              if ($hasWorkshopsStatusColumn) {
                $stmtWorkshopInsert->bindValue(':statut', $workshopToCreate['statut']);
              } else {
                $stmtWorkshopInsert->bindValue(':statut', 'a_venir');
              }

              $stmtWorkshopInsert->execute();
              $idWorkshopCree = (int) $pdo->lastInsertId();

              if ($idWorkshopCree > 0) {
                if ($hasWorkshopsFormationTable) {
                  $sqlWorkshopLink = 'INSERT INTO workshops_formation (id_formation, id_workshop)
                                      VALUES (:id_formation, :id_workshop)';
                  $stmtWorkshopLink = $pdo->prepare($sqlWorkshopLink);
                  $stmtWorkshopLink->bindValue(':id_formation', $idFormationCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->bindValue(':id_workshop', $idWorkshopCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->execute();
                } elseif ($hasFormationsWorkshopsTable) {
                  $sqlWorkshopLink = 'INSERT INTO formations_workshops (id_formation, id_workshop)
                                      VALUES (:id_formation, :id_workshop)';
                  $stmtWorkshopLink = $pdo->prepare($sqlWorkshopLink);
                  $stmtWorkshopLink->bindValue(':id_formation', $idFormationCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->bindValue(':id_workshop', $idWorkshopCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->execute();
                } elseif ($hasWorkshopsFormationId) {
                  $sqlWorkshopLink = 'UPDATE workshops SET id_formation = :id_formation WHERE id_workshop = :id_workshop';
                  $stmtWorkshopLink = $pdo->prepare($sqlWorkshopLink);
                  $stmtWorkshopLink->bindValue(':id_formation', $idFormationCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->bindValue(':id_workshop', $idWorkshopCree, PDO::PARAM_INT);
                  $stmtWorkshopLink->execute();
                }
              }
            }
          }

          $pdo->commit();

          header('Location: front.php?ok=1');
          exit;
        } catch (PDOException $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          $erreur = 'Erreur base de données pendant l\'ajout.';
        }
      }
  }
}

if (count($oldQuizBuilderQuestions) === 0) {
  $oldQuizBuilderQuestions = defaultQuizBuilderQuestions();
}

$nextQuizBuilderQuestionIndex = 0;
for ($qb = 0; $qb < count($oldQuizBuilderQuestions); $qb++) {
  $qbKey = (string) $oldQuizBuilderQuestions[$qb]['key'];
  if (ctype_digit($qbKey)) {
    $candidate = (int) $qbKey + 1;
    if ($candidate > $nextQuizBuilderQuestionIndex) {
      $nextQuizBuilderQuestionIndex = $candidate;
    }
  }
}

if ($nextQuizBuilderQuestionIndex <= 0) {
  $nextQuizBuilderQuestionIndex = count($oldQuizBuilderQuestions);
}

if (count($oldWorkshopBuilderItems) === 0) {
  $oldWorkshopBuilderItems = defaultWorkshopBuilderItems();
}

$nextWorkshopBuilderIndex = 0;
for ($wb = 0; $wb < count($oldWorkshopBuilderItems); $wb++) {
  $wbKey = (string) $oldWorkshopBuilderItems[$wb]['key'];
  if (ctype_digit($wbKey)) {
    $candidate = (int) $wbKey + 1;
    if ($candidate > $nextWorkshopBuilderIndex) {
      $nextWorkshopBuilderIndex = $candidate;
    }
  }
}

if ($nextWorkshopBuilderIndex <= 0) {
  $nextWorkshopBuilderIndex = count($oldWorkshopBuilderItems);
}

$recherche = '';
if (isset($_GET['q'])) {
    $recherche = trim((string) $_GET['q']);
} elseif (isset($_POST['q'])) {
    $recherche = trim((string) $_POST['q']);
}

$niveauFiltre = '';
if (isset($_GET['niveau'])) {
    $niveauFiltre = trim((string) $_GET['niveau']);
} elseif (isset($_POST['niveau'])) {
    $niveauFiltre = trim((string) $_POST['niveau']);
}

$certifFiltre = '';
if (isset($_GET['certif'])) {
    $certifFiltre = trim((string) $_GET['certif']);
} elseif (isset($_POST['certif'])) {
    $certifFiltre = trim((string) $_POST['certif']);
}

$niveauFiltreDb = '';
if ($niveauFiltre !== '') {
  $niveauFiltreDb = normalizeNiveau($niveauFiltre);
}

$formations = [];
$totalFormations = 0;
$totalInscrits = 0;
$mentorsUniques = [];

try {
    $sqlStats = 'SELECT f.id_formation, f.formateur,
                        (SELECT COUNT(*) FROM inscriptions i WHERE i.id_formation = f.id_formation) AS inscrits
                 FROM formations f';
    $stmtStats = $pdo->prepare($sqlStats);
    $stmtStats->execute();
    $statsRows = $stmtStats->fetchAll();

    $totalFormations = count($statsRows);

    for ($i = 0; $i < count($statsRows); $i++) {
        $row = $statsRows[$i];
        $totalInscrits += (int) $row['inscrits'];

        $mentor = trim((string) $row['formateur']);
        if ($mentor !== '' && !in_array($mentor, $mentorsUniques, true)) {
            $mentorsUniques[] = $mentor;
        }
    }

    $selectVideo = 'NULL AS video_url';
    if ($hasFormationsVideoUrl) {
        $selectVideo = 'f.video_url AS video_url';
    }

    $selectMeet = 'NULL AS meet_link';
    if ($formationMeetColumn !== null) {
        $selectMeet = 'f.' . $formationMeetColumn . ' AS meet_link';
    }

    $selectTrad = '';
    if ($hasTranslationCols) {
        $selectTrad = ', f.domaine_ar, f.domaine_en, f.description_ar, f.description_en';
    }

    $sql = 'SELECT f.id_formation, f.domaine, f.formateur, f.description, f.duree, f.certification, f.niveau, f.prix'
         . $selectTrad . ',
                   ' . $selectVideo . ',
                   ' . $selectMeet . ',
             qz.id_quizz AS quiz_id, qz.titre AS quiz_titre, ' . $selectQuizDescriptionExpr . ',
             ' . $selectQuizNotePassageExpr . ', ' . $selectQuizNbTentativesExpr . ', ' . $selectQuizDureeMinutesExpr . ',
                   (SELECT COUNT(*) FROM inscriptions i WHERE i.id_formation = f.id_formation) AS inscrits
            FROM formations f
            LEFT JOIN (
              SELECT liens.id_formation, MIN(liens.id_quizz) AS id_quizz
              FROM (
                SELECT q.formation_id AS id_formation, q.id_quizz
                FROM quizz q
                WHERE q.formation_id IS NOT NULL
                UNION ALL
                SELECT qf.id_formation, qf.id_quizz
                FROM quizz_formations qf
              ) liens
              GROUP BY liens.id_formation
            ) lq ON lq.id_formation = f.id_formation
            LEFT JOIN quizz qz ON qz.id_quizz = lq.id_quizz';

    $conditions = [];
    $params = [];

    if ($recherche !== '') {
        $conditions[] = '(f.domaine LIKE :q OR f.formateur LIKE :q OR f.description LIKE :q OR qz.titre LIKE :q)';
        $params[':q'] = '%' . $recherche . '%';
    }

    if ($niveauFiltreDb !== '') {
        $conditions[] = 'f.niveau = :niveau';
        $params[':niveau'] = $niveauFiltreDb;
    }

    if ($certifFiltre === 'oui' || $certifFiltre === 'non') {
        $conditions[] = 'f.certification = :certif';
        $params[':certif'] = $certifFiltre;
    }

    if (count($conditions) > 0) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY f.id_formation DESC';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $formations = $stmt->fetchAll();
} catch (PDOException $e) {
    $erreur = 'Impossible de charger les formations depuis la base.';
}

$workshopsByFormation = [];
$quizQuestionsByQuiz = [];

if ($quizQuestionsEnabled) {
  try {
    $quizIds = [];

    for ($f = 0; $f < count($formations); $f++) {
      $formationQuizId = 0;
      if (isset($formations[$f]['quiz_id']) && is_numeric($formations[$f]['quiz_id'])) {
        $formationQuizId = (int) $formations[$f]['quiz_id'];
      }

      if ($formationQuizId > 0 && !in_array($formationQuizId, $quizIds, true)) {
        $quizIds[] = $formationQuizId;
      }
    }

    if (count($quizIds) > 0) {
      $placeholders = [];
      $paramsQuiz = [];

      for ($i = 0; $i < count($quizIds); $i++) {
        $key = ':quiz_' . (string) $i;
        $placeholders[] = $key;
        $paramsQuiz[$key] = $quizIds[$i];
      }

      $sqlQuizQuestions = 'SELECT q.id_quizz, q.id_question, q.enonce, q.type, q.points, q.ordre,
                    r.id_reponse, r.texte
                 FROM questions q
                 LEFT JOIN reponses r ON r.id_question = q.id_question
                 WHERE q.id_quizz IN (' . implode(', ', $placeholders) . ')
                 ORDER BY q.id_quizz ASC, q.ordre ASC, q.id_question ASC, r.id_reponse ASC';
      $stmtQuizQuestions = $pdo->prepare($sqlQuizQuestions);

      foreach ($paramsQuiz as $key => $value) {
        $stmtQuizQuestions->bindValue($key, (int) $value, PDO::PARAM_INT);
      }

      $stmtQuizQuestions->execute();
      $quizQuestionRows = $stmtQuizQuestions->fetchAll();

      for ($q = 0; $q < count($quizQuestionRows); $q++) {
        $row = $quizQuestionRows[$q];
        $quizId = 0;
        if (isset($row['id_quizz']) && is_numeric($row['id_quizz'])) {
          $quizId = (int) $row['id_quizz'];
        }

        $questionId = 0;
        if (isset($row['id_question']) && is_numeric($row['id_question'])) {
          $questionId = (int) $row['id_question'];
        }

        if ($quizId <= 0 || $questionId <= 0) {
          continue;
        }

        if (!isset($quizQuestionsByQuiz[$quizId])) {
          $quizQuestionsByQuiz[$quizId] = [];
        }

        if (!isset($quizQuestionsByQuiz[$quizId][$questionId])) {
          $questionPoints = 1;
          if (isset($row['points']) && is_numeric($row['points']) && (int) $row['points'] > 0) {
            $questionPoints = (int) $row['points'];
          }

          $quizQuestionsByQuiz[$quizId][$questionId] = [
            'id_question' => $questionId,
            'enonce' => trim((string) $row['enonce']),
            'type' => trim((string) $row['type']),
            'points' => $questionPoints,
            'reponses' => []
          ];
        }

        if (isset($row['id_reponse']) && is_numeric($row['id_reponse']) && (int) $row['id_reponse'] > 0) {
          $quizQuestionsByQuiz[$quizId][$questionId]['reponses'][] = [
            'id_reponse' => (int) $row['id_reponse'],
            'texte' => trim((string) $row['texte'])
          ];
        }
      }

      foreach ($quizQuestionsByQuiz as $quizId => $questionsMap) {
        $quizQuestionsByQuiz[$quizId] = array_values($questionsMap);
      }
    }
  } catch (PDOException $e) {
    $quizQuestionsLoadMessage = 'Impossible de charger les questions du quiz pour le moment.';
  }
}

if ($workshopsAssociationEnabled) {
    try {
        $selectProgram = 'NULL AS program_details';
        if ($hasWorkshopsProgramDetails) {
            $selectProgram = 'w.program_details AS program_details';
        }

        $selectWorkshopMeet = 'NULL AS workshop_meet_link';
        if ($workshopMeetColumn !== null) {
            $selectWorkshopMeet = 'w.' . $workshopMeetColumn . ' AS workshop_meet_link';
        }

        $selectWorkshopDate = 'NULL AS workshop_date_atelier';
        if ($hasWorkshopsDateAtelierColumn) {
          $selectWorkshopDate = 'w.date_atelier AS workshop_date_atelier';
        }

        $selectWorkshopPrice = 'NULL AS workshop_prix';
        if ($hasWorkshopsPriceColumn) {
          $selectWorkshopPrice = 'w.prix AS workshop_prix';
        }

        $selectWorkshopCertification = 'NULL AS workshop_certification';
        if ($hasWorkshopsCertificationColumn) {
          $selectWorkshopCertification = 'w.certification AS workshop_certification';
        }

        $selectWorkshopStatus = 'NULL AS workshop_statut';
        if ($hasWorkshopsStatusColumn) {
          $selectWorkshopStatus = 'w.statut AS workshop_statut';
        }

        if ($hasFormationsWorkshopsTable || $hasWorkshopsFormationTable) {
          $linksSqlParts = [];

          if ($hasFormationsWorkshopsTable) {
            $linksSqlParts[] = 'SELECT fw.id_formation, fw.id_workshop FROM formations_workshops fw';
          }

          if ($hasWorkshopsFormationTable) {
            $linksSqlParts[] = 'SELECT wf.id_formation, wf.id_workshop FROM workshops_formation wf';
          }

          $sqlWorkshops = 'SELECT l.id_formation,
                      w.id_workshop, w.titre, w.description, w.duree, w.lieu, w.places_max, w.places_restantes,
                      ' . $selectProgram . ',
                      ' . $selectWorkshopMeet . ',
                   ' . $selectWorkshopDate . ',
                   ' . $selectWorkshopPrice . ',
                   ' . $selectWorkshopCertification . ',
                   ' . $selectWorkshopStatus . ',
                      TRIM(CONCAT(COALESCE(u.prenom, ""), " ", COALESCE(u.nom, ""))) AS workshop_mentor_nom
                   FROM (' . implode(' UNION ', $linksSqlParts) . ') l
                   INNER JOIN workshops w ON w.id_workshop = l.id_workshop
                   LEFT JOIN `user` u ON u.id_user = w.mentor_id
                   ORDER BY l.id_formation ASC, w.id_workshop DESC';
        } else {
            $sqlWorkshops = 'SELECT w.id_formation,
                                    w.id_workshop, w.titre, w.description, w.duree, w.lieu, w.places_max, w.places_restantes,
                                    ' . $selectProgram . ',
                                    ' . $selectWorkshopMeet . ',
                     ' . $selectWorkshopDate . ',
                     ' . $selectWorkshopPrice . ',
                     ' . $selectWorkshopCertification . ',
                     ' . $selectWorkshopStatus . ',
                                    TRIM(CONCAT(COALESCE(u.prenom, ""), " ", COALESCE(u.nom, ""))) AS workshop_mentor_nom
                             FROM workshops w
                             LEFT JOIN `user` u ON u.id_user = w.mentor_id
                             WHERE w.id_formation IS NOT NULL
                             ORDER BY w.id_formation ASC, w.id_workshop DESC';
        }

        $stmtWorkshops = $pdo->query($sqlWorkshops);
        $workshopsRows = $stmtWorkshops->fetchAll();

        for ($i = 0; $i < count($workshopsRows); $i++) {
            $rowWorkshop = $workshopsRows[$i];
            $formationIdWorkshop = 0;
            if (isset($rowWorkshop['id_formation']) && is_numeric($rowWorkshop['id_formation'])) {
                $formationIdWorkshop = (int) $rowWorkshop['id_formation'];
            }

            if ($formationIdWorkshop <= 0) {
                continue;
            }

            if (!isset($workshopsByFormation[$formationIdWorkshop])) {
                $workshopsByFormation[$formationIdWorkshop] = [];
            }

            $workshopsByFormation[$formationIdWorkshop][] = $rowWorkshop;
        }
    } catch (PDOException $e) {
        $workshopsLoadMessage = 'Impossible de charger les ateliers associés pour le moment.';
    }
}

// ── UI labels (FR / EN / AR) ─────────────────────────────────────────────────
$ui = [
    'fr' => [
        'hero_title'      => 'Développez votre <em>savoir-faire</em><br>artisanal tunisien',
        'hero_subtitle'   => 'Des formations structurées par des mentors certifiés, liées à de vrais projets artisanaux. Obtenez un certificat CraftLink reconnu.',
        'search_ph'       => 'Rechercher une formation...',
        'search_btn'      => 'Rechercher',
        'filter_label'    => 'Filtrer la liste :',
        'all_levels'      => 'Tous niveaux',
        'with_certif'     => 'Avec certification',
        'without_certif'  => 'Sans certification',
        'certif_or_not'   => 'Certif ou non',
        'apply'           => 'Appliquer',
        'reset'           => 'Réinitialiser',
        'section_title'   => 'Formations disponibles',
        'see_details'     => 'Voir détails',
        'stat_formations' => 'Formations dans la base',
        'stat_mentors'    => 'Mentors',
        'stat_inscrits'   => 'Inscriptions',
        'stat_results'    => 'Résultats affichés',
        'lang_hint'       => '',
        'lang_back'       => '',
    ],
    'en' => [
        'hero_title'      => 'Develop your <em>expertise</em><br>in Tunisian craftsmanship',
        'hero_subtitle'   => 'Structured courses taught by certified mentors, linked to real artisan projects. Earn a recognised CraftLink certificate.',
        'search_ph'       => 'Search a course...',
        'search_btn'      => 'Search',
        'filter_label'    => 'Filter the list:',
        'all_levels'      => 'All levels',
        'with_certif'     => 'With certification',
        'without_certif'  => 'Without certification',
        'certif_or_not'   => 'All',
        'apply'           => 'Apply',
        'reset'           => 'Reset',
        'section_title'   => 'Available Courses',
        'see_details'     => 'See details',
        'stat_formations' => 'Courses in database',
        'stat_mentors'    => 'Mentors',
        'stat_inscrits'   => 'Enrolments',
        'stat_results'    => 'Results shown',
        'lang_hint'       => '🌐 Content displayed in English',
        'lang_back'       => 'Switch to French',
    ],
    'ar' => [
        'hero_title'      => 'طوّر <em>مهاراتك</em><br>في الحرف التونسية',
        'hero_subtitle'   => 'دورات تكوينية منظّمة بإشراف مدرّبين معتمدين، مرتبطة بمشاريع حرفية حقيقية. احصل على شهادة CraftLink المعترف بها.',
        'search_ph'       => 'ابحث عن تكوين...',
        'search_btn'      => 'بحث',
        'filter_label'    => 'تصفية القائمة :',
        'all_levels'      => 'جميع المستويات',
        'with_certif'     => 'مع شهادة',
        'without_certif'  => 'بدون شهادة',
        'certif_or_not'   => 'الكل',
        'apply'           => 'تطبيق',
        'reset'           => 'إعادة تعيين',
        'section_title'   => 'التكوينات المتاحة',
        'see_details'     => 'عرض التفاصيل',
        'stat_formations' => 'التكوينات في القاعدة',
        'stat_mentors'    => 'المدرّبون',
        'stat_inscrits'   => 'التسجيلات',
        'stat_results'    => 'نتائج معروضة',
        'lang_hint'       => '🌐 المحتوى معروض بالعربية',
        'lang_back'       => 'العودة إلى الفرنسية',
    ],
];
$t = $ui[$activeLang] ?? $ui['fr'];
$htmlDir = $activeLang === 'ar' ? 'rtl' : 'ltr';
// ── Fin UI labels ────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="<?php echo e($activeLang); ?>" dir="<?php echo e($htmlDir); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Formations CraftLink</title>
<link href="https://fonts.googleapis.com/css2?family=Georgia&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Calibri:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../view/style.css">
<style>
.inline-alert {
  max-width: 1100px;
  margin: 16px auto 0;
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
.inline-alert.info {
  background: rgba(139, 90, 58, 0.10);
  color: #4d3422;
  border: 1px solid rgba(139, 90, 58, 0.25);
}
.filter-form {
  max-width: 1100px;
  margin: 0 auto;
  padding: 10px 32px 0;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
.filter-form select {
  padding: 8px 12px;
  border-radius: 20px;
  border: 1.5px solid var(--caramel);
  color: var(--marron);
  background: transparent;
}
.filter-form .btn-inscrit {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
}
.filter-actions-right {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
}
.filter-actions-right .btn-inscrit {
  white-space: nowrap;
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
}
.btn-cancel {
  border: 1px solid rgba(196,154,108,0.5);
  color: var(--gris);
  background: transparent;
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
}
.btn-save {
  border: none;
  color: var(--creme);
  background: var(--marron);
  border-radius: 6px;
  padding: 9px 14px;
  cursor: pointer;
}
.btn-save:hover {
  background: var(--brun);
}
.form-subtitle {
  margin: 10px 0 8px;
  color: var(--marron);
  font-weight: 700;
  font-size: 0.9rem;
}
.form-note {
  margin-top: 6px;
  font-size: 0.78rem;
  color: var(--gris);
}
.field-alert {
  display: none;
  margin-top: 6px;
  font-size: 0.76rem;
  color: #7b1f15;
}
.field-alert.show {
  display: block;
}
.modal .input-error {
  border-color: #c0392b !important;
  background: rgba(192, 57, 43, 0.06);
}
.form-feedback {
  display: none;
  margin-top: 8px;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 0.8rem;
}
.form-feedback.show {
  display: block;
}
.form-feedback.error {
  background: rgba(192, 57, 43, 0.12);
  border: 1px solid rgba(192, 57, 43, 0.32);
  color: #7b1f15;
}
.form-feedback.success {
  background: rgba(46,107,62,0.12);
  border: 1px solid rgba(46,107,62,0.32);
  color: #1f4d2b;
}
.form-feedback.info {
  background: rgba(139, 90, 58, 0.10);
  border: 1px solid rgba(139, 90, 58, 0.25);
  color: #4d3422;
}
.search-bar + .form-feedback {
  max-width: 520px;
  margin: 10px auto 0;
}
.filter-form + .form-feedback {
  max-width: 1100px;
  margin: 10px auto 0;
}
.search-bar .input-error {
  box-shadow: inset 0 0 0 2px rgba(192, 57, 43, 0.7);
}
.filter-form .input-error,
.quiz-submit-form .input-error,
.quiz-builder-answer-row .input-error,
.quiz-builder-question .input-error {
  border-color: #c0392b !important;
  background: rgba(192, 57, 43, 0.06);
}
.quiz-question-item.quiz-question-error {
  border: 1px solid rgba(192, 57, 43, 0.35);
  border-radius: 8px;
  background: rgba(192, 57, 43, 0.05);
  padding: 8px;
}
.quiz-builder-wrap {
  display: flex;
  flex-direction: column;
  gap: 10px;
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
.quiz-builder-answer-row label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 0.78rem;
  color: var(--gris);
  white-space: nowrap;
}
.quiz-builder-question .btn-inscrit {
  text-decoration: none;
}
.quiz-builder-actions {
  display: flex;
  justify-content: flex-start;
  margin-top: 4px;
}
.workshop-builder-wrap {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.workshop-builder-item {
  border: 1px solid rgba(196,154,108,0.35);
  border-radius: 8px;
  background: rgba(245,236,215,0.5);
  padding: 10px;
}
.workshop-builder-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}
.workshop-builder-head strong {
  color: var(--marron);
  font-size: 0.85rem;
}
.workshop-builder-actions {
  display: flex;
  justify-content: flex-start;
  margin-top: 4px;
}
.quiz-builder-hidden {
  display: none;
}
#modalAjoutFront {
  align-items: flex-start;
  padding: 18px 0;
  overflow-y: auto;
}
#modalAjoutFront .modal {
  width: calc(100% - 24px);
  max-width: 620px;
  max-height: calc(100vh - 36px);
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}
#modalAjoutFront .modal-body {
  overflow-y: auto;
}
#modalAjoutFront .form-group {
  margin-bottom: 12px;
}
#modalAjoutFront .modal-footer {
  position: sticky;
  bottom: 0;
  background: var(--blanc);
  padding-top: 10px;
}
.formation-card {
  cursor: pointer;
}
.formation-card:focus-visible {
  outline: 2px solid var(--marron);
  outline-offset: 2px;
}
.formation-card .card-info {
  min-height: 24px;
}
.formation-detail-btn {
  text-decoration: none;
}
.formation-detail-view {
  max-width: 1100px;
  margin: 0 auto 36px;
  padding: 0 32px;
  display: none;
}
.formation-detail-view.open {
  display: block;
}
.formation-detail-card {
  display: none;
  background: var(--blanc);
  border: 1px solid rgba(196,154,108,0.2);
  border-radius: 12px;
  box-shadow: 0 6px 24px rgba(59,35,20,0.08);
  padding: 22px;
}
.detail-actions-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.detail-actions-top .btn-inscrit,
.detail-actions-top .btn-cancel {
  text-decoration: none;
}
.detail-head h2 {
  font-family: 'Playfair Display', serif;
  color: var(--brun);
  margin-bottom: 8px;
}
.detail-chip-row {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.detail-chip {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  color: var(--marron);
  border: 1px solid rgba(139,90,58,0.28);
  background: rgba(245,236,215,0.7);
}
.detail-description {
  margin: 16px 0;
  color: var(--gris);
  line-height: 1.6;
}
.detail-meta-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-bottom: 18px;
}
.detail-meta-item {
  background: rgba(245,236,215,0.65);
  border: 1px solid rgba(196,154,108,0.2);
  border-radius: 8px;
  padding: 10px;
}
.detail-meta-item .k {
  display: block;
  font-size: 0.75rem;
  color: var(--gris);
}
.detail-meta-item .v {
  display: block;
  font-size: 0.95rem;
  color: var(--brun);
  font-weight: 700;
}
.detail-block {
  border-top: 1px solid rgba(196,154,108,0.2);
  padding-top: 14px;
  margin-top: 14px;
}
.detail-block h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1rem;
  color: var(--brun);
  margin-bottom: 8px;
}
.detail-block p {
  color: var(--gris);
  line-height: 1.55;
  font-size: 0.9rem;
}
.detail-block-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}
.detail-video-embed {
  width: 100%;
  max-width: 760px;
  aspect-ratio: 16 / 9;
  border-radius: 10px;
  border: 1px solid rgba(196,154,108,0.25);
  overflow: hidden;
  background: #000;
  margin-top: 8px;
}
.detail-video-embed iframe {
  width: 100%;
  height: 100%;
  border: 0;
}
.detail-link {
  display: inline-flex;
  margin-top: 8px;
  text-decoration: none;
}
.formation-quiz-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  font-size: 0.8rem;
  color: var(--gris);
  margin-bottom: 10px;
}
.formation-quiz-hint {
  margin-bottom: 10px;
  font-size: 0.8rem;
  color: var(--gris);
}
.quiz-launch-row {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 10px;
}
.quiz-launch-row .form-note {
  margin: 0;
}
.quiz-questions-panel {
  display: none;
  border: 1px solid rgba(196,154,108,0.25);
  border-radius: 10px;
  background: rgba(245,236,215,0.6);
  padding: 12px;
  margin-top: 10px;
}
.quiz-questions-panel.open {
  display: block;
}
.quiz-submit-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.quiz-question-item {
  border-bottom: 1px dashed rgba(196,154,108,0.35);
  padding-bottom: 10px;
}
.quiz-question-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.quiz-question-title {
  margin: 0 0 6px;
  color: var(--brun);
  font-size: 0.9rem;
  font-weight: 700;
}
.quiz-answer-option {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin: 6px 0;
  color: var(--gris);
  font-size: 0.85rem;
}
.quiz-answer-option input {
  margin-top: 2px;
}
.quiz-submit-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.quiz-feedback {
  margin-top: 10px;
  padding: 8px 10px;
  border-radius: 6px;
  font-size: 0.84rem;
}
.quiz-feedback.success {
  background: rgba(46,107,62,0.12);
  border: 1px solid rgba(46,107,62,0.32);
  color: #1f4d2b;
}
.quiz-feedback.error {
  background: rgba(192,57,43,0.12);
  border: 1px solid rgba(192,57,43,0.32);
  color: #7b1f15;
}
.associated-workshops {
  display: none;
  margin-top: 10px;
}
.associated-workshops.open {
  display: block;
}
.associated-workshop-item {
  border: 1px solid rgba(196,154,108,0.2);
  border-radius: 10px;
  padding: 12px;
  background: rgba(245,236,215,0.55);
  margin-bottom: 10px;
}
.associated-workshop-item h4 {
  font-size: 1rem;
  color: var(--brun);
  margin-bottom: 6px;
}
.workshop-meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin: 8px 0;
  font-size: 0.82rem;
  color: var(--gris);
}
.workshop-program {
  margin-top: 8px;
}
.workshop-program strong {
  display: block;
  color: var(--marron);
  margin-bottom: 4px;
  font-size: 0.84rem;
}
.workshop-actions {
  margin-top: 10px;
}
.btn-meet {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 7px 12px;
  border-radius: 6px;
  text-decoration: none;
  background: var(--marron);
  color: var(--creme);
  font-size: 0.8rem;
  font-weight: 600;
}
.btn-meet:hover {
  background: var(--brun);
}
.btn-meet.disabled {
  background: rgba(107,91,78,0.2);
  color: var(--gris);
  cursor: not-allowed;
}
.migration-hint-list {
  margin: 4px 0 0 18px;
  color: #4d3422;
}
.front-nav {
  justify-content: space-between;
}
.front-nav .logo {
  position: static;
  left: auto;
}
.front-nav .nav-admin-actions {
  margin-left: auto;
  list-style: none;
  display: flex;
}
.front-nav .admin-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  border-radius: 20px;
  text-decoration: none;
  background: var(--caramel);
  color: var(--brun);
  font-size: 0.82rem;
  font-weight: 700;
  letter-spacing: 0.4px;
  transition: background 0.2s, color 0.2s;
}
.front-nav .admin-link:hover {
  background: var(--creme);
  color: var(--brun);
}
/* Language switcher active state */
.front-nav .admin-link.lang-active {
  background: var(--marron);
  color: var(--creme);
}
/* Arabic RTL card support */
[dir="rtl"] .card-body h3,
[dir="rtl"] .card-body p,
[dir="rtl"] .detail-description {
  text-align: right;
}
@media (max-width: 900px) {
  .front-nav {
    padding: 0 20px;
  }
  .detail-meta-grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 800px) {
  .filter-actions-right {
    margin-left: 0;
    width: 100%;
    justify-content: flex-end;
  }
  .front-form-row-2,
  .front-form-row-3 {
    grid-template-columns: 1fr;
  }
  .formation-detail-view {
    padding: 0 20px;
  }
}
/* Darija explanation styles */
/* Darija explanation styles - Enhanced */
.darija-explain-btn {
    background: #C8860A !important;
    color: white !important;
    border: none !important;
    transition: all 0.3s;
}

.darija-explain-btn:hover {
    background: #a56e08 !important;
    transform: scale(1.02);
}

.darija-explain-btn:disabled {
    opacity: 0.6;
    cursor: wait;
    transform: none;
}

.darija-explanation-card {
    margin-top: 20px;
    padding: 18px;
    background: linear-gradient(135deg, #fff8ee, #fffdf5);
    border-radius: 16px;
    border-right: 4px solid #C8860A;
    animation: slideIn 0.5s ease-out;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.darija-explanation-text {
    font-size: 16px;
    line-height: 1.8;
    color: #2c1d05;
    text-align: right;
    direction: rtl;
    margin-bottom: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.darija-audio-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0d090;
}

.darija-play-btn {
    background: linear-gradient(135deg, #C8860A, #E5A020);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s;
}

.darija-play-btn:hover {
    background: linear-gradient(135deg, #a56e08, #c8860a);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(200,134,10,0.3);
}

.darija-play-btn.playing {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    animation: pulse 1s infinite;
}

.darija-stop-btn {
    background: #7f8c8d;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s;
}

.darija-stop-btn:hover {
    background: #6c7a7a;
}

.darija-note {
    margin-top: 10px;
    font-size: 11px;
    color: #C8860A;
    text-align: center;
    direction: rtl;
}

.darija-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #C8860A;
    font-size: 14px;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── English Voice Button ── */
.english-explain-btn {
    background: #1a6fa8 !important;
    color: white !important;
    border: none !important;
    transition: all 0.3s;
}
.english-explain-btn:hover {
    background: #155a8a !important;
    transform: scale(1.02);
}
.english-explain-btn:disabled {
    opacity: 0.6;
    cursor: wait;
    transform: none;
}

/* ── English Explanation Card ── */
.english-explanation-card {
    margin-top: 20px;
    padding: 18px;
    background: linear-gradient(135deg, #eef5fb, #f5f9ff);
    border-radius: 16px;
    border-left: 4px solid #1a6fa8;
    animation: slideIn 0.5s ease-out;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.english-explanation-text {
    font-size: 16px;
    line-height: 1.8;
    color: #0d2b40;
    text-align: left;
    direction: ltr;
    margin-bottom: 15px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.english-audio-controls {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #b3d4ec;
}
.english-play-btn {
    background: linear-gradient(135deg, #1a6fa8, #2196d3);
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s;
}
.english-play-btn:hover {
    background: linear-gradient(135deg, #155a8a, #1a6fa8);
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(26,111,168,0.3);
}
.english-play-btn.playing {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    animation: pulse 1s infinite;
}
.english-stop-btn {
    background: #7f8c8d;
    color: white;
    border: none;
    border-radius: 10px;
    padding: 10px 20px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.2s;
}
.english-stop-btn:hover { background: #6c7a7a; }
.english-loading {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #1a6fa8;
    font-size: 14px;
}
.english-note {
    margin-top: 10px;
    font-size: 11px;
    color: #1a6fa8;
    text-align: center;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}
/* Voice availability hint */
.voice-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 10px;
    margin: 10px 0;
    font-size: 12px;
    color: #856404;
    text-align: center;
    direction: rtl;
}
</style>
</head>

<body>
<nav class="front-nav">
  <div class="logo">
    ح <span>CraftLink Tunisie</span>
  </div>
  <ul class="nav-admin-actions">
    <li style="display:flex;align-items:center;gap:6px;margin-right:12px;">
      <?php
        $buildLangUrl = function($lang) {
            $params = $_GET;
            $params['lang'] = $lang;
            unset($params['ok']);
            return 'front.php?' . http_build_query($params);
        };
      ?>

    </li>
    <li><a href="back.php" class="admin-link">Admin</a></li>
  </ul>
</nav>

<section class="hero">
  <h1><?php echo $t['hero_title']; ?></h1>
  <p><?php echo e($t['hero_subtitle']); ?></p>
  <?php if ($activeLang !== 'fr' && $t['lang_hint'] !== ''): ?>
  <p style="margin-top:6px;font-size:0.82rem;color:rgba(255,255,255,0.8);">
    <?php echo e($t['lang_hint']); ?>
    — <a href="front.php?lang=fr" style="color:rgba(255,255,255,0.9);text-decoration:underline;"><?php echo e($t['lang_back']); ?></a>
  </p>
  <?php endif; ?>
  <form method="get" action="front.php" class="search-bar" id="formRechercheFront" novalidate>
    <input type="hidden" name="lang" value="<?php echo e($activeLang); ?>">
    <input type="text" name="q" value="<?php echo e($recherche); ?>" placeholder="<?php echo e($t['search_ph']); ?>">
    <button type="submit"><?php echo e($t['search_btn']); ?></button>
  </form>
</section>

<div class="stats-bar">
  <div class="stat"><strong><?php echo e((string) $totalFormations); ?></strong><span><?php echo e($t['stat_formations']); ?></span></div>
  <div class="stat"><strong><?php echo e((string) count($mentorsUniques)); ?></strong><span><?php echo e($t['stat_mentors']); ?></span></div>
  <div class="stat"><strong><?php echo e((string) $totalInscrits); ?>+</strong><span><?php echo e($t['stat_inscrits']); ?></span></div>
  <div class="stat"><strong><?php echo e((string) count($formations)); ?></strong><span><?php echo e($t['stat_results']); ?></span></div>
</div>

<div class="filter-section">
  <span class="filter-label"><?php echo e($t['filter_label']); ?></span>
</div>
<form method="get" action="front.php" class="filter-form" id="formFiltresFront" novalidate>
  <input type="hidden" name="q" value="<?php echo e($recherche); ?>">
  <input type="hidden" name="lang" value="<?php echo e($activeLang); ?>">
  <select name="niveau">
    <option value=""><?php echo e($t['all_levels']); ?></option>
    <option value="debutant" <?php echo ($niveauFiltreDb === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
    <option value="intermediaire" <?php echo ($niveauFiltreDb === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
    <option value="avance" <?php echo ($niveauFiltreDb === 'avance' ? 'selected' : ''); ?>>Avancé</option>
  </select>
  <select name="certif">
    <option value=""><?php echo e($t['certif_or_not']); ?></option>
    <option value="oui" <?php echo ($certifFiltre === 'oui' ? 'selected' : ''); ?>><?php echo e($t['with_certif']); ?></option>
    <option value="non" <?php echo ($certifFiltre === 'non' ? 'selected' : ''); ?>><?php echo e($t['without_certif']); ?></option>
  </select>
  <button type="submit" class="btn-inscrit"><?php echo e($t['apply']); ?></button>
  <a href="front.php?lang=<?php echo e($activeLang); ?>" class="btn-inscrit vert"><?php echo e($t['reset']); ?></a>
</form>

<?php if ($succes !== ''): ?>
  <div class="inline-alert success"><?php echo e($succes); ?></div>
<?php endif; ?>

<?php if ($erreur !== ''): ?>
  <div class="inline-alert error"><?php echo e($erreur); ?></div>
<?php endif; ?>

<?php if ($quizFeedback['message'] !== ''): ?>
  <div class="inline-alert <?php echo ($quizFeedback['status'] === 'success' ? 'success' : 'error'); ?>"><?php echo e($quizFeedback['message']); ?></div>
<?php endif; ?>



<div class="section-title container"><?php echo e($t['section_title']); ?></div>
<div class="berber-divider container">◆ ◇ ◆ ◇ ◆</div>

<div id="formationsGridSection" class="cards-wrapper">
  <div class="cards-grid" id="cardsGrid">
    <?php if (count($formations) === 0): ?>
      <div class="card">
        <div class="card-body">
          <h3>Aucune formation trouvée</h3>
          <p>Essayez un autre filtre ou ajoutez une nouvelle formation depuis le backoffice.</p>
        </div>
      </div>
    <?php else: ?>
      <?php for ($i = 0; $i < count($formations); $i++): ?>
        <?php
          $f = $formations[$i];
          $formationId = (int) $f['id_formation'];
          $niveau = niveauLabel($f['niveau']);
          $certif = certifOui($f['certification']);
          $displayDomaine = getTranslatedField($f, 'domaine', $activeLang);
          $description = getTranslatedField($f, 'description', $activeLang);
          if ($description === '') {
              $description = 'Description bientôt disponible.';
          }
          $avatar = initials($f['formateur']);
          $assocCount = 0;
          if (isset($workshopsByFormation[$formationId])) {
              $assocCount = count($workshopsByFormation[$formationId]);
          }
        ?>
        <div class="card formation-card" tabindex="0" role="button" data-formation-id="<?php echo e((string) $formationId); ?>" aria-label="Voir les détails de la formation <?php echo e($displayDomaine); ?>">
          <div class="card-body">
            <div class="card-meta">
              <span class="tag tag-niveau"><?php echo e($niveau); ?></span>
              <?php if ($certif): ?>
                <span class="tag tag-certif">📜 Certifiant</span>
              <?php endif; ?>
            </div>
            <h3><?php echo e($displayDomaine); ?></h3>
            <p><?php echo e($description); ?></p>
            <div class="card-info">
              <span>🕐 <?php echo e((string) $f['duree']); ?>h</span>
              <span>👤 <?php echo e((string) $f['inscrits']); ?> inscrits</span>
              <span>🎓 <?php echo e((string) $assocCount); ?> atelier(s) lié(s)</span>
            </div>
          </div>
          <div class="card-mentor">
            <div class="mentor-avatar" style="background:var(--marron)"><?php echo e($avatar); ?></div>
            <div class="mentor-info">
              <strong><?php echo e($f['formateur']); ?></strong>
              <small>Tunisie</small>
            </div>
          </div>
          <div class="card-footer">
            <div class="price"><?php echo e(formatPrix($f['prix'])); ?> TND
              <small><?php echo ($certif ? 'Certificat inclus' : 'Attestation de suivi'); ?></small>
            </div>
            <button type="button" class="btn-inscrit formation-detail-btn" data-formation-id="<?php echo e((string) $formationId); ?>"><?php echo e($t['see_details']); ?></button>
          </div>
        </div>
      <?php endfor; ?>
    <?php endif; ?>
  </div>
</div>

<div id="formationDetailView" class="formation-detail-view" aria-live="polite">
  <?php for ($i = 0; $i < count($formations); $i++): ?>
    <?php
      $f = $formations[$i];
      $formationId = (int) $f['id_formation'];
      $niveau = niveauLabel($f['niveau']);
      $certif = certifOui($f['certification']);
      $displayDomaine = getTranslatedField($f, 'domaine', $activeLang);
      $description = getTranslatedField($f, 'description', $activeLang);
      if ($description === '') {
          $description = 'Description bientôt disponible.';
      }

        $videoUrl = '';
        if (isset($f['video_url'])) {
          $videoUrl = trim((string) $f['video_url']);
        }
        $videoFromDescription = false;
        if ($videoUrl === '') {
          $videoUrl = extractFirstUrlFromText($description);
          $videoFromDescription = ($videoUrl !== '');
        }
      $embedUrl = videoEmbedUrl($videoUrl);
      $meetLink = trim((string) $f['meet_link']);
      $formationMeetIsValid = filter_var($meetLink, FILTER_VALIDATE_URL) !== false;

      $quizTitre = trim((string) $f['quiz_titre']);
      $quizDescription = trim((string) $f['quiz_description']);
      $quizNotePassage = 60;
      if (is_numeric($f['quiz_note_passage'])) {
          $quizNotePassage = (int) $f['quiz_note_passage'];
      }
      $quizTentatives = 3;
      if (is_numeric($f['quiz_nb_tentatives'])) {
          $quizTentatives = (int) $f['quiz_nb_tentatives'];
      }
      if ($quizTentatives <= 0) {
          $quizTentatives = 1;
      }

      $quizDuree = 20;
      if (is_numeric($f['quiz_duree_minutes'])) {
          $quizDuree = (int) $f['quiz_duree_minutes'];
      }

      $quizId = 0;
      if (isset($f['quiz_id']) && is_numeric($f['quiz_id'])) {
          $quizId = (int) $f['quiz_id'];
      }

        $quizQuestions = [];
        if ($quizId > 0 && isset($quizQuestionsByQuiz[$quizId]) && is_array($quizQuestionsByQuiz[$quizId])) {
          $quizQuestions = $quizQuestionsByQuiz[$quizId];
        }
        $quizPanelOpen = false;

      $attemptsUsed = 0;
      if ($quizId > 0 && isset($_SESSION['quiz_attempts']['quiz_' . (string) $quizId]) && is_numeric($_SESSION['quiz_attempts']['quiz_' . (string) $quizId])) {
          $attemptsUsed = (int) $_SESSION['quiz_attempts']['quiz_' . (string) $quizId];
      }
      if ($attemptsUsed < 0) {
          $attemptsUsed = 0;
      }
      $attemptsLeft = $quizTentatives - $attemptsUsed;
      if ($attemptsLeft < 0) {
          $attemptsLeft = 0;
      }

        $quizHasFinalScore = (
          $quizFeedback['formation_id'] === $formationId
          && isset($quizFeedback['is_final_score'])
          && $quizFeedback['is_final_score'] === true
        );

      $workshopsAssocies = [];
      if (isset($workshopsByFormation[$formationId])) {
          $workshopsAssocies = $workshopsByFormation[$formationId];
      }
    ?>
    <article class="formation-detail-card" data-detail-id="<?php echo e((string) $formationId); ?>">
      <div class="detail-actions-top">
    <button type="button" class="btn-cancel" onclick="closeFormationDetails()">← Retour aux formations</button>
    
    
    <button type="button" class="btn-inscrit darija-explain-btn" 
            data-formation-id="<?php echo e((string) $formationId); ?>"
            data-formation-title="<?php echo e($f['domaine']); ?>"
            data-formation-description="<?php echo e(trim((string) $f['description'])); ?>"
            data-formation-niveau="<?php echo e($f['niveau']); ?>"
            data-formation-duree="<?php echo e($f['duree']); ?>"
            data-formation-prix="<?php echo e(formatPrix($f['prix'])); ?>"
            data-formation-certif="<?php echo e($f['certification']); ?>"
            onclick="explainFormationInDarija(this)">
        🔊 شرح بالدارجة
    </button>

   <button type="button" class="btn-inscrit english-explain-btn"
        data-formation-id="<?php echo e((string) $formationId); ?>"
        data-formation-title="<?php echo e($f['domaine']); ?>"
        data-formation-description="<?php echo e(trim((string) $f['description'])); ?>"
        data-formation-niveau="<?php echo e($f['niveau']); ?>"
        data-formation-duree="<?php echo e($f['duree']); ?>"
        data-formation-prix="<?php echo e(formatPrix($f['prix'])); ?>"
        data-formation-certif="<?php echo e($f['certification']); ?>"
        onclick="explainFormationInEnglish(this)">
    🔊 Explain in English
</button>
    
    <?php if ($formationMeetIsValid): ?>
        <a href="<?php echo e($meetLink); ?>" target="_blank" rel="noopener noreferrer" class="btn-inscrit">Rencontrer le formateur</a>
    <?php endif; ?>
</div>

      <div class="detail-head">
        <h2><?php echo e($displayDomaine); ?></h2>
        <div class="detail-chip-row">
          <span class="detail-chip">📚 <?php echo e($niveau); ?></span>
          <?php if ($certif): ?>
            <span class="detail-chip">📜 Formation certifiante</span>
          <?php else: ?>
            <span class="detail-chip">📝 Attestation de suivi</span>
          <?php endif; ?>
          <span class="detail-chip">🧪 Quiz requis</span>
          <span class="detail-chip">🎓 <?php echo e((string) count($workshopsAssocies)); ?> atelier(s) associé(s)</span>
        </div>
      </div>

      <p class="detail-description" <?php echo ($activeLang === 'ar' ? 'dir="rtl" style="text-align:right"' : ''); ?>><?php echo e($description); ?></p>

      <div class="detail-meta-grid">
        <div class="detail-meta-item">
          <span class="k">Mentor</span>
          <span class="v"><?php echo e($f['formateur']); ?></span>
        </div>
        <div class="detail-meta-item">
          <span class="k">Durée</span>
          <span class="v"><?php echo e((string) $f['duree']); ?>h</span>
        </div>
        <div class="detail-meta-item">
          <span class="k">Prix</span>
          <span class="v"><?php echo e(formatPrix($f['prix'])); ?> TND</span>
        </div>
        <div class="detail-meta-item">
          <span class="k">Niveau</span>
          <span class="v"><?php echo e($niveau); ?></span>
        </div>
      </div>

      <section class="detail-block">
        <h3>Certification</h3>
        <?php if ($certif): ?>
          <p>Cette formation délivre un certificat CraftLink après réussite du quiz final.</p>
        <?php else: ?>
          <p>Cette formation propose une attestation de suivi (sans certification).</p>
        <?php endif; ?>
      </section>

      <section class="detail-block">
        <h3>Vidéos de la formation</h3>
        <?php if ($videoUrl !== ''): ?>
          <p>Lien vidéo associé à cette formation.</p>
          <?php if ($videoFromDescription): ?>
            <p class="form-note">Lien détecté automatiquement dans la description de la formation.</p>
          <?php endif; ?>
          <?php if ($embedUrl !== ''): ?>
            <div class="detail-video-embed">
              <iframe src="<?php echo e($embedUrl); ?>" allowfullscreen loading="lazy"></iframe>
            </div>
          <?php else: ?>
            <a href="<?php echo e($videoUrl); ?>" target="_blank" rel="noopener noreferrer" class="btn-inscrit detail-link">Ouvrir la vidéo</a>
          <?php endif; ?>
        <?php else: ?>
          <p>Aucune vidéo n'est encore configurée pour cette formation.</p>
        <?php endif; ?>
      </section>

      <section class="detail-block">
        <h3>Quiz de validation</h3>
        <?php if ($quizTitre !== ''): ?>
          <div class="formation-quiz-meta">
            <span>🧾 Titre: <?php echo e($quizTitre); ?></span>
            <span>⏱️ Durée: <?php echo e((string) $quizDuree); ?> min</span>
          </div>

          <?php if ($quizDescription !== ''): ?>
            <p class="formation-quiz-hint"><?php echo e($quizDescription); ?></p>
          <?php else: ?>
            <p class="formation-quiz-hint">Validez ce quiz pour compléter la formation.</p>
          <?php endif; ?>

          <?php if (!$quizHasFinalScore && $attemptsLeft > 0): ?>
            <div class="quiz-launch-row">
              <button type="button" class="btn-inscrit quiz-launch-btn" data-formation-id="<?php echo e((string) $formationId); ?>" onclick="toggleQuizPanel(<?php echo e((string) $formationId); ?>)"><?php echo ($quizPanelOpen ? 'Masquer quizz' : 'Passer quizz'); ?></button>
              <span class="form-note">Le score est calculé uniquement après validation des réponses.</span>
            </div>

            <div class="quiz-questions-panel<?php echo ($quizPanelOpen ? ' open' : ''); ?>" id="quizPanel<?php echo e((string) $formationId); ?>">
              <?php if ($quizQuestionsLoadMessage !== ''): ?>
                <p><?php echo e($quizQuestionsLoadMessage); ?></p>
              <?php elseif (!$quizQuestionsEnabled): ?>
                <p>Le quiz existe, mais la structure questions/reponses n'est pas disponible dans la base.</p>
              <?php elseif (count($quizQuestions) === 0): ?>
                <p>Ce quiz est configuré, mais aucune question/réponse n'a encore été ajoutée.</p>
              <?php else: ?>
                <form method="post" action="front.php" class="quiz-submit-form" novalidate>
                  <input type="hidden" name="action" value="submit_quiz">
                  <input type="hidden" name="formation_id" value="<?php echo e((string) $formationId); ?>">
                  <input type="hidden" name="q" value="<?php echo e($recherche); ?>">
                  <input type="hidden" name="niveau" value="<?php echo e($niveauFiltre); ?>">
                  <input type="hidden" name="certif" value="<?php echo e($certifFiltre); ?>">
                  <div class="form-feedback" data-form-message></div>

                  <?php for ($questionIndex = 0; $questionIndex < count($quizQuestions); $questionIndex++): ?>
                    <?php
                      $question = $quizQuestions[$questionIndex];
                      $questionId = (int) $question['id_question'];
                      $questionText = trim((string) $question['enonce']);
                      if ($questionText === '') {
                          $questionText = 'Question sans énoncé';
                      }

                      $questionType = trim((string) $question['type']);
                      $answerInputType = 'radio';
                      if ($questionType === 'choix_multiple') {
                          $answerInputType = 'checkbox';
                      }
                    ?>
                    <div class="quiz-question-item">
                      <p class="quiz-question-title">Question <?php echo e((string) ($questionIndex + 1)); ?>: <?php echo e($questionText); ?></p>

                      <?php if (!isset($question['reponses']) || count($question['reponses']) === 0): ?>
                        <p class="form-note">Aucune réponse proposée pour cette question.</p>
                      <?php else: ?>
                        <?php for ($ri = 0; $ri < count($question['reponses']); $ri++): ?>
                          <?php
                            $quizResponse = $question['reponses'][$ri];
                            $responseId = (int) $quizResponse['id_reponse'];
                            $responseText = trim((string) $quizResponse['texte']);
                            if ($responseText === '') {
                                $responseText = 'Réponse sans texte';
                            }

                            $answerName = 'answers[' . (string) $questionId . ']';
                            if ($answerInputType === 'checkbox') {
                                $answerName = 'answers[' . (string) $questionId . '][]';
                            }
                          ?>
                          <label class="quiz-answer-option">
                            <input type="<?php echo e($answerInputType); ?>" name="<?php echo e($answerName); ?>" value="<?php echo e((string) $responseId); ?>">
                            <span><?php echo e($responseText); ?></span>
                          </label>
                        <?php endfor; ?>
                      <?php endif; ?>
                    </div>
                  <?php endfor; ?>

                  <div class="quiz-submit-actions">
                    <button type="submit" class="btn-inscrit" <?php echo ($attemptsLeft <= 0 ? 'disabled' : ''); ?>>Valider mes réponses</button>
                  </div>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($attemptsLeft <= 0 && !$quizHasFinalScore): ?>
            <div class="quiz-feedback error">Tentatives épuisées pour ce quiz.</div>
          <?php endif; ?>

          <?php if ($quizFeedback['formation_id'] === $formationId && $quizFeedback['message'] !== ''): ?>
            <div class="quiz-feedback <?php echo ($quizFeedback['status'] === 'success' ? 'success' : 'error'); ?>"><?php echo e($quizFeedback['message']); ?></div>
          <?php endif; ?>
        <?php else: ?>
          <p>Aucun quiz n'est configuré pour cette formation.</p>
        <?php endif; ?>
      </section>

      <section class="detail-block">
        <div class="detail-block-head">
          <h3>Ateliers associés</h3>
          <button type="button" class="btn-inscrit vert workshops-toggle-btn" data-formation-id="<?php echo e((string) $formationId); ?>" onclick="toggleAssociatedWorkshops(<?php echo e((string) $formationId); ?>)">Voir ateliers</button>
        </div>

        <div class="associated-workshops" id="associatedWorkshops<?php echo e((string) $formationId); ?>">
          <?php if ($workshopsLoadMessage !== ''): ?>
            <p><?php echo e($workshopsLoadMessage); ?></p>
          <?php elseif (!$workshopsAssociationEnabled): ?>
            <p>La liaison formation-atelier n'est pas encore disponible (table formations_workshops/workshops_formation ou colonne workshops.id_formation manquante).</p>
          <?php elseif (count($workshopsAssocies) === 0): ?>
            <p>Aucun atelier associé à cette formation pour le moment.</p>
          <?php else: ?>
            <?php for ($w = 0; $w < count($workshopsAssocies); $w++): ?>
              <?php
                $workshop = $workshopsAssocies[$w];
                $workshopTitle = trim((string) $workshop['titre']);
                if ($workshopTitle === '') {
                    $workshopTitle = 'Atelier sans titre';
                }

                $workshopDescription = trim((string) $workshop['description']);
                if ($workshopDescription === '') {
                    $workshopDescription = 'Description atelier indisponible.';
                }

                $workshopProgram = trim((string) $workshop['program_details']);
                $workshopMentor = trim((string) $workshop['workshop_mentor_nom']);
                if ($workshopMentor === '') {
                    $workshopMentor = trim((string) $f['formateur']);
                }

                $workshopDate = workshopDateLabel(isset($workshop['workshop_date_atelier']) ? $workshop['workshop_date_atelier'] : '');

                $workshopPrice = 'Prix à confirmer';
                if (isset($workshop['workshop_prix']) && is_numeric($workshop['workshop_prix'])) {
                  $workshopPrice = formatPrix($workshop['workshop_prix']) . ' TND';
                }

                $workshopCertification = 'Sans certification';
                if (isset($workshop['workshop_certification']) && certifOui($workshop['workshop_certification'])) {
                  $workshopCertification = 'Avec certification';
                }

                $workshopStatus = 'Statut non défini';
                if (isset($workshop['workshop_statut'])) {
                  $statusRaw = trim((string) $workshop['workshop_statut']);
                  if ($statusRaw !== '') {
                    $workshopStatus = $statusRaw;
                  }
                }

                $workshopMeetLink = trim((string) $workshop['workshop_meet_link']);
                if ($workshopMeetLink === '' && $formationMeetIsValid) {
                    $workshopMeetLink = $meetLink;
                }
                $workshopMeetValid = filter_var($workshopMeetLink, FILTER_VALIDATE_URL) !== false;
              ?>
              <article class="associated-workshop-item">
                <h4><?php echo e($workshopTitle); ?></h4>
                <p><?php echo e($workshopDescription); ?></p>

                <div class="workshop-meta">
                  <span>👤 <?php echo e($workshopMentor); ?></span>
                  <span>⏱️ <?php echo e(workshopDurationLabel($workshop['duree'])); ?></span>
                  <span>📅 <?php echo e($workshopDate); ?></span>
                  <span>📍 <?php echo e((string) $workshop['lieu']); ?></span>
                  <span>💰 <?php echo e($workshopPrice); ?></span>
                  <span>📜 <?php echo e($workshopCertification); ?></span>
                  <span>📌 <?php echo e($workshopStatus); ?></span>
                  <span>🪑 <?php echo e(workshopSeatsLabel($workshop['places_restantes'], $workshop['places_max'])); ?></span>
                </div>

                <div class="workshop-program">
                  <strong>Programme détaillé</strong>
                  <?php if ($workshopProgram !== ''): ?>
                    <p><?php echo nl2br(e($workshopProgram)); ?></p>
                  <?php else: ?>
                    <p>Aucun programme détaillé n'est encore saisi pour cet atelier.</p>
                  <?php endif; ?>
                </div>

                <div class="workshop-actions">
                  <?php if ($workshopMeetValid): ?>
                    <a href="<?php echo e($workshopMeetLink); ?>" target="_blank" rel="noopener noreferrer" class="btn-meet">Rencontrer le formateur</a>
                  <?php else: ?>
                    <span class="btn-meet disabled">Lien de rencontre indisponible</span>
                  <?php endif; ?>
                </div>
              </article>
            <?php endfor; ?>
          <?php endif; ?>
        </div>
      </section>
    </article>
  <?php endfor; ?>
</div>

<div class="modal-overlay<?php echo ($openFormModal ? ' open' : ''); ?>" id="modalAjoutFront" onclick="fermerFormFrontSiExterieur(event)">
  <div class="modal">
    <div class="modal-header">
      <div>
        <h2>Ajouter une formation</h2>
        <p>Les données seront enregistrées dans la base</p>
      </div>
      <button class="modal-close" type="button" onclick="fermerFormAjoutFront()">✕</button>
    </div>
    <div class="modal-body">
      <form method="post" action="front.php" id="formAjoutFormationFront" novalidate>
        <input type="hidden" name="action" value="add_formation">
        <div class="form-feedback" data-form-message></div>

        <div class="front-form-row-2">
          <div class="form-group">
            <label>Titre *</label>
            <input type="text" name="titre" value="<?php echo e($old['titre']); ?>" placeholder="Ex: Initiation à la poterie" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="100"<?php echo (isset($formErrors['titre']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['titre']) ? ' show' : ''); ?>" data-field-error-for="titre"><?php echo (isset($formErrors['titre']) ? e($formErrors['titre']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Mentor *</label>
            <input type="text" name="mentor" value="<?php echo e($old['mentor']); ?>" placeholder="Ex: Fatma Ayari" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="100"<?php echo (isset($formErrors['mentor']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['mentor']) ? ' show' : ''); ?>" data-field-error-for="mentor"><?php echo (isset($formErrors['mentor']) ? e($formErrors['mentor']) : ''); ?></div>
          </div>
        </div>

        <div class="form-group">
          <label>Description</label>
          <textarea name="description" rows="3" placeholder="Description de la formation"><?php echo e($old['description']); ?></textarea>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Niveau *</label>
            <select name="niveau"<?php echo (isset($formErrors['niveau']) ? ' class="input-error"' : ''); ?>>
              <option value="debutant" <?php echo ($old['niveau'] === 'debutant' ? 'selected' : ''); ?>>Débutant</option>
              <option value="intermediaire" <?php echo ($old['niveau'] === 'intermediaire' ? 'selected' : ''); ?>>Intermédiaire</option>
              <option value="avance" <?php echo ($old['niveau'] === 'avance' ? 'selected' : ''); ?>>Avancé</option>
            </select>
            <div class="field-alert<?php echo (isset($formErrors['niveau']) ? ' show' : ''); ?>" data-field-error-for="niveau"><?php echo (isset($formErrors['niveau']) ? e($formErrors['niveau']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Durée (heures) *</label>
            <input type="text" name="duree" value="<?php echo e($old['duree']); ?>" placeholder="Ex: 24" inputmode="numeric" pattern="[0-9]+"<?php echo (isset($formErrors['duree']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['duree']) ? ' show' : ''); ?>" data-field-error-for="duree"><?php echo (isset($formErrors['duree']) ? e($formErrors['duree']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Prix (TND) *</label>
            <input type="text" name="prix" value="<?php echo e($old['prix']); ?>" placeholder="Ex: 180" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?"<?php echo (isset($formErrors['prix']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['prix']) ? ' show' : ''); ?>" data-field-error-for="prix"><?php echo (isset($formErrors['prix']) ? e($formErrors['prix']) : ''); ?></div>
          </div>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Certification</label>
            <select name="certification"<?php echo (isset($formErrors['certification']) ? ' class="input-error"' : ''); ?>>
              <option value="oui" <?php echo ($old['certification'] === 'oui' ? 'selected' : ''); ?>>Oui</option>
              <option value="non" <?php echo ($old['certification'] === 'non' ? 'selected' : ''); ?>>Non</option>
            </select>
            <div class="field-alert<?php echo (isset($formErrors['certification']) ? ' show' : ''); ?>" data-field-error-for="certification"><?php echo (isset($formErrors['certification']) ? e($formErrors['certification']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Statut</label>
            <select name="etat"<?php echo (isset($formErrors['etat']) ? ' class="input-error"' : ''); ?>>
              <option value="Actif" <?php echo ($old['etat'] === 'Actif' ? 'selected' : ''); ?>>Actif</option>
              <option value="Inactif" <?php echo ($old['etat'] === 'Inactif' ? 'selected' : ''); ?>>Inactif</option>
              <option value="Brouillon" <?php echo ($old['etat'] === 'Brouillon' ? 'selected' : ''); ?>>Brouillon</option>
            </select>
            <div class="field-alert<?php echo (isset($formErrors['etat']) ? ' show' : ''); ?>" data-field-error-for="etat"><?php echo (isset($formErrors['etat']) ? e($formErrors['etat']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Date de réalisation</label>
            <input type="date" name="date_realisation" value="<?php echo e($old['date_realisation']); ?>"<?php echo (isset($formErrors['date_realisation']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['date_realisation']) ? ' show' : ''); ?>" data-field-error-for="date_realisation"><?php echo (isset($formErrors['date_realisation']) ? e($formErrors['date_realisation']) : ''); ?></div>
          </div>
        </div>

        <div class="front-form-row-2">
          <div class="form-group">
            <label>URL vidéo (YouTube/Vimeo)</label>
            <input type="url" name="video_url" value="<?php echo e($old['video_url']); ?>" placeholder="Ex: https://www.youtube.com/watch?v=..." maxlength="1024"<?php echo (isset($formErrors['video_url']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['video_url']) ? ' show' : ''); ?>" data-field-error-for="video_url"><?php echo (isset($formErrors['video_url']) ? e($formErrors['video_url']) : ''); ?></div>
          </div>
          <div class="form-group">
            <label>Lien rencontre formateur (Meet/Zoom/booking)</label>
            <input type="url" name="meet_link" value="<?php echo e($old['meet_link']); ?>" placeholder="Ex: https://calendly.com/..."<?php echo (isset($formErrors['meet_link']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['meet_link']) ? ' show' : ''); ?>" data-field-error-for="meet_link"><?php echo (isset($formErrors['meet_link']) ? e($formErrors['meet_link']) : ''); ?></div>
          </div>
        </div>

        <?php if (!$hasFormationsVideoUrl || $formationMeetColumn === null): ?>
          <p class="form-note">Note: exécutez les migrations SQL pour enregistrer video_url et booking_url/meet_link en base.</p>
        <?php endif; ?>

        <div class="form-subtitle">Workshops liés à la formation (optionnel)</div>
        <p class="form-note">Vous pouvez ajouter 0, 1 ou plusieurs workshops. Laissez les lignes vides si vous ne voulez pas en créer.</p>

        <div id="workshopBuilderRows" class="workshop-builder-wrap" data-next-workshop-index="<?php echo e((string) $nextWorkshopBuilderIndex); ?>">
          <?php for ($wb = 0; $wb < count($oldWorkshopBuilderItems); $wb++): ?>
            <?php
              $builderWorkshop = $oldWorkshopBuilderItems[$wb];

              $workshopKeyBuilder = (string) $builderWorkshop['key'];
              if ($workshopKeyBuilder === '') {
                $workshopKeyBuilder = (string) $wb;
              }

              $workshopTitreBuilder = isset($builderWorkshop['titre']) ? trim((string) $builderWorkshop['titre']) : '';
              $workshopDescriptionBuilder = isset($builderWorkshop['description']) ? trim((string) $builderWorkshop['description']) : '';
              $workshopMentorBuilder = isset($builderWorkshop['mentor_id']) ? trim((string) $builderWorkshop['mentor_id']) : '';
              $workshopDureeBuilder = isset($builderWorkshop['duree']) ? trim((string) $builderWorkshop['duree']) : '';
              $workshopDateBuilder = isset($builderWorkshop['date_atelier']) ? trim((string) $builderWorkshop['date_atelier']) : '';
              $workshopLieuBuilder = isset($builderWorkshop['lieu']) ? trim((string) $builderWorkshop['lieu']) : '';
              $workshopPlacesBuilder = isset($builderWorkshop['places_max']) ? trim((string) $builderWorkshop['places_max']) : '';
              $workshopPrixBuilder = isset($builderWorkshop['prix']) ? trim((string) $builderWorkshop['prix']) : '';

              $workshopCertificationBuilder = 'non';
              if (isset($builderWorkshop['certification']) && trim((string) $builderWorkshop['certification']) === 'oui') {
                $workshopCertificationBuilder = 'oui';
              }

              $workshopStatutBuilder = 'a_venir';
              if (isset($builderWorkshop['statut'])) {
                $workshopStatutRaw = trim((string) $builderWorkshop['statut']);
                if ($workshopStatutRaw === 'en_cours' || $workshopStatutRaw === 'termine' || $workshopStatutRaw === 'annule') {
                  $workshopStatutBuilder = $workshopStatutRaw;
                }
              }
            ?>
            <div class="workshop-builder-item" data-workshop-index="<?php echo e($workshopKeyBuilder); ?>">
              <div class="workshop-builder-head">
                <strong>Workshop <span class="workshop-builder-number"><?php echo e((string) ($wb + 1)); ?></span></strong>
                <button type="button" class="btn-cancel quiz-remove-btn" onclick="removeWorkshopBuilderRow(this)">Supprimer</button>
              </div>

              <div class="front-form-row-2">
                <div class="form-group">
                  <label>Titre workshop</label>
                  <input type="text" name="workshop_titre[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopTitreBuilder); ?>" placeholder="Ex: Atelier pratique de poterie" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="200">
                </div>
                <div class="form-group">
                  <label>Mentor ID (optionnel)</label>
                  <input type="number" min="1" step="1" name="workshop_mentor_id[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopMentorBuilder); ?>" placeholder="Ex: 3">
                </div>
              </div>

              <div class="form-group">
                <label>Description workshop</label>
                <textarea name="workshop_description[<?php echo e($workshopKeyBuilder); ?>]" rows="2" placeholder="Description du workshop"><?php echo e($workshopDescriptionBuilder); ?></textarea>
              </div>

              <div class="front-form-row-3">
                <div class="form-group">
                  <label>Durée workshop (heures)</label>
                  <input type="number" min="1" step="1" name="workshop_duree[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopDureeBuilder); ?>" placeholder="Ex: 2">
                </div>
                <div class="form-group">
                  <label>Date atelier (optionnel)</label>
                  <input type="datetime-local" name="workshop_date_atelier[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopDateBuilder); ?>">
                </div>
                <div class="form-group">
                  <label>Lieu workshop</label>
                  <input type="text" name="workshop_lieu[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopLieuBuilder); ?>" placeholder="Ex: Tunis" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="255">
                </div>
              </div>

              <div class="front-form-row-3">
                <div class="form-group">
                  <label>Places max workshop</label>
                  <input type="number" min="1" step="1" name="workshop_places_max[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopPlacesBuilder); ?>" placeholder="Ex: 20">
                </div>
                <div class="form-group">
                  <label>Prix workshop (TND)</label>
                  <input type="number" min="0" step="0.01" name="workshop_prix[<?php echo e($workshopKeyBuilder); ?>]" value="<?php echo e($workshopPrixBuilder); ?>" placeholder="Ex: 120">
                </div>
                <div class="form-group">
                  <label>Certification workshop</label>
                  <select name="workshop_certification[<?php echo e($workshopKeyBuilder); ?>]">
                    <option value="oui" <?php echo ($workshopCertificationBuilder === 'oui' ? 'selected' : ''); ?>>Oui</option>
                    <option value="non" <?php echo ($workshopCertificationBuilder === 'non' ? 'selected' : ''); ?>>Non</option>
                  </select>
                </div>
              </div>

              <div class="front-form-row-2">
                <div class="form-group">
                  <label>Statut workshop</label>
                  <select name="workshop_statut[<?php echo e($workshopKeyBuilder); ?>]">
                    <option value="a_venir" <?php echo ($workshopStatutBuilder === 'a_venir' ? 'selected' : ''); ?>>A venir</option>
                    <option value="en_cours" <?php echo ($workshopStatutBuilder === 'en_cours' ? 'selected' : ''); ?>>En cours</option>
                    <option value="termine" <?php echo ($workshopStatutBuilder === 'termine' ? 'selected' : ''); ?>>Termine</option>
                    <option value="annule" <?php echo ($workshopStatutBuilder === 'annule' ? 'selected' : ''); ?>>Annule</option>
                  </select>
                </div>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <div class="workshop-builder-actions">
          <button type="button" class="btn-inscrit vert" onclick="addWorkshopBuilderRow()">+ Ajouter un workshop</button>
        </div>

        <div class="form-subtitle">Quiz obligatoire de la formation</div>

        <div class="form-group">
          <label>Titre du quiz *</label>
          <input type="text" name="quiz_titre" value="<?php echo e($old['quiz_titre']); ?>" placeholder="Ex: Quiz final - Initiation à la poterie" maxlength="255" required<?php echo (isset($formErrors['quiz_titre']) ? ' class="input-error"' : ''); ?>>
          <div class="field-alert<?php echo (isset($formErrors['quiz_titre']) ? ' show' : ''); ?>" data-field-error-for="quiz_titre"><?php echo (isset($formErrors['quiz_titre']) ? e($formErrors['quiz_titre']) : ''); ?></div>
        </div>

        <div class="form-group">
          <label>Description du quiz</label>
          <textarea name="quiz_description" rows="3" placeholder="Description du quiz (optionnel)"><?php echo e($old['quiz_description']); ?></textarea>
        </div>

        <div class="front-form-row-3">
          <div class="form-group">
            <label>Durée du quiz (minutes) *</label>
            <input type="number" min="1" step="1" name="quiz_duree_minutes" value="<?php echo e($old['quiz_duree_minutes']); ?>" required<?php echo (isset($formErrors['quiz_duree_minutes']) ? ' class="input-error"' : ''); ?>>
            <div class="field-alert<?php echo (isset($formErrors['quiz_duree_minutes']) ? ' show' : ''); ?>" data-field-error-for="quiz_duree_minutes"><?php echo (isset($formErrors['quiz_duree_minutes']) ? e($formErrors['quiz_duree_minutes']) : ''); ?></div>
          </div>
        </div>

        <div class="form-subtitle">Questions du quiz</div>
        <p class="form-note">Ajoutez le texte de la question, choisissez le type, puis ajoutez autant de réponses que nécessaire.</p>

        <div id="quizBuilderQuestions" class="quiz-builder-wrap" data-next-question-index="<?php echo e((string) $nextQuizBuilderQuestionIndex); ?>">
          <?php for ($qb = 0; $qb < count($oldQuizBuilderQuestions); $qb++): ?>
            <?php
              $builderQuestion = $oldQuizBuilderQuestions[$qb];

              $questionKeyBuilder = (string) $builderQuestion['key'];
              if ($questionKeyBuilder === '') {
                $questionKeyBuilder = (string) $qb;
              }

              $questionTextBuilder = trim((string) $builderQuestion['text']);

              $questionTypeBuilder = trim((string) $builderQuestion['type']);
              if ($questionTypeBuilder !== 'choix_unique' && $questionTypeBuilder !== 'choix_multiple' && $questionTypeBuilder !== 'vrai_faux') {
                $questionTypeBuilder = 'choix_unique';
              }

              $questionPointsBuilder = 1;
              if (isset($builderQuestion['points']) && is_numeric($builderQuestion['points']) && (int) $builderQuestion['points'] > 0) {
                $questionPointsBuilder = (int) $builderQuestion['points'];
              }

              $tfCorrectBuilder = 'true';
              if (isset($builderQuestion['tf_correct']) && trim((string) $builderQuestion['tf_correct']) === 'false') {
                $tfCorrectBuilder = 'false';
              }

              $builderAnswers = [];
              if (isset($builderQuestion['answers']) && is_array($builderQuestion['answers'])) {
                $builderAnswers = $builderQuestion['answers'];
              }

              if ($questionTypeBuilder !== 'vrai_faux' && count($builderAnswers) === 0) {
                $builderAnswers[] = ['key' => '0', 'text' => '', 'is_correct' => false];
                $builderAnswers[] = ['key' => '1', 'text' => '', 'is_correct' => false];
              }

              $nextAnswerIndexBuilder = 0;
              for ($qa = 0; $qa < count($builderAnswers); $qa++) {
                $answerKeyCandidate = '';
                if (isset($builderAnswers[$qa]['key'])) {
                  $answerKeyCandidate = (string) $builderAnswers[$qa]['key'];
                }

                if (ctype_digit($answerKeyCandidate)) {
                  $candidate = (int) $answerKeyCandidate + 1;
                  if ($candidate > $nextAnswerIndexBuilder) {
                    $nextAnswerIndexBuilder = $candidate;
                  }
                }
              }

              if ($nextAnswerIndexBuilder <= 0) {
                $nextAnswerIndexBuilder = count($builderAnswers);
              }

              if ($nextAnswerIndexBuilder < 2) {
                $nextAnswerIndexBuilder = 2;
              }
            ?>
            <div class="quiz-builder-question" data-question-index="<?php echo e($questionKeyBuilder); ?>" data-next-answer-index="<?php echo e((string) $nextAnswerIndexBuilder); ?>">
              <div class="quiz-builder-head">
                <strong>Question <span class="quiz-builder-number"><?php echo e((string) ($qb + 1)); ?></span></strong>
                <button type="button" class="btn-cancel quiz-remove-btn" onclick="removeQuizBuilderQuestion(this)">Supprimer</button>
              </div>

              <div class="form-group">
                <label>Texte de la question *</label>
                <input type="text" name="quiz_question_text[<?php echo e($questionKeyBuilder); ?>]" value="<?php echo e($questionTextBuilder); ?>" placeholder="Ex: Quelle matière est utilisée en vannerie ?">
              </div>

              <div class="front-form-row-3">
                <div class="form-group">
                  <label>Type</label>
                  <select class="quiz-builder-type-select" data-question-index="<?php echo e($questionKeyBuilder); ?>" name="quiz_question_type[<?php echo e($questionKeyBuilder); ?>]" onchange="onQuizBuilderTypeChange(this, '<?php echo e($questionKeyBuilder); ?>')">
                    <option value="choix_unique" <?php echo ($questionTypeBuilder === 'choix_unique' ? 'selected' : ''); ?>>Choix unique</option>
                    <option value="choix_multiple" <?php echo ($questionTypeBuilder === 'choix_multiple' ? 'selected' : ''); ?>>Choix multiple</option>
                    <option value="vrai_faux" <?php echo ($questionTypeBuilder === 'vrai_faux' ? 'selected' : ''); ?>>Vrai / Faux</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Points</label>
                  <input type="number" min="1" step="1" name="quiz_question_points[<?php echo e($questionKeyBuilder); ?>]" value="<?php echo e((string) $questionPointsBuilder); ?>">
                </div>
                <div class="form-group <?php echo ($questionTypeBuilder === 'vrai_faux' ? '' : 'quiz-builder-hidden'); ?>" id="quizTrueFalseBox<?php echo e($questionKeyBuilder); ?>">
                  <label>Bonne réponse (Vrai/Faux)</label>
                  <select name="quiz_tf_correct[<?php echo e($questionKeyBuilder); ?>]">
                    <option value="true" <?php echo ($tfCorrectBuilder === 'true' ? 'selected' : ''); ?>>True</option>
                    <option value="false" <?php echo ($tfCorrectBuilder === 'false' ? 'selected' : ''); ?>>False</option>
                  </select>
                </div>
              </div>

              <div class="form-group <?php echo ($questionTypeBuilder === 'vrai_faux' ? 'quiz-builder-hidden' : ''); ?>" id="quizAnswersBox<?php echo e($questionKeyBuilder); ?>">
                <label>Réponses *</label>
                <div class="quiz-builder-answer-list" id="quizAnswerList<?php echo e($questionKeyBuilder); ?>">
                  <?php for ($qa = 0; $qa < count($builderAnswers); $qa++): ?>
                    <?php
                      $builderAnswer = $builderAnswers[$qa];
                      $answerKeyBuilder = '';
                      if (isset($builderAnswer['key'])) {
                        $answerKeyBuilder = (string) $builderAnswer['key'];
                      }
                      if ($answerKeyBuilder === '') {
                        $answerKeyBuilder = (string) $qa;
                      }

                      $answerTextBuilder = '';
                      if (isset($builderAnswer['text'])) {
                        $answerTextBuilder = trim((string) $builderAnswer['text']);
                      }

                      $answerIsCorrectBuilder = false;
                      if (isset($builderAnswer['is_correct']) && $builderAnswer['is_correct']) {
                        $answerIsCorrectBuilder = true;
                      }
                    ?>
                    <div class="quiz-builder-answer-row">
                      <input type="text" name="quiz_answer_text[<?php echo e($questionKeyBuilder); ?>][<?php echo e($answerKeyBuilder); ?>]" value="<?php echo e($answerTextBuilder); ?>" placeholder="Texte de la réponse">
                      <label>
                        <input type="checkbox" name="quiz_answer_correct[<?php echo e($questionKeyBuilder); ?>][]" value="<?php echo e($answerKeyBuilder); ?>" <?php echo ($answerIsCorrectBuilder ? 'checked' : ''); ?>>
                        Bonne réponse
                      </label>
                    </div>
                  <?php endfor; ?>
                </div>
                <button type="button" class="btn-inscrit vert" onclick="addQuizBuilderAnswer('<?php echo e($questionKeyBuilder); ?>')">+ Ajouter une réponse</button>
              </div>
            </div>
          <?php endfor; ?>
        </div>

        <div class="quiz-builder-actions">
          <button type="button" class="btn-inscrit vert" onclick="addQuizBuilderQuestion()">+ Ajouter une question</button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="fermerFormAjoutFront()">Annuler</button>
          <button type="submit" class="btn-save">Ajouter</button>
        </div>
      </form>
    </div>
  </div>
</div>

<footer>
  <strong>ح CraftLink Tunisie</strong> · 
</footer>

<script>
// ============================================================
// Formation Explanation with Browser Text-to-Speech (DARIJA ONLY!)
// ============================================================

let currentUtterance = null;
let currentSpeakingButton = null;

async function explainFormationInDarija(buttonElement) {
    const formationId = buttonElement.getAttribute('data-formation-id');
    const formationTitle = buttonElement.getAttribute('data-formation-title');
    const formationDescription = buttonElement.getAttribute('data-formation-description');
    const formationNiveau = buttonElement.getAttribute('data-formation-niveau') || '';
    const formationDuree = buttonElement.getAttribute('data-formation-duree') || '';
    const formationPrix = buttonElement.getAttribute('data-formation-prix') || '';
    const formationCertif = buttonElement.getAttribute('data-formation-certif') || '';
    
    // Disable button while processing
    buttonElement.disabled = true;
    buttonElement.textContent = '⏳ تحضير...';
    
    const detailCard = buttonElement.closest('.formation-detail-card');
    
    // Remove existing explanation card if any
    const existingCard = detailCard.querySelector('.darija-explanation-card');
    if (existingCard) existingCard.remove();
    
    // Create explanation card
    const explanationCard = document.createElement('div');
    explanationCard.className = 'darija-explanation-card';
    explanationCard.innerHTML = `
        <div class="darija-loading">
            <span>🎙️</span> جاري تحضير الشرح بالدارجة التونسية...
        </div>
    `;
    
    buttonElement.parentNode.insertAdjacentElement('afterend', explanationCard);
    
    try {
        const formData = new FormData();
        formData.append('action', 'explain_formation');
        // Use title as fallback when description is empty (newly added formations)
        formData.append('formation_text', (formationDescription && formationDescription.trim() !== '') ? formationDescription : formationTitle);
        formData.append('formation_title', formationTitle);
        formData.append('formation_niveau', formationNiveau);
        formData.append('formation_duree', formationDuree);
        formData.append('formation_prix', formationPrix);
        formData.append('formation_certif', formationCertif);
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.error) {
            explanationCard.innerHTML = `<div class="darija-explanation-text" style="color:#c0392b;">⚠️ ${data.error}</div>`;
            setTimeout(() => { explanationCard.remove(); }, 4000);
            return;
        }
        
        const darijaText = data.darija_text;
        
        // Update card with text and audio controls
        // IMPORTANT: text is stored in data-text attribute to avoid
        // single-quote truncation when Arabic text contains apostrophes.
        explanationCard.innerHTML = `
            <div class="darija-explanation-text">📢 ${escapeHtml(darijaText)}</div>
            <div class="darija-audio-controls">
                <button class="darija-play-btn" data-text="${escapeHtmlForAttr(darijaText)}" onclick="speakDarijaText(this)">
                    🔊 استمع بالدارجة
                </button>
                <button class="darija-stop-btn" onclick="stopDarijaSpeech()">
                    ⏹️ إيقاف
                </button>
            </div>
            <div class="darija-note">🎧 النص والقراءة بالدارجة التونسية</div>
        `;
        
    } catch (error) {
        console.error('Error:', error);
        explanationCard.innerHTML = `<div class="darija-explanation-text" style="color:#c0392b;">⚠️ خطأ في الاتصال. حاول مرة أخرى.</div>`;
        setTimeout(() => { explanationCard.remove(); }, 4000);
    } finally {
        buttonElement.disabled = false;
        buttonElement.textContent = '🔊 شرح بالدارجة';
    }
}

// ============================================================
// speakDarijaText — TTS for Arabic/Darija
// Calls a PHP proxy on your own server which fetches the audio
// from Google Translate TTS and streams it back — no CORS issues.
// ============================================================
function speakDarijaText(buttonElement, text) {
    // Read text from data-text attribute when not passed directly.
    if (!text) {
        text = buttonElement.getAttribute('data-text') || '';
    }
    if (!text) { return; }

    // Stop anything already playing
    if (currentUtterance) {
        window.speechSynthesis.cancel();
        currentUtterance = null;
    }
    var existingPlayer = document.getElementById('darija-tts-player');
    if (existingPlayer) {
        existingPlayer.pause();
        existingPlayer.src = '';
    }

    // Reset any previously active button
    if (currentSpeakingButton && currentSpeakingButton !== buttonElement) {
        currentSpeakingButton.innerHTML = '🔊 استمع بالدارجة';
        currentSpeakingButton.disabled = false;
        currentSpeakingButton.classList.remove('playing');
    }

    var originalHTML = buttonElement.innerHTML;
    buttonElement.innerHTML = '⏳ تشغيل...';
    buttonElement.disabled = true;
    currentSpeakingButton = buttonElement;

    // Split into ≤180-char chunks on sentence boundaries
    function chunkText(str, maxLen) {
        var chunks = [];
        var sentences = str.split(/([.!?،؟\n]+\s*)/);
        var current = '';
        for (var i = 0; i < sentences.length; i++) {
            var s = sentences[i];
            if ((current + s).length <= maxLen) {
                current += s;
            } else {
                if (current.trim()) { chunks.push(current.trim()); }
                while (s.length > maxLen) {
                    chunks.push(s.slice(0, maxLen));
                    s = s.slice(maxLen);
                }
                current = s;
            }
        }
        if (current.trim()) { chunks.push(current.trim()); }
        return chunks.filter(function(c) { return c.trim() !== ''; });
    }

    var chunks = chunkText(text, 180);

    var player = document.getElementById('darija-tts-player');
    if (!player) {
        player = document.createElement('audio');
        player.id = 'darija-tts-player';
        player.style.display = 'none';
        document.body.appendChild(player);
    }

    function resetButton() {
        buttonElement.innerHTML = originalHTML;
        buttonElement.disabled = false;
        buttonElement.classList.remove('playing');
        currentSpeakingButton = null;
    }

    function playChunk(index) {
        if (index >= chunks.length) {
            resetButton();
            return;
        }

        // Point to your PHP proxy — same page, action=tts_proxy
        var proxyUrl = window.location.pathname
            + '?action=tts_proxy&tl=ar&q=' + encodeURIComponent(chunks[index]);

        player.src = proxyUrl;

        player.oncanplay = null;
        player.onplay = function() {
            buttonElement.innerHTML = '🔊 جاري القراءة...';
            buttonElement.classList.add('playing');
        };
        player.onended = function() {
            playChunk(index + 1);
        };
        player.onerror = function(e) {
            console.error('TTS proxy error on chunk ' + index, e);
            resetButton();
        };

        player.load();
        player.play().catch(function(err) {
            console.error('play() rejected:', err);
            resetButton();
        });
    }

    playChunk(0);
}

// Also add a function to check and list available voices (for debugging)
function listAvailableVoices() {
    var voices = window.speechSynthesis.getVoices();
    console.log('=== Available Voices for Darija ===');
    var arabicFound = false;
    for (var i = 0; i < voices.length; i++) {
        if (voices[i].lang === 'ar' || voices[i].lang.startsWith('ar-')) {
            console.log('Arabic voice:', voices[i].name, voices[i].lang);
            arabicFound = true;
        }
    }
    if (!arabicFound) {
        console.warn('No Arabic voices found! Your browser may not support Arabic TTS.');
        console.log('Available languages:', voices.map(v => v.lang).join(', '));
    }
}

// Initialize voices and log them for debugging
window.addEventListener('load', function() {
    // Small delay to ensure voices are loaded
    setTimeout(function() {
        listAvailableVoices();
    }, 500);
    
    // Also trigger voice loading
    window.speechSynthesis.getVoices();
});

function stopDarijaSpeech() {
    // Stop Web Speech API
    if (currentUtterance) {
        window.speechSynthesis.cancel();
        currentUtterance = null;
    }

    // Stop Google TTS audio player
    var player = document.getElementById('darija-tts-player');
    if (player) {
        player.pause();
        player.src = '';
    }

    // Reset any playing buttons
    document.querySelectorAll('.darija-play-btn').forEach(function(btn) {
        btn.innerHTML = '🔊 استمع بالدارجة';
        btn.disabled = false;
        btn.classList.remove('playing');
    });

    currentSpeakingButton = null;
}

// ============================================================
// ENGLISH VOICE — Complete rewrite with better error handling
// ============================================================
let currentEnglishSpeakingButton = null;
let currentEnglishAudio = null;

async function explainFormationInEnglish(buttonElement) {
    const formationTitle       = buttonElement.getAttribute('data-formation-title');
    const formationDescription = buttonElement.getAttribute('data-formation-description');
    const formationNiveau      = buttonElement.getAttribute('data-formation-niveau') || '';
    const formationDuree       = buttonElement.getAttribute('data-formation-duree')  || '';
    const formationPrix        = buttonElement.getAttribute('data-formation-prix')   || '';
    const formationCertif      = buttonElement.getAttribute('data-formation-certif') || '';

    // Disable button and show loading state
    const originalText = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '⏳ Loading...';

    const detailCard = buttonElement.closest('.formation-detail-card');
    if (!detailCard) return;

    // Remove any existing English explanation card
    const existingCard = detailCard.querySelector('.english-explanation-card');
    if (existingCard) existingCard.remove();

    // Create explanation card with loading indicator
    const explanationCard = document.createElement('div');
    explanationCard.className = 'english-explanation-card';
    explanationCard.innerHTML = `
        <div class="english-loading">
            <span>🎙️</span> Generating English explanation...
        </div>
    `;
    buttonElement.parentNode.insertAdjacentElement('afterend', explanationCard);

    try {
        const formData = new FormData();
        formData.append('action',           'explain_formation_en');
        // Use title as fallback when description is empty (newly added formations)
        formData.append('formation_text',   (formationDescription && formationDescription.trim() !== '') ? formationDescription : formationTitle);
        formData.append('formation_title',  formationTitle);
        formData.append('formation_niveau', formationNiveau);
        formData.append('formation_duree',  formationDuree);
        formData.append('formation_prix',   formationPrix);
        formData.append('formation_certif', formationCertif);

        const response = await fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();

        if (data.error) {
            explanationCard.innerHTML = `<div class="english-explanation-text" style="color:#c0392b;">⚠️ Error: ${escapeHtml(data.error)}</div>`;
            setTimeout(() => { explanationCard.remove(); }, 5000);
            return;
        }

        const englishText = data.english_text || 'No explanation generated. Please try again.';

        // Build the explanation card with audio buttons
        explanationCard.innerHTML = `
            <div class="english-explanation-text">📢 ${escapeHtml(englishText)}</div>
            <div class="english-audio-controls">
                <button class="english-play-btn" data-text="${escapeHtmlForAttr(englishText)}" onclick="speakEnglishTextViaProxy(this)">
                    🔊 Listen in English
                </button>
                <button class="english-stop-btn" onclick="stopEnglishAudio()">
                    ⏹️ Stop
                </button>
            </div>
            <div class="english-note">🎧 Click the play button to hear this explanation in English</div>
        `;

    } catch (error) {
        console.error('English explanation error:', error);
        explanationCard.innerHTML = `<div class="english-explanation-text" style="color:#c0392b;">⚠️ Connection error. Please refresh and try again.</div>`;
        setTimeout(() => { explanationCard.remove(); }, 5000);
    } finally {
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalText;
    }
}

// ============================================================
// Speak English text via PHP proxy (handles CORS)
// ============================================================
function speakEnglishTextViaProxy(buttonElement) {
    const text = buttonElement.getAttribute('data-text') || '';
    if (!text) {
        console.error('No text to speak');
        return;
    }

    // Stop any currently playing audio
    stopEnglishAudio();

    const originalHTML = buttonElement.innerHTML;
    buttonElement.innerHTML = '⏳ Loading audio...';
    buttonElement.disabled = true;
    currentEnglishSpeakingButton = buttonElement;

    // Split text into smaller chunks for better TTS handling
    const chunks = splitTextIntoChunks(text, 180);
    
    let currentChunk = 0;
    let audioPlayer = document.getElementById('english-tts-player');
    
    if (!audioPlayer) {
        audioPlayer = document.createElement('audio');
        audioPlayer.id = 'english-tts-player';
        audioPlayer.style.display = 'none';
        document.body.appendChild(audioPlayer);
    }

    function playNextChunk() {
        if (currentChunk >= chunks.length) {
            // All chunks played
            resetEnglishButton();
            return;
        }

        const chunkText = chunks[currentChunk];
        const proxyUrl = window.location.pathname + '?action=tts_proxy_en&tl=en&q=' + encodeURIComponent(chunkText);
        
        audioPlayer.src = proxyUrl;
        
        audioPlayer.oncanplay = function() {
            buttonElement.innerHTML = '🔊 Playing... ' + (currentChunk + 1) + '/' + chunks.length;
            buttonElement.classList.add('playing');
        };
        
        audioPlayer.onended = function() {
            currentChunk++;
            playNextChunk();
        };
        
        audioPlayer.onerror = function(e) {
            console.error('TTS proxy error on chunk', currentChunk, e);
            resetEnglishButton();
            alert('Audio playback error. Please try again.');
        };
        
        audioPlayer.load();
        audioPlayer.play().catch(function(err) {
            console.error('Playback failed:', err);
            resetEnglishButton();
            alert('Could not play audio. Your browser may not support this feature.');
        });
    }

    function resetEnglishButton() {
        if (currentEnglishSpeakingButton) {
            currentEnglishSpeakingButton.innerHTML = originalHTML;
            currentEnglishSpeakingButton.disabled = false;
            currentEnglishSpeakingButton.classList.remove('playing');
            currentEnglishSpeakingButton = null;
        }
    }

    playNextChunk();
}

// Helper: Split text into chunks at sentence boundaries
function splitTextIntoChunks(text, maxLength) {
    const chunks = [];
    const sentences = text.split(/([.!?]\s+)/);
    let currentChunk = '';
    
    for (let i = 0; i < sentences.length; i++) {
        const sentence = sentences[i];
        if ((currentChunk + sentence).length <= maxLength) {
            currentChunk += sentence;
        } else {
            if (currentChunk.trim()) {
                chunks.push(currentChunk.trim());
            }
            if (sentence.length > maxLength) {
                // Split long sentence further
                for (let j = 0; j < sentence.length; j += maxLength) {
                    chunks.push(sentence.substring(j, j + maxLength));
                }
                currentChunk = '';
            } else {
                currentChunk = sentence;
            }
        }
    }
    
    if (currentChunk.trim()) {
        chunks.push(currentChunk.trim());
    }
    
    return chunks.filter(c => c.trim() !== '');
}

function stopEnglishAudio() {
    const player = document.getElementById('english-tts-player');
    if (player) {
        player.pause();
        player.src = '';
    }
    
    // Reset any active play button
    if (currentEnglishSpeakingButton) {
        currentEnglishSpeakingButton.innerHTML = '🔊 Listen in English';
        currentEnglishSpeakingButton.disabled = false;
        currentEnglishSpeakingButton.classList.remove('playing');
        currentEnglishSpeakingButton = null;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escapeHtmlForAttr(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/\n/g, ' ')
        .replace(/\r/g, '');
}



// Initialize voices when page loads
window.addEventListener('load', function() {
    window.speechSynthesis.getVoices();
    console.log('🎙️ Darija Text-to-Speech ready');
});

// Opens the related modal or panel.
function ouvrirFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.add('open');
  }
}

// Closes the related modal or panel.
function fermerFormAjoutFront()
{
  var modal = document.getElementById('modalAjoutFront');
  if (modal) {
    modal.classList.remove('open');
  }
}

// Closes the related modal or panel.
function fermerFormFrontSiExterieur(event)
{
  if (event.target && event.target.id === 'modalAjoutFront') {
    fermerFormAjoutFront();
  }
}

// Returns true when the provided value exists in the allowed list.
function isAllowedOption(value, allowedValues)
{
  for (var i = 0; i < allowedValues.length; i++) {
    if (value === allowedValues[i]) {
      return true;
    }
  }
  return false;
}

// Validates optional URL fields (http/https only).
function isValidHttpUrl(value)
{
  if (value === '') {
    return true;
  }
  try {
    var parsed = new URL(value);
    return parsed.protocol === 'http:' || parsed.protocol === 'https:';
  } catch (error) {
    return false;
  }
}

// Returns or creates the message container used by a form.
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
  if (formElement.classList.contains('search-bar') || formElement.classList.contains('filter-form')) {
    formElement.insertAdjacentElement('afterend', messageNode);
  } else {
    formElement.insertBefore(messageNode, formElement.firstChild);
  }
  return messageNode;
}

// Clears the currently visible message for a given form.
function clearFormMessage(formElement)
{
  var messageNode = ensureFormMessageNode(formElement);
  messageNode.textContent = '';
  messageNode.classList.remove('show', 'error', 'success', 'info');
}

// Shows a message for a given form.
function showFormMessage(formElement, status, message)
{
  var messageNode = ensureFormMessageNode(formElement);
  messageNode.textContent = message;
  messageNode.classList.remove('error', 'success', 'info');
  messageNode.classList.add('show');
  if (status === 'success' || status === 'info') {
    messageNode.classList.add(status);
  } else {
    messageNode.classList.add('error');
  }
}

// Removes dynamic field alerts generated during client-side validation.
function clearDynamicFieldAlerts(formElement)
{
  var dynamicAlerts = formElement.querySelectorAll('.dynamic-field-alert');
  for (var i = 0; i < dynamicAlerts.length; i++) {
    dynamicAlerts[i].remove();
  }
}

// Adds a dynamic inline error under a field that has no static error placeholder.
function setDynamicFieldError(fieldElement, message)
{
  if (!fieldElement) {
    return;
  }
  fieldElement.classList.add('input-error');
  var alertNode = null;
  var group = fieldElement.closest('.form-group');
  if (group) {
    alertNode = group.querySelector('.field-alert.dynamic-field-alert');
    if (!alertNode) {
      alertNode = document.createElement('div');
      alertNode.className = 'field-alert dynamic-field-alert';
      group.appendChild(alertNode);
    }
  } else {
    var nextNode = fieldElement.nextElementSibling;
    if (nextNode && nextNode.classList.contains('field-alert') && nextNode.classList.contains('dynamic-field-alert')) {
      alertNode = nextNode;
    } else {
      alertNode = document.createElement('div');
      alertNode.className = 'field-alert dynamic-field-alert';
      if (fieldElement.parentNode) {
        fieldElement.parentNode.insertBefore(alertNode, fieldElement.nextSibling);
      }
    }
  }
  if (alertNode) {
    alertNode.textContent = message;
    alertNode.classList.add('show');
  }
}

// Returns true when a YYYY-MM-DD date value is valid.
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

// Returns true when an optional datetime-local value is valid.
function isValidDateTimeValue(value)
{
  if (value === '') {
    return true;
  }
  return !isNaN(Date.parse(value));
}

// Clears existing inline field errors from the add-formation form.
function clearAddFormationFieldErrors(formElement)
{
  clearFormMessage(formElement);
  var alerts = formElement.querySelectorAll('.field-alert[data-field-error-for]');
  for (var i = 0; i < alerts.length; i++) {
    alerts[i].textContent = '';
    alerts[i].classList.remove('show');
  }
  var fields = formElement.querySelectorAll('.input-error');
  for (var j = 0; j < fields.length; j++) {
    fields[j].classList.remove('input-error');
  }
  clearDynamicFieldAlerts(formElement);
}

// Displays an inline field error message under the corresponding input.
function setAddFormationFieldError(formElement, fieldName, message)
{
  var field = formElement.querySelector('[name="' + fieldName + '"]');
  if (field) {
    field.classList.add('input-error');
  }
  var alertNode = formElement.querySelector('[data-field-error-for="' + fieldName + '"]');
  if (alertNode) {
    alertNode.textContent = message;
    alertNode.classList.add('show');
  }
}

// Validates the add-formation form and renders inline errors per field.
function validateAddFormationForm(formElement)
{
  clearAddFormationFieldErrors(formElement);
  var isValid = true;
  var firstErrorMessage = '';
  var integerPattern = /^\d+$/;
  var titleLettersPattern = /^[A-Za-zÀ-ÖØ-öø-ÿ ]+$/;
  var titleMaxLength = 100;
  var mentorMaxLength = 100;
  var quizTitleMaxLength = 255;
  var videoUrlMaxLength = 1024;
  var workshopTitleMaxLength = 200;
  var workshopLieuMaxLength = 255;

  function registerFormError(message)
  {
    isValid = false;
    if (firstErrorMessage === '') {
      firstErrorMessage = message;
    }
  }

  function registerStaticFieldError(fieldName, message)
  {
    setAddFormationFieldError(formElement, fieldName, message);
    registerFormError(message);
  }

  function registerDynamicFieldError(fieldElement, message)
  {
    setDynamicFieldError(fieldElement, message);
    registerFormError(message);
  }

  var titre = '';
  var titreField = formElement.querySelector('[name="titre"]');
  if (titreField) {
    titre = titreField.value.trim();
  }
  if (titre === '') {
    registerStaticFieldError('titre', 'Le titre est obligatoire.');
  } else if (titre.length < 3) {
    registerStaticFieldError('titre', 'Le titre doit contenir au moins 3 caracteres.');
  } else if (titre.length > titleMaxLength) {
    registerStaticFieldError('titre', 'Le titre ne doit pas depasser 100 caracteres.');
  } else if (!titleLettersPattern.test(titre)) {
    registerStaticFieldError('titre', 'Le titre doit contenir uniquement des lettres.');
  }

  var mentor = '';
  var mentorField = formElement.querySelector('[name="mentor"]');
  if (mentorField) {
    mentor = mentorField.value.trim();
  }
  if (mentor === '') {
    registerStaticFieldError('mentor', 'Le mentor est obligatoire.');
  } else if (mentor.length > mentorMaxLength) {
    registerStaticFieldError('mentor', 'Le mentor ne doit pas depasser 100 caracteres.');
  } else if (!titleLettersPattern.test(mentor)) {
    registerStaticFieldError('mentor', 'Le mentor doit contenir uniquement des lettres.');
  }

  var niveau = '';
  var niveauField = formElement.querySelector('[name="niveau"]');
  if (niveauField) {
    niveau = niveauField.value;
  }
  if (!isAllowedOption(niveau, ['debutant', 'intermediaire', 'avance'])) {
    registerStaticFieldError('niveau', 'Le niveau selectionne est invalide.');
  }

  var duree = '';
  var dureeField = formElement.querySelector('[name="duree"]');
  if (dureeField) {
    duree = dureeField.value.trim();
  }
  if (!integerPattern.test(duree) || parseInt(duree, 10) <= 0) {
    registerStaticFieldError('duree', 'La duree doit etre un nombre entier superieur a 0.');
  }

  var prix = '';
  var prixField = formElement.querySelector('[name="prix"]');
  if (prixField) {
    prix = prixField.value.trim();
  }
  var prixNormalise = prix.replace(',', '.');
  if (prixNormalise === '' || isNaN(parseFloat(prixNormalise))) {
    registerStaticFieldError('prix', 'Le prix doit etre un nombre valide.');
  } else if (parseFloat(prixNormalise) < 0) {
    registerStaticFieldError('prix', 'Le prix doit etre superieur ou egal a 0.');
  }

  var certification = '';
  var certificationField = formElement.querySelector('[name="certification"]');
  if (certificationField) {
    certification = certificationField.value;
  }
  if (!isAllowedOption(certification, ['oui', 'non'])) {
    registerStaticFieldError('certification', 'La certification selectionnee est invalide.');
  }

  var etat = '';
  var etatField = formElement.querySelector('[name="etat"]');
  if (etatField) {
    etat = etatField.value;
  }
  if (!isAllowedOption(etat, ['Actif', 'Inactif', 'Brouillon'])) {
    registerStaticFieldError('etat', 'Le statut selectionne est invalide.');
  }

  var dateRealisation = '';
  var dateField = formElement.querySelector('[name="date_realisation"]');
  if (dateField) {
    dateRealisation = dateField.value.trim();
  }
  if (dateRealisation !== '' && !isValidDateValue(dateRealisation)) {
    registerStaticFieldError('date_realisation', 'La date de realisation est invalide.');
  }

  var videoUrl = '';
  var videoField = formElement.querySelector('[name="video_url"]');
  if (videoField) {
    videoUrl = videoField.value.trim();
  }
  if (!isValidHttpUrl(videoUrl)) {
    registerStaticFieldError('video_url', 'Le lien video doit etre une URL valide.');
  } else if (videoUrl.length > videoUrlMaxLength) {
    registerStaticFieldError('video_url', 'Le lien video ne doit pas depasser 1024 caracteres.');
  }

  var meetLink = '';
  var meetField = formElement.querySelector('[name="meet_link"]');
  if (meetField) {
    meetLink = meetField.value.trim();
  }
  if (!isValidHttpUrl(meetLink)) {
    registerStaticFieldError('meet_link', 'Le lien de rencontre doit etre une URL valide.');
  }

  var quizTitre = '';
  var quizTitreField = formElement.querySelector('[name="quiz_titre"]');
  if (quizTitreField) {
    quizTitre = quizTitreField.value.trim();
  }
  if (quizTitre === '') {
    registerStaticFieldError('quiz_titre', 'Le titre du quiz est obligatoire.');
  } else if (quizTitre.length < 3) {
    registerStaticFieldError('quiz_titre', 'Le titre du quiz doit contenir au moins 3 caracteres.');
  } else if (quizTitre.length > quizTitleMaxLength) {
    registerStaticFieldError('quiz_titre', 'Le titre du quiz ne doit pas depasser 255 caracteres.');
  }

  var quizDuree = '';
  var quizDureeField = formElement.querySelector('[name="quiz_duree_minutes"]');
  if (quizDureeField) {
    quizDuree = quizDureeField.value.trim();
  }
  if (!integerPattern.test(quizDuree) || parseInt(quizDuree, 10) <= 0) {
    registerStaticFieldError('quiz_duree_minutes', 'La duree du quiz doit etre un entier superieur a 0.');
  }

  var workshopCards = formElement.querySelectorAll('.workshop-builder-item');
  for (var w = 0; w < workshopCards.length; w++) {
    var workshopCard = workshopCards[w];
    var workshopLabel = 'Workshop #' + String(w + 1);
    var workshopTitreField = workshopCard.querySelector('input[name^="workshop_titre["]');
    var workshopDescriptionField = workshopCard.querySelector('textarea[name^="workshop_description["]');
    var workshopMentorField = workshopCard.querySelector('input[name^="workshop_mentor_id["]');
    var workshopDureeField = workshopCard.querySelector('input[name^="workshop_duree["]');
    var workshopDateField = workshopCard.querySelector('input[name^="workshop_date_atelier["]');
    var workshopLieuField = workshopCard.querySelector('input[name^="workshop_lieu["]');
    var workshopPlacesField = workshopCard.querySelector('input[name^="workshop_places_max["]');
    var workshopPrixField = workshopCard.querySelector('input[name^="workshop_prix["]');
    var workshopCertificationField = workshopCard.querySelector('select[name^="workshop_certification["]');
    var workshopStatutField = workshopCard.querySelector('select[name^="workshop_statut["]');
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
    var hasWorkshopInput = (workshopTitre !== '' || workshopDescription !== '' || workshopMentor !== '' || workshopDuree !== '' || workshopDate !== '' || workshopLieu !== '' || workshopPlaces !== '' || workshopPrix !== '');
    if (!hasWorkshopInput) { continue; }
    if (workshopTitre === '') {
      registerDynamicFieldError(workshopTitreField, workshopLabel + ' : renseignez le titre ou supprimez cette ligne.');
    } else if (workshopTitre.length < 3) {
      registerDynamicFieldError(workshopTitreField, workshopLabel + ' : le titre doit contenir au moins 3 caracteres.');
    } else if (workshopTitre.length > workshopTitleMaxLength) {
      registerDynamicFieldError(workshopTitreField, workshopLabel + ' : le titre ne doit pas depasser 200 caracteres.');
    } else if (!titleLettersPattern.test(workshopTitre)) {
      registerDynamicFieldError(workshopTitreField, workshopLabel + ' : le titre doit contenir uniquement des lettres.');
    }
    if (workshopDescription === '' || workshopDescription.length < 10) {
      registerDynamicFieldError(workshopDescriptionField, workshopLabel + ' : la description doit contenir au moins 10 caracteres.');
    }
    if (workshopMentor !== '' && (!integerPattern.test(workshopMentor) || parseInt(workshopMentor, 10) <= 0)) {
      registerDynamicFieldError(workshopMentorField, workshopLabel + ' : mentor ID doit etre un entier positif.');
    }
    if (!integerPattern.test(workshopDuree) || parseInt(workshopDuree, 10) <= 0) {
      registerDynamicFieldError(workshopDureeField, workshopLabel + ' : la duree doit etre un entier superieur a 0.');
    }
    if (!isValidDateTimeValue(workshopDate)) {
      registerDynamicFieldError(workshopDateField, workshopLabel + ' : la date d\'atelier est invalide.');
    }
    if (workshopLieu === '' || workshopLieu.length < 2) {
      registerDynamicFieldError(workshopLieuField, workshopLabel + ' : le lieu est obligatoire (min. 2 caracteres).');
    } else if (workshopLieu.length > workshopLieuMaxLength) {
      registerDynamicFieldError(workshopLieuField, workshopLabel + ' : le lieu ne doit pas depasser 255 caracteres.');
    } else if (!titleLettersPattern.test(workshopLieu)) {
      registerDynamicFieldError(workshopLieuField, workshopLabel + ' : le lieu doit contenir uniquement des lettres.');
    }
    if (!integerPattern.test(workshopPlaces) || parseInt(workshopPlaces, 10) <= 0) {
      registerDynamicFieldError(workshopPlacesField, workshopLabel + ' : le nombre de places max doit etre un entier superieur a 0.');
    }
    var workshopPrixNormalise = workshopPrix.replace(',', '.');
    if (workshopPrixNormalise === '' || isNaN(parseFloat(workshopPrixNormalise))) {
      registerDynamicFieldError(workshopPrixField, workshopLabel + ' : le prix doit etre un nombre valide.');
    } else if (parseFloat(workshopPrixNormalise) < 0) {
      registerDynamicFieldError(workshopPrixField, workshopLabel + ' : le prix doit etre superieur ou egal a 0.');
    }
    if (!isAllowedOption(workshopCertification, ['oui', 'non'])) {
      registerDynamicFieldError(workshopCertificationField, workshopLabel + ' : la certification est invalide.');
    }
    if (!isAllowedOption(workshopStatut, ['a_venir', 'en_cours', 'termine', 'annule'])) {
      registerDynamicFieldError(workshopStatutField, workshopLabel + ' : le statut est invalide.');
    }
  }

  var questionCards = formElement.querySelectorAll('.quiz-builder-question');
  if (questionCards.length === 0) {
    registerFormError('Ajoutez au moins une question au quiz.');
  }
  for (var q = 0; q < questionCards.length; q++) {
    var questionCard = questionCards[q];
    var questionLabel = 'Question #' + String(q + 1);
    var questionTextField = questionCard.querySelector('input[name^="quiz_question_text["]');
    var questionTypeField = questionCard.querySelector('select[name^="quiz_question_type["]');
    var questionPointsField = questionCard.querySelector('input[name^="quiz_question_points["]');
    var tfCorrectField = questionCard.querySelector('select[name^="quiz_tf_correct["]');
    var questionText = questionTextField ? questionTextField.value.trim() : '';
    var questionType = questionTypeField ? questionTypeField.value : 'choix_unique';
    var questionPoints = questionPointsField ? questionPointsField.value.trim() : '1';
    if (questionText === '' || questionText.length < 3) {
      registerDynamicFieldError(questionTextField, questionLabel + ' : le texte doit contenir au moins 3 caracteres.');
    }
    if (!isAllowedOption(questionType, ['choix_unique', 'choix_multiple', 'vrai_faux'])) {
      registerDynamicFieldError(questionTypeField, questionLabel + ' : le type de question est invalide.');
      continue;
    }
    if (!integerPattern.test(questionPoints) || parseInt(questionPoints, 10) <= 0) {
      registerDynamicFieldError(questionPointsField, questionLabel + ' : les points doivent etre un entier superieur a 0.');
    }
    if (questionType === 'vrai_faux') {
      var tfValue = tfCorrectField ? tfCorrectField.value : 'true';
      if (!isAllowedOption(tfValue, ['true', 'false'])) {
        registerDynamicFieldError(tfCorrectField, questionLabel + ' : la bonne reponse vrai/faux est invalide.');
      }
      continue;
    }
    var answerRows = questionCard.querySelectorAll('.quiz-builder-answer-row');
    var validAnswers = 0;
    var correctCount = 0;
    for (var a = 0; a < answerRows.length; a++) {
      var answerRow = answerRows[a];
      var answerTextField = answerRow.querySelector('input[type="text"]');
      var answerCorrectField = answerRow.querySelector('input[type="checkbox"]');
      var answerText = answerTextField ? answerTextField.value.trim() : '';
      var isCorrectAnswer = answerCorrectField ? answerCorrectField.checked : false;
      if (answerText !== '') {
        validAnswers += 1;
        if (isCorrectAnswer) { correctCount += 1; }
      } else if (isCorrectAnswer) {
        registerDynamicFieldError(answerTextField, questionLabel + ' : une reponse cochee ne peut pas etre vide.');
      }
    }
    if (validAnswers < 2) { registerFormError(questionLabel + ' : ajoutez au moins 2 reponses non vides.'); }
    if (correctCount <= 0) { registerFormError(questionLabel + ' : cochez au moins une bonne reponse.'); }
    if (questionType === 'choix_unique' && correctCount !== 1) { registerFormError(questionLabel + ' : en choix unique, une seule bonne reponse est autorisee.'); }
  }

  if (!isValid) {
    showFormMessage(formElement, 'error', firstErrorMessage !== '' ? firstErrorMessage : 'Veuillez corriger les champs en erreur.');
  }
  return isValid;
}

// Validates the main search form.
function validateSearchForm(formElement)
{
  clearFormMessage(formElement);
  var searchField = formElement.querySelector('[name="q"]');
  if (!searchField) { return true; }
  searchField.classList.remove('input-error');
  var value = searchField.value.trim();
  if (value !== '' && value.length < 2) {
    searchField.classList.add('input-error');
    showFormMessage(formElement, 'error', 'Veuillez saisir au moins 2 caracteres pour la recherche.');
    return false;
  }
  return true;
}

// Validates the filter form option values.
function validateFilterForm(formElement)
{
  clearFormMessage(formElement);
  var niveauField = formElement.querySelector('[name="niveau"]');
  var certifField = formElement.querySelector('[name="certif"]');
  var isValid = true;
  if (niveauField) {
    niveauField.classList.remove('input-error');
    if (!isAllowedOption(niveauField.value, ['', 'debutant', 'intermediaire', 'avance'])) {
      niveauField.classList.add('input-error');
      isValid = false;
    }
  }
  if (certifField) {
    certifField.classList.remove('input-error');
    if (!isAllowedOption(certifField.value, ['', 'oui', 'non'])) {
      certifField.classList.add('input-error');
      isValid = false;
    }
  }
  if (!isValid) {
    showFormMessage(formElement, 'error', 'Valeur de filtre invalide. Choisissez une option dans la liste.');
  }
  return isValid;
}

// Validates that each quiz question has at least one selected answer.
function validateQuizSubmitForm(formElement)
{
  clearFormMessage(formElement);
  var questionItems = formElement.querySelectorAll('.quiz-question-item');
  var isValid = true;
  for (var i = 0; i < questionItems.length; i++) {
    var questionItem = questionItems[i];
    questionItem.classList.remove('quiz-question-error');
    var options = questionItem.querySelectorAll('input[type="radio"], input[type="checkbox"]');
    var hasChecked = false;
    for (var j = 0; j < options.length; j++) {
      if (options[j].checked) { hasChecked = true; break; }
    }
    if (!hasChecked) {
      questionItem.classList.add('quiz-question-error');
      isValid = false;
    }
  }
  if (!isValid) {
    showFormMessage(formElement, 'error', 'Selectionnez au moins une reponse pour chaque question du quiz.');
  }
  return isValid;
}

// Escapes text before injecting it into quiz builder markup.
function quizBuilderEscapeHtml(value)
{
  var text = String(value);
  return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#039;');
}

// Builds HTML markup used by the dynamic form builder.
function quizBuilderAnswerRowMarkup(questionIndex, answerIndex, answerText, isCorrect)
{
  var checked = isCorrect ? ' checked' : '';
  return '<div class="quiz-builder-answer-row"><input type="text" name="quiz_answer_text[' + questionIndex + '][' + answerIndex + ']" value="' + quizBuilderEscapeHtml(answerText) + '" placeholder="Texte de la réponse"><label><input type="checkbox" name="quiz_answer_correct[' + questionIndex + '][]" value="' + answerIndex + '"' + checked + '>Bonne réponse</label></div>';
}

// Builds HTML markup used by the dynamic form builder.
function workshopBuilderRowMarkup(workshopIndex)
{
  return '<div class="workshop-builder-item" data-workshop-index="' + workshopIndex + '"><div class="workshop-builder-head"><strong>Workshop <span class="workshop-builder-number">0</span></strong><button type="button" class="btn-cancel quiz-remove-btn" onclick="removeWorkshopBuilderRow(this)">Supprimer</button></div><div class="front-form-row-2"><div class="form-group"><label>Titre workshop</label><input type="text" name="workshop_titre[' + workshopIndex + ']" placeholder="Ex: Atelier pratique de poterie" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="200"></div><div class="form-group"><label>Mentor ID (optionnel)</label><input type="number" min="1" step="1" name="workshop_mentor_id[' + workshopIndex + ']" placeholder="Ex: 3"></div></div><div class="form-group"><label>Description workshop</label><textarea name="workshop_description[' + workshopIndex + ']" rows="2" placeholder="Description du workshop"></textarea></div><div class="front-form-row-3"><div class="form-group"><label>Durée workshop (heures)</label><input type="number" min="1" step="1" name="workshop_duree[' + workshopIndex + ']" placeholder="Ex: 2"></div><div class="form-group"><label>Date atelier (optionnel)</label><input type="datetime-local" name="workshop_date_atelier[' + workshopIndex + ']"></div><div class="form-group"><label>Lieu workshop</label><input type="text" name="workshop_lieu[' + workshopIndex + ']" placeholder="Ex: Tunis" pattern="[A-Za-zÀ-ÖØ-öø-ÿ ]+" maxlength="255"></div></div><div class="front-form-row-3"><div class="form-group"><label>Places max workshop</label><input type="number" min="1" step="1" name="workshop_places_max[' + workshopIndex + ']" placeholder="Ex: 20"></div><div class="form-group"><label>Prix workshop (TND)</label><input type="number" min="0" step="0.01" name="workshop_prix[' + workshopIndex + ']" placeholder="Ex: 120"></div><div class="form-group"><label>Certification workshop</label><select name="workshop_certification[' + workshopIndex + ']"><option value="oui">Oui</option><option value="non" selected>Non</option></select></div></div><div class="front-form-row-2"><div class="form-group"><label>Statut workshop</label><select name="workshop_statut[' + workshopIndex + ']"><option value="a_venir" selected>A venir</option><option value="en_cours">En cours</option><option value="termine">Termine</option><option value="annule">Annule</option></select></div></div></div>';
}

// Refreshes derived UI values and labels.
function refreshWorkshopBuilderLabels()
{
  var container = document.getElementById('workshopBuilderRows');
  if (!container) { return; }
  var cards = container.querySelectorAll('.workshop-builder-item');
  for (var i = 0; i < cards.length; i++) {
    var label = cards[i].querySelector('.workshop-builder-number');
    if (label) { label.textContent = String(i + 1); }
  }
}

// Adds a new item to the current dataset or form.
function addWorkshopBuilderRow()
{
  var container = document.getElementById('workshopBuilderRows');
  if (!container) { return; }
  var nextWorkshopIndex = parseInt(container.getAttribute('data-next-workshop-index'), 10);
  if (isNaN(nextWorkshopIndex) || nextWorkshopIndex < 0) {
    nextWorkshopIndex = container.querySelectorAll('.workshop-builder-item').length;
  }
  var workshopIndex = String(nextWorkshopIndex);
  container.setAttribute('data-next-workshop-index', String(nextWorkshopIndex + 1));
  container.insertAdjacentHTML('beforeend', workshopBuilderRowMarkup(workshopIndex));
  refreshWorkshopBuilderLabels();
}

// Removes the selected item from the current dataset or form.
function removeWorkshopBuilderRow(buttonElement)
{
  var card = buttonElement.closest('.workshop-builder-item');
  if (!card) { return; }
  var container = document.getElementById('workshopBuilderRows');
  card.remove();
  refreshWorkshopBuilderLabels();
  if (container && container.querySelectorAll('.workshop-builder-item').length === 0) {
    addWorkshopBuilderRow();
  }
}

// Refreshes derived UI values and labels.
function refreshQuizBuilderQuestionLabels()
{
  var container = document.getElementById('quizBuilderQuestions');
  if (!container) { return; }
  var cards = container.querySelectorAll('.quiz-builder-question');
  for (var i = 0; i < cards.length; i++) {
    var label = cards[i].querySelector('.quiz-builder-number');
    if (label) { label.textContent = String(i + 1); }
  }
}

// Updates question inputs when the quiz question type changes.
function onQuizBuilderTypeChange(selectElement, questionIndex)
{
  var tfBox = document.getElementById('quizTrueFalseBox' + String(questionIndex));
  var answersBox = document.getElementById('quizAnswersBox' + String(questionIndex));
  if (!tfBox || !answersBox) { return; }
  if (selectElement.value === 'vrai_faux') {
    tfBox.classList.remove('quiz-builder-hidden');
    answersBox.classList.add('quiz-builder-hidden');
  } else {
    tfBox.classList.add('quiz-builder-hidden');
    answersBox.classList.remove('quiz-builder-hidden');
  }
}

// Adds a new item to the current dataset or form.
function addQuizBuilderAnswer(questionIndex)
{
  var questionCard = document.querySelector('.quiz-builder-question[data-question-index="' + String(questionIndex) + '"]');
  if (!questionCard) { return; }
  var answerList = document.getElementById('quizAnswerList' + String(questionIndex));
  if (!answerList) { return; }
  var nextAnswerIndex = parseInt(questionCard.getAttribute('data-next-answer-index'), 10);
  if (isNaN(nextAnswerIndex) || nextAnswerIndex < 0) {
    nextAnswerIndex = answerList.children.length;
  }
  answerList.insertAdjacentHTML('beforeend', quizBuilderAnswerRowMarkup(questionIndex, nextAnswerIndex, '', false));
  questionCard.setAttribute('data-next-answer-index', String(nextAnswerIndex + 1));
}

// Removes the selected item from the current dataset or form.
function removeQuizBuilderQuestion(buttonElement)
{
  var card = buttonElement.closest('.quiz-builder-question');
  if (!card) { return; }
  var container = document.getElementById('quizBuilderQuestions');
  card.remove();
  refreshQuizBuilderQuestionLabels();
  if (container && container.querySelectorAll('.quiz-builder-question').length === 0) {
    addQuizBuilderQuestion();
  }
}

// Adds a new item to the current dataset or form.
function addQuizBuilderQuestion()
{
  var container = document.getElementById('quizBuilderQuestions');
  if (!container) { return; }
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

// Opens the related modal or panel.
function openFormationDetails(formationId)
{
  var sectionGrid = document.getElementById('formationsGridSection');
  var sectionDetail = document.getElementById('formationDetailView');
  if (!sectionGrid || !sectionDetail) { return; }
  var panels = sectionDetail.querySelectorAll('.formation-detail-card');
  var found = false;
  var activePanel = null;
  for (var i = 0; i < panels.length; i++) {
    var panel = panels[i];
    var panelId = parseInt(panel.getAttribute('data-detail-id'), 10);
    if (panelId === formationId) {
      panel.style.display = 'block';
      found = true;
      activePanel = panel;
    } else {
      panel.style.display = 'none';
    }
  }
  if (!found || !activePanel) { return; }
  var workshopSections = sectionDetail.querySelectorAll('.associated-workshops');
  for (var j = 0; j < workshopSections.length; j++) { workshopSections[j].classList.remove('open'); }
  var workshopButtons = sectionDetail.querySelectorAll('.workshops-toggle-btn');
  for (var k = 0; k < workshopButtons.length; k++) { workshopButtons[k].textContent = 'Voir ateliers'; }
  var quizPanels = sectionDetail.querySelectorAll('.quiz-questions-panel');
  for (var l = 0; l < quizPanels.length; l++) { quizPanels[l].classList.remove('open'); }
  var quizButtons = sectionDetail.querySelectorAll('.quiz-launch-btn');
  for (var m = 0; m < quizButtons.length; m++) { quizButtons[m].textContent = 'Passer quizz'; }
  var selectedWorkshopSection = activePanel.querySelector('.associated-workshops');
  if (selectedWorkshopSection) { selectedWorkshopSection.classList.add('open'); }
  var selectedWorkshopButton = activePanel.querySelector('.workshops-toggle-btn');
  if (selectedWorkshopButton) { selectedWorkshopButton.textContent = 'Masquer ateliers'; }
  var selectedQuizPanel = activePanel.querySelector('.quiz-questions-panel');
  if (selectedQuizPanel) { selectedQuizPanel.classList.add('open'); }
  var selectedQuizButton = activePanel.querySelector('.quiz-launch-btn');
  if (selectedQuizButton) { selectedQuizButton.textContent = 'Masquer quizz'; }
  sectionGrid.style.display = 'none';
  sectionDetail.classList.add('open');
  sectionDetail.setAttribute('data-active-id', String(formationId));
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Closes the related modal or panel.
function closeFormationDetails()
{
  var sectionGrid = document.getElementById('formationsGridSection');
  var sectionDetail = document.getElementById('formationDetailView');
  if (!sectionGrid || !sectionDetail) { return; }
  sectionGrid.style.display = '';
  sectionDetail.classList.remove('open');
  sectionDetail.removeAttribute('data-active-id');
  var panels = sectionDetail.querySelectorAll('.formation-detail-card');
  for (var i = 0; i < panels.length; i++) { panels[i].style.display = 'none'; }
  var workshopSections = sectionDetail.querySelectorAll('.associated-workshops');
  for (var j = 0; j < workshopSections.length; j++) { workshopSections[j].classList.remove('open'); }
  var workshopButtons = sectionDetail.querySelectorAll('.workshops-toggle-btn');
  for (var k = 0; k < workshopButtons.length; k++) { workshopButtons[k].textContent = 'Voir ateliers'; }
  var quizPanels = sectionDetail.querySelectorAll('.quiz-questions-panel');
  for (var l = 0; l < quizPanels.length; l++) { quizPanels[l].classList.remove('open'); }
  var quizButtons = sectionDetail.querySelectorAll('.quiz-launch-btn');
  for (var m = 0; m < quizButtons.length; m++) { quizButtons[m].textContent = 'Passer quizz'; }
  // Stop any playing audio when closing
  stopDarijaSpeech();
}

// Toggles visibility for the requested section.
function toggleAssociatedWorkshops(formationId)
{
  var container = document.getElementById('associatedWorkshops' + String(formationId));
  if (!container) { return; }
  var button = document.querySelector('.workshops-toggle-btn[data-formation-id="' + String(formationId) + '"]');
  var isOpen = container.classList.contains('open');
  if (isOpen) {
    container.classList.remove('open');
    if (button) { button.textContent = 'Voir ateliers'; }
  } else {
    container.classList.add('open');
    if (button) { button.textContent = 'Masquer ateliers'; }
  }
}

// Toggles visibility for the requested section.
function toggleQuizPanel(formationId)
{
  var panel = document.getElementById('quizPanel' + String(formationId));
  if (!panel) { return; }
  var button = document.querySelector('.quiz-launch-btn[data-formation-id="' + String(formationId) + '"]');
  var isOpen = panel.classList.contains('open');
  if (isOpen) {
    panel.classList.remove('open');
    if (button) { button.textContent = 'Passer quizz'; }
  } else {
    panel.classList.add('open');
    if (button) { button.textContent = 'Masquer quizz'; }
  }
}

document.addEventListener('DOMContentLoaded', function ()
{
  var searchForm = document.getElementById('formRechercheFront');
  if (searchForm) {
    searchForm.addEventListener('submit', function (event) {
      if (!validateSearchForm(searchForm)) { event.preventDefault(); }
    });
    var searchField = searchForm.querySelector('[name="q"]');
    if (searchField) {
      searchField.addEventListener('input', function () {
        searchField.classList.remove('input-error');
        clearFormMessage(searchForm);
      });
    }
  }
  var filterForm = document.getElementById('formFiltresFront');
  if (filterForm) {
    filterForm.addEventListener('submit', function (event) {
      if (!validateFilterForm(filterForm)) { event.preventDefault(); }
    });
    var filterFields = filterForm.querySelectorAll('select');
    for (var fs = 0; fs < filterFields.length; fs++) {
      (function (filterField) {
        filterField.addEventListener('change', function () {
          filterField.classList.remove('input-error');
          clearFormMessage(filterForm);
        });
      })(filterFields[fs]);
    }
  }
  var quizSubmitForms = document.querySelectorAll('.quiz-submit-form');
  for (var qf = 0; qf < quizSubmitForms.length; qf++) {
    (function (quizForm) {
      quizForm.addEventListener('submit', function (event) {
        if (!validateQuizSubmitForm(quizForm)) { event.preventDefault(); }
      });
      quizForm.addEventListener('change', function (event) {
        var target = event.target;
        if (!target || (target.type !== 'radio' && target.type !== 'checkbox')) { return; }
        var questionItem = target.closest('.quiz-question-item');
        if (!questionItem) { return; }
        var options = questionItem.querySelectorAll('input[type="radio"], input[type="checkbox"]');
        for (var i = 0; i < options.length; i++) {
          if (options[i].checked) {
            questionItem.classList.remove('quiz-question-error');
            clearFormMessage(quizForm);
            return;
          }
        }
      });
    })(quizSubmitForms[qf]);
  }
  var addFormationForm = document.getElementById('formAjoutFormationFront');
  if (addFormationForm) {
    addFormationForm.addEventListener('submit', function (event) {
      if (!validateAddFormationForm(addFormationForm)) { event.preventDefault(); }
    });
    var addFormFields = addFormationForm.querySelectorAll('input, textarea, select');
    for (var af = 0; af < addFormFields.length; af++) {
      (function (fieldElement) {
        var liveEventName = 'input';
        if (fieldElement.tagName === 'SELECT' || fieldElement.type === 'checkbox' || fieldElement.type === 'radio') {
          liveEventName = 'change';
        }
        fieldElement.addEventListener(liveEventName, function () {
          fieldElement.classList.remove('input-error');
          clearFormMessage(addFormationForm);
          var fieldName = fieldElement.getAttribute('name');
          if (fieldName !== null && fieldName !== '') {
            var staticAlert = addFormationForm.querySelector('[data-field-error-for="' + fieldName + '"]');
            if (staticAlert) {
              staticAlert.textContent = '';
              staticAlert.classList.remove('show');
            }
          }
          var group = fieldElement.closest('.form-group');
          if (group) {
            var dynamicGroupAlert = group.querySelector('.field-alert.dynamic-field-alert');
            if (dynamicGroupAlert) { dynamicGroupAlert.remove(); }
          }
          var nextDynamicAlert = fieldElement.nextElementSibling;
          if (nextDynamicAlert && nextDynamicAlert.classList.contains('field-alert') && nextDynamicAlert.classList.contains('dynamic-field-alert')) {
            nextDynamicAlert.remove();
          }
        });
      })(addFormFields[af]);
    }
  }
  refreshWorkshopBuilderLabels();
  refreshQuizBuilderQuestionLabels();
  var typeSelects = document.querySelectorAll('.quiz-builder-type-select');
  for (var ts = 0; ts < typeSelects.length; ts++) {
    var typeSelect = typeSelects[ts];
    var questionIndex = typeSelect.getAttribute('data-question-index');
    if (questionIndex !== null && questionIndex !== '') {
      onQuizBuilderTypeChange(typeSelect, questionIndex);
    }
  }
  var cartesFormations = document.querySelectorAll('.formation-card');
  for (var i = 0; i < cartesFormations.length; i++) {
    (function (carte) {
      var formationId = parseInt(carte.getAttribute('data-formation-id'), 10);
      carte.addEventListener('click', function (event) {
        if (event.target && event.target.closest('.formation-detail-btn')) { return; }
        if (formationId > 0) { openFormationDetails(formationId); }
      });
      carte.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && formationId > 0) {
          event.preventDefault();
          openFormationDetails(formationId);
        }
      });
      var boutonDetail = carte.querySelector('.formation-detail-btn');
      if (boutonDetail) {
        boutonDetail.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          if (formationId > 0) { openFormationDetails(formationId); }
        });
      }
    })(cartesFormations[i]);
  }
  var autoOpenId = <?php echo (string) ((int) $selectedFormationId); ?>;
  if (autoOpenId > 0) { openFormationDetails(autoOpenId); }
});
// Alternative: Use Google TTS API (better Arabic voices)
async function speakDarijaTextGoogle(buttonElement, text) {
    // Stop any ongoing audio
    const audioPlayer = document.getElementById('google-tts-player');
    if (audioPlayer) {
        audioPlayer.pause();
    }
    
    const originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = '⏳ تشغيل...';
    buttonElement.disabled = true;
    
    try {
        // Google TTS API (free, no API key required for basic usage)
        const url = 'https://translate.google.com/translate_tts?ie=UTF-8&q=' + 
                    encodeURIComponent(text) + '&tl=ar&client=tw-ob';
        
        let player = document.getElementById('google-tts-player');
        if (!player) {
            player = document.createElement('audio');
            player.id = 'google-tts-player';
            player.style.display = 'none';
            document.body.appendChild(player);
        }
        
        player.src = url;
        
        player.onplay = function() {
            buttonElement.innerHTML = '🔊 جاري القراءة...';
            buttonElement.classList.add('playing');
        };
        
        player.onended = function() {
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
            buttonElement.classList.remove('playing');
        };
        
        player.onerror = function() {
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
            buttonElement.classList.remove('playing');
            alert('عذرا، لم نتمكن من تشغيل الصوت. حاول مرة أخرى.');
        };
        
        await player.play();
        
    } catch (error) {
        console.error('Google TTS error:', error);
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
        buttonElement.classList.remove('playing');
        alert('عذرا، حدث خطأ في تشغيل الصوت.');
    }
}
</script>


</body>
</html>