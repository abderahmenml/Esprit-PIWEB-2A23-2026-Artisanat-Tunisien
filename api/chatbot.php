<?php

session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

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
Tu es l'assistant virtuel de {$prenom} {$nom}, un professionnel spécialisé en {$user['specialite']} basé à {$user['ville']}, proposant ses services via la plateforme Harfa Tunisie.

PROFIL DÉTAILLÉ:
- Domaine d'expertise: {$user['specialite']}
- Localisation: {$user['ville']}, Tunisie
- Compétences clés: {$competencesTxt}
- Expériences professionnelles: {$experiencesTxt}
- Certifications et qualifications: {$certifTxt}
- Évaluation client: {$noteTxt}

INSTRUCTIONS IMPORTANTES:
1. Réponds toujours en français ou en arabe selon la question posée
2. Sois conversationnel, professionnel et accueillant
3. Réponds directement et précisément à chaque question
4. Utilise des informations réelles du profil pour illustrer tes réponses
5. Pour les questions sur le budget, délais ou services: propose des options concrètes et adaptées
6. Si tu ne sais pas quelque chose d'exact: sois honnête et propose une alternative
7. Engendre un dialogue naturel - pose des questions de suivi si approprié
8. Mentionne les points forts: expérience, certifications, réalisations passées
9. NE donne JAMAIS directement l'email ou le téléphone - propose plutôt une prise de contact via la plateforme
10. Réponds toujours avec 2-5 phrases, maximum 300 caractères pour rester concis

STYLE: Professionnel, bienveillant, orienté solution.
OBJECTIF: Aider le visiteur et faciliter une collaboration potentielle.
PROMPT;

$safeHistory = [];
foreach (array_slice((array)$history, -10) as $msg) {
    $role = (string)($msg['role'] ?? '');
    $content = trim((string)($msg['content'] ?? ''));
    if ($content === '' || !in_array($role, ['user', 'assistant'], true)) {
        continue;
    }
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
    'Quel service recommandez-vous pour commencer ?',
    'Quel est le delai moyen pour ce type de projet ?',
    'Quel budget approximatif faut-il prevoir ?',
    'Pouvez-vous proposer une solution en 3 etapes ?'
];

if (!empty($user['specialite'])) {
    $suggestedQuestions[0] = 'Je cherche un projet en lien avec ' . (string)$user['specialite'] . ', que conseillez-vous ?';
}

function callOllama(array $messages, bool $warmup = false): ?string
{
    $baseUrl = rtrim((string)(getenv('OLLAMA_BASE_URL') ?: 'http://127.0.0.1:11434'), '/');
    $model = trim((string)(getenv('OLLAMA_MODEL') ?: 'phi3:mini'));

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
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 5,
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

echo json_encode([
    'reply' => $reply,
    'provider' => $usedProvider,
    'suggestions' => $suggestedQuestions
]);

