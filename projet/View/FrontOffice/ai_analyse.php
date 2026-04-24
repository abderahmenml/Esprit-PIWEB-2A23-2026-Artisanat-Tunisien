<?php
/**
 * ai_analyse.php  —  Groq + Llama 3 version
 * Drop this file next to your index.php.
 *
 * SETUP:
 *   1. Go to https://console.groq.com — create a free account
 *   2. Generate an API key
 *   3. Replace YOUR_GROQ_API_KEY below
 */

header('Content-Type: application/json; charset=utf-8');

define('GROQ_API_KEY', '');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST requis']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

if (empty(GROQ_API_KEY)) {
    http_response_code(500);
    echo json_encode(['error' => 'Clé API manquante']);
    exit;
}

// Parse competences
$competences = [];
if (!empty($data['competences']) && is_array($data['competences'])) {
    foreach ($data['competences'] as $c) {
        if (is_array($c)) {
            $name  = $c['name'] ?? $c['nom'] ?? '';
            $level = $c['level'] ?? $c['niveau'] ?? '';
            if ($name) {
                $competences[] = $level ? "$name ($level)" : $name;
            }
        } else {
            $competences[] = $c;
        }
    }
}

// Parse materials
$materials = [];
if (!empty($data['materials']) && is_array($data['materials'])) {
    foreach ($data['materials'] as $m) {
        if (is_array($m)) {
            $name = $m['name'] ?? $m['nom'] ?? '';
            $qty  = $m['quantity'] ?? $m['quantité'] ?? '';
            if ($name) {
                $materials[] = $qty ? "$name x$qty" : $name;
            }
        } else {
            $materials[] = $m;
        }
    }
}

// Alternative: check for 'materiaux' key
if (empty($materials) && !empty($data['materiaux']) && is_array($data['materiaux'])) {
    foreach ($data['materiaux'] as $m) {
        if (is_array($m)) {
            $name = $m['name'] ?? $m['nom'] ?? '';
            $qty  = $m['quantity'] ?? $m['quantité'] ?? '';
            if ($name) {
                $materials[] = $qty ? "$name x$qty" : $name;
            }
        } else {
            $materials[] = $m;
        }
    }
}

// Format for prompt
$competencesText = !empty($competences) ? "\n- Competences: " . implode(", ", $competences) : "";
$materialsText   = !empty($materials) ? "\n- Materiaux: " . implode(", ", $materials) : "";

// Build user prompt
$userPrompt = <<<PROMPT
Analyze this craft/artisan project idea and provide structured feedback:

Title: {$data['title']}
Category: {$data['category']}
Status: {$data['status']}
Budget: {$data['budget']}
Description: {$data['description']}$competencesText$materialsText

Provide your response as a JSON object with exactly these fields (respond in French):
{
  "score": <0-100>,
  "resume": "<short summary>",
  "ameliorations": ["<improvement>"],
  "corrections": [
    {"field": "<which field>", "issue": "<problem>", "suggestion": "<fix>"}
  ],
  "tendances": ["<trend>"],
  "projets_similaires": [
    {"name": "<name>", "similarity": <0-100>, "reason": "<reason>", "proofLink": "<url or null>"}
  ],
  "risques": ["<risk>"],
  "disponibilite_marche_tunisien": {
    "competences": [
      {"nom": "<competence>", "disponible": "<oui/non/partiel>", "justification": "<why>"}
    ],
    "materiaux": [
      {"nom": "<material>", "disponible": "<oui/non/partiel>", "justification": "<why>"}
    ]
  }
}

For market availability assessment, evaluate if these competences and materials are readily available in Tunisia's artisan/craft market. Use only oui (yes), non (no), or partiel (partial).

Return ONLY valid JSON, no extra text.
PROMPT;

// Make API call to Groq
$requestBody = json_encode([
    'model'       => GROQ_MODEL,
    'messages'    => [
        ['role' => 'system', 'content' => 'You are an expert analyst for Tunisian artisan projects. Respond in French with valid JSON only.'],
        ['role' => 'user',   'content' => $userPrompt]
    ],
    'temperature' => 0.4,
    'max_tokens'  => 1024,
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_POSTFIELDS    => $requestBody,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT       => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => "Groq error: HTTP $httpCode"]);
    exit;
}

