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
Analyse cette idee de projet artisanal tunisien et fournis un diagnostic complet:

Title: {$data['title']}
Category: {$data['category']}
Status: {$data['status']}
Budget: {$data['budget']}
Description: {$data['description']}$competencesText$materialsText

Reponds en francais avec un objet JSON valide contenant exactement ces champs:
{
  "score": <0-100>,
  "resume": "<short summary>",
    "verdict_originalite": {
        "statut": "<probablement existante|partiellement originale|plutot originale>",
        "confiance": <0-100>,
                "justification": "<explication courte>",
                "preuves": [
                        {"source": "<nom source>", "url": "<url>", "raison": "<pourquoi cette source prouve la similarite ou l originalite>"}
                ]
    },
  "ameliorations": ["<improvement>"],
  "corrections": [
    {"field": "<which field>", "issue": "<problem>", "suggestion": "<fix>"}
  ],
    "orthographe": [
        {"texte": "<mot ou phrase source>", "correction": "<version corrigee>", "gravite": "<faible|moyenne|elevee>"}
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

Regles importantes:
- Vérifie explicitement si l'idee existe deja ou si elle est proche d'idees connues.
- Ajoute des liens de preuve dans verdict_originalite.preuves.
- Si statut = probablement existante ou partiellement originale, fournis au moins 1 lien valide.
- Si statut = plutot originale, tu peux fournir un tableau vide ou des liens montrant l'absence de match direct.
- Donne au moins 3 ameliorations actionnables.
- Detecte les fautes d'orthographe/grammaire dans le titre et la description.
- Pour disponibilite_marche_tunisien, utilise uniquement oui/non/partiel.
- Si une information est inconnue, mets null ou un tableau vide.

Return ONLY valid JSON, no extra text.
PROMPT;

// Make API call to Groq
$requestBody = json_encode([
    'model'       => GROQ_MODEL,
    'messages'    => [
        ['role' => 'system', 'content' => 'Tu es un analyste expert des projets artisanaux tunisiens. Tu reponds uniquement en JSON valide et en francais.'],
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

$content = trim((string)$decoded['choices'][0]['message']['content']);

// Some models still wrap JSON in markdown fences or add extra prose.
$content = preg_replace('/^```json\s*/i', '', $content);
$content = preg_replace('/^```\s*/i', '', $content);
$content = preg_replace('/\s*```$/', '', $content);
$content = trim($content);

$result = json_decode($content, true);

if (!$result) {
    $firstBrace = strpos($content, '{');
    $lastBrace  = strrpos($content, '}');

    if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
        $jsonCandidate = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
        $result = json_decode($jsonCandidate, true);
    }
}

if (!$result) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Failed to parse AI response as JSON',
        'raw' => $content,
    ]);
    exit;
}

// Return the analysis
http_response_code(200);
echo json_encode($result);
