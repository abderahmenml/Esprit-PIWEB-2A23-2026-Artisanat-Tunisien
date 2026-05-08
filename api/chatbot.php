<?php

session_start();
require_once __DIR__ . '/../config/config.php';

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Methode non autorisee']);
    exit;
}

$body = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'Payload JSON invalide']);
    exit;
}

$message = trim((string)($body['message'] ?? ''));
$history = $body['history'] ?? [];
$profilId = (int)($body['profil_id'] ?? 0);
$provider = strtolower(trim((string)($body['provider'] ?? 'ollama')));
$warmup = !empty($body['warmup']);

if ($message === '' || $profilId === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parametres manquants']);
    exit;
}

if ($warmup) {
    function callWarmupOllama(): ?string
    {
        return callOllama([
            ['role' => 'system', 'content' => 'Tu es un assistant utile et concis.'],
            ['role' => 'user', 'content' => 'Bonjour']
        ], true);
    }

    $warmupReply = callWarmupOllama();

    echo json_encode([
        'reply' => $warmupReply !== null ? '' : '',
        'provider' => $warmupReply !== null ? 'ollama' : 'none',
        'suggestions' => []
    ]);
    exit;
}

$pdo = getPDO();

try {
    $stmt = $pdo->prepare("\n        SELECT u.prenom, u.nom, u.email,\n               pp.specialite, pp.ville\n        FROM user u\n        LEFT JOIN profil_professionnel pp ON u.id_user = pp.id_user\n        WHERE u.id_user = ?\n    ");
    $stmt->execute([$profilId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Profil introuvable']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT nom_competence, niveau FROM competences WHERE id_user = ? ORDER BY ordre');
    $stmt->execute([$profilId]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT poste, entreprise, date_debut, date_fin FROM experience WHERE id_user = ? ORDER BY date_debut DESC LIMIT 5');
    $stmt->execute([$profilId]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT nom_certification, niveau FROM certification WHERE id_user = ? ORDER BY ordre');
    $stmt->execute([$profilId]);
    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT AVG(note) as note_moy, COUNT(*) as total FROM avis WHERE id_user_recepteur = ?');
    $stmt->execute([$profilId]);
    $avis = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['note_moy' => null, 'total' => 0];
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de donnees']);
    exit;
}

$competencesTxt = implode(', ', array_map(static fn(array $c): string => ((string)$c['nom_competence']) . ' (' . (int)$c['niveau'] . '%)', $competences));
$experiencesTxt = implode(' | ', array_map(static fn(array $e): string => ((string)$e['poste']) . ' chez ' . ((string)$e['entreprise']), $experiences));
$certifTxt = implode(', ', array_map(static fn(array $c): string => (string)$c['nom_certification'], $certifications));
$noteTxt = ((int)($avis['total'] ?? 0) > 0)
    ? number_format((float)($avis['note_moy'] ?? 0), 1) . '/5 base sur ' . (int)$avis['total'] . ' avis'
    : 'Pas encore d\'avis';

$prenom = trim((string)($user['prenom'] ?? 'artisan'));
$nom = trim((string)($user['nom'] ?? ''));

$systemPrompt = <<<PROMPT
Tu es un assistant IA généraliste intelligent et polyvalent, exactement comme ChatGPT.

OUBLIE TOUT CONTEXTE PRÉCÉDENT RELATIF À UN PROFIL, UN PROFESSIONNEL OU UN ARTISAN. Tu n'es pas lié à une personne spécifique.

Tu dois:
1. Répondre en français ou en arabe selon la langue de la question
2. Répondre librement à n'importe quel sujet (science, tech, culture, conseils, créatif, etc.)
3. Être conversationnel, utile et bienveillant
4. Répondre directement et précisément aux questions posées
5. Pour les sujets complexes: expliquer de manière claire et compréhensible
6. Si tu ne sais pas: être honnête et proposer une alternative
7. Être concis (2-5 phrases, 300-500 caractères max)
8. Maintenir un ton professionnel et amical
9. Poser des questions de suivi si approprié

IMPORTANT: Tu es une IA GÉNÉRALE libre. Ne mentionne JAMAIS de professionnel, d'artisan, de plateforme Harfa ou de contexte professionnel spécifique.

STYLE: Intelligent, accessible, polyvalent, orienté solution.
PROMPT;

$safeHistory = [];
foreach (array_slice((array)$history, -4) as $msg) {
    $role = (string)($msg['role'] ?? '');
    $content = trim((string)($msg['content'] ?? ''));
    if ($content === '' || !in_array($role, ['user', 'assistant'], true)) {
        continue;
    }
    // Nettoyer le contenu de toute mention du profil
    $content = preg_replace('/assistant\s+de\s+\w+|expert\s+en\s+\w+|spécialisé|artisan|professionnel/i', '', $content);
    $safeHistory[] = [
        'role' => $role,
        'content' => substr(strip_tags($content), 0, 700)
    ];
}

$safeHistory[] = [
    'role' => 'user',
    'content' => substr(strip_tags($message), 0, 700)
];

$messages = [
    ['role' => 'system', 'content' => $systemPrompt]
];
foreach ($safeHistory as $msg) {
    $messages[] = $msg;
}

$suggestedQuestions = [
    'Pose-moi une question sur n\'importe quel sujet',
    'Explique-moi comment fonctionne l\'IA',
    'Aide-moi à résoudre un problème',
    'Donne-moi des conseils pratiques'
];


function callOllama(array $messages, bool $warmup = false): ?string
{
    $baseUrl = rtrim((string)get_ollama_base_url(), '/');
    $model = get_ollama_model();

    $payload = [
        'model' => $model,
        'messages' => $messages,
        'stream' => false,
        'keep_alive' => '10m',
        'options' => [
            'temperature' => 0.6,
            'top_k' => 30,
            'top_p' => 0.85,
            'num_predict' => $warmup ? 16 : 180
        ]
    ];

    $ch = curl_init($baseUrl . '/api/chat');
    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 120,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }

    $reply = $data['message']['content'] ?? null;
    return is_string($reply) ? trim($reply) : null;
}

function callXai(array $messages): ?string
{
    $apiKey = trim((string)getenv('XAI_API_KEY'));
    if ($apiKey === '') {
        return null;
    }

    $payload = [
        'model' => 'grok-2-latest',
        'messages' => $messages,
        'max_tokens' => 300,
        'temperature' => 0.6
    ];

    $ch = curl_init('https://api.x.ai/v1/chat/completions');
    if ($ch === false) {
        return null;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!is_string($response) || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return null;
    }

    $reply = $data['choices'][0]['message']['content'] ?? null;
    return is_string($reply) ? trim($reply) : null;
}

$reply = null;
$usedProvider = 'none';

// Always try Ollama first. The local model is the main assistant.
$reply = callOllama($messages);
if ($reply !== null && $reply !== '') {
    $usedProvider = 'ollama';
} else {
    // Keep a secondary fallback, but only if Ollama is unavailable.
    $reply = callXai($messages);
    if ($reply !== null && $reply !== '') {
        $usedProvider = 'xai';
    }
}

if ($reply === null || $reply === '') {
    $reply = 'Je rencontre un souci temporaire de réponse. Reformulez votre question en une phrase simple, et je vous répondrai avec précision.';
}

// Persist chat messages (non-fatal)
try {
    $pdo = getPDO();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    // store user message
    $stmt = $pdo->prepare("INSERT INTO chat_messages (profil_id, user_id, role, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$profilId, $userId, 'user', $body['message'] ?? '']);
    // store assistant reply
    $stmt->execute([$profilId, $userId, 'assistant', $reply]);
} catch (Exception $e) {
    // ignore persistence errors
}

echo json_encode([
    'reply' => $reply,
    'provider' => $usedProvider,
    'suggestions' => $suggestedQuestions
]);