$decoded = json_decode($response, true);
if (!$decoded || !isset($decoded['choices'][0]['message']['content'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from Groq']);
    exit;
}

$content = $decoded['choices'][0]['message']['content'];
$result  = json_decode($content, true);

if (!$result) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to parse AI response as JSON']);
    exit;
}

// Return the analysis
http_response_code(200);
echo json_encode($result);
<?php
/**
 * ai_analyse.php  —  Groq + Llama 3 version
 * Drop this file next to your index.php.
 *
 * SETUP:
 *   1. Go to https://console.groq.com — create a free account
 *   2. Generate an API key
 *   3. Replace YOUR_GROQ_API_KEY below
 */

header('Content-Type: application/json; charset=utf-8');

define('GROQ_API_KEY', '');
define('GROQ_MODEL',   'llama-3.3-70b-versatile');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST requis']);
    exit;
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

$title       = isset($data['title'])       ? trim($data['title'])       : '';
$category    = isset($data['category'])    ? trim($data['category'])    : '';
$description = isset($data['description']) ? trim($data['description']) : '';
$budget      = isset($data['budget'])      ? trim($data['budget'])      : '';
$status      = isset($data['status'])      ? trim($data['status'])      : '';

$competencesInput = [];
if (isset($data['competences']) && is_array($data['competences'])) {
    $competencesInput = $data['competences'];
} elseif (isset($data['skills']) && is_array($data['skills'])) {
    $competencesInput = $data['skills'];
}

$materiauxInput = [];
if (isset($data['materiaux']) && is_array($data['materiaux'])) {
    $materiauxInput = $data['materiaux'];
} elseif (isset($data['materials']) && is_array($data['materials'])) {
    $materiauxInput = $data['materials'];
}

$competencesList = [];
foreach ($competencesInput as $competence) {
    if (is_array($competence)) {
        $name = isset($competence['name']) ? trim((string)$competence['name']) : '';
        if ($name === '' && isset($competence['nom'])) {
            $name = trim((string)$competence['nom']);
        }
        $level = isset($competence['level']) ? trim((string)$competence['level']) : '';
        if ($level === '' && isset($competence['skill_level'])) {
            $level = trim((string)$competence['skill_level']);
        }
        if ($name !== '') {
            $competencesList[] = $level !== '' ? ($name . ' (' . $level . ')') : $name;
        }
    } elseif (is_string($competence) && trim($competence) !== '') {
        $competencesList[] = trim($competence);
    }
}

$materiauxList = [];
foreach ($materiauxInput as $materiau) {
    if (is_array($materiau)) {
        $name = isset($materiau['name']) ? trim((string)$materiau['name']) : '';
        if ($name === '' && isset($materiau['nom'])) {
            $name = trim((string)$materiau['nom']);
        }
        if ($name === '' && isset($materiau['nom_materiel'])) {
            $name = trim((string)$materiau['nom_materiel']);
        }
        $qty = isset($materiau['quantity']) ? trim((string)$materiau['quantity']) : '';
        if ($qty === '' && isset($materiau['quantite'])) {
            $qty = trim((string)$materiau['quantite']);
        }
        if ($name !== '') {
            $materiauxList[] = $qty !== '' ? ($name . ' x' . $qty) : $name;
        }
    } elseif (is_string($materiau) && trim($materiau) !== '') {
        $materiauxList[] = trim($materiau);
    }
}

$competencesText = 'Non renseignees';
if (count($competencesList) > 0) {
    $competencesText = implode(', ', $competencesList);
}

$materiauxText = 'Non renseignes';
if (count($materiauxList) > 0) {
    $materiauxText = implode(', ', $materiauxList);
}

if ($title === '' && $description === '') {
    echo json_encode(['error' => 'Titre ou description requis']);
    exit;
}

$systemPrompt = 'Tu es un assistant expert en projets artisanaux tunisiens pour la plateforme 7erfa Tunisie. Tu analyses les fiches projet soumises par les utilisateurs. Tu reponds TOUJOURS et UNIQUEMENT en JSON valide, sans texte avant ni apres, sans balises markdown.';

$userPrompt = "Voici la fiche projet d'un utilisateur :\n"
    . "- Titre : {$title}\n"
    . "- Categorie : {$category}\n"
    . "- Statut : {$status}\n"
    . "- Budget : {$budget} DT\n"
    . "- Competences : {$competencesText}\n"
    . "- Materiaux : {$materiauxText}\n"
    . "- Description : {$description}\n\n"
    . 'Reponds UNIQUEMENT avec ce JSON (aucun texte autour) :' . "\n"
    . '{
  "score": <entier 0-100 representant la qualite et completude de l\'idee>,
  "resume": "<une phrase courte resumant l\'etat de l\'idee>",
  "ameliorations": ["<conseil 1>", "<conseil 2>", "<conseil 3>"],
  "corrections": [{"champ": "<champ>", "probleme": "<probleme>", "suggestion": "<suggestion>"}],
  "tendances": ["<tendance 1>", "<tendance 2>"],
    "disponibilite_marche_tunisien": {
        "competences": [{"nom": "<nom competence>", "disponible": "<oui/non/partiel>", "justification": "<raison courte>"}],
        "materiaux": [{"nom": "<nom materiau>", "disponible": "<oui/non/partiel>", "justification": "<raison courte>"}]
    },
  "projets_similaires": [{"nom": "<nom>", "similarite": <0-100>, "raison": "<raison>", "lien_recherche": "<url google>"}],
  "risques": ["<risque 1>", "<risque 2>"]
}' . "\n\n"
        . "Regles: detecte les fautes d'orthographe, signale les champs vides, evalue la disponibilite des competences et materiaux dans le marche tunisien, projets similaires artisanat tunisien/mediterraneen, lien_recherche = URL Google encodee, reponds en francais, UNIQUEMENT le JSON brut.";

$payload = json_encode([
    'model'       => GROQ_MODEL,
    'max_tokens'  => 1024,
    'temperature' => 0.4,
    'messages'    => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user',   'content' => $userPrompt],
    ],
]);

$ch = curl_init(GROQ_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur reseau : ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(500);
    $decoded = json_decode($response, true);
    $msg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Erreur API Groq';
    echo json_encode(['error' => $msg]);
    exit;
}

$apiResult = json_decode($response, true);
$text = '';
if (isset($apiResult['choices'][0]['message']['content'])) {
    $text = $apiResult['choices'][0]['message']['content'];
}

$text = trim($text);
$text = preg_replace('/^```json\s*/i', '', $text);
$text = preg_replace('/^```\s*/i',     '', $text);
$text = preg_replace('/\s*```$/',      '', $text);
$text = trim($text);

$analysis = json_decode($text, true);
if (!$analysis) {
    http_response_code(500);
    echo json_encode(['error' => 'Reponse IA non parseable', 'raw' => $text]);
    exit;
}

echo json_encode($analysis);