<?php


session_start();
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$message    = trim($body['message'] ?? '');
$history    = $body['history'] ?? [];
$profil_id  = (int)($body['profil_id'] ?? 0);

if ($message === '' || $profil_id === 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

// ─── Charger profil ───────────────────────────────────────

$pdo = getPDO();

try {
    $stmt = $pdo->prepare("
        SELECT u.prenom, u.nom, u.email, u.telephone,
               pp.specialite, pp.bio, pp.ville, pp.portfolio, pp.experience
        FROM user u
        LEFT JOIN profil_professionnel pp ON u.id_user = pp.id_user
        WHERE u.id_user = ?
    ");
    $stmt->execute([$profil_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['error' => 'Profil introuvable']);
        exit;
    }

    // Compétences
    $stmt = $pdo->prepare("SELECT nom_competence, niveau FROM competences WHERE id_user = ? ORDER BY ordre");
    $stmt->execute([$profil_id]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Expériences
    $stmt = $pdo->prepare("SELECT poste, entreprise, date_debut, date_fin FROM experience WHERE id_user = ? ORDER BY date_debut DESC LIMIT 5");
    $stmt->execute([$profil_id]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Certifications
    $stmt = $pdo->prepare("SELECT nom_certification, niveau FROM certification WHERE id_user = ? ORDER BY ordre");
    $stmt->execute([$profil_id]);
    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Avis
    $stmt = $pdo->prepare("SELECT AVG(note) as note_moy, COUNT(*) as total FROM avis WHERE id_user_recepteur = ?");
    $stmt->execute([$profil_id]);
    $avis = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données']);
    exit;
}

// ─── Construire contexte ──────────────────────────────────

$competences_txt = implode(', ', array_map(fn($c) => $c['nom_competence'] . ' (' . $c['niveau'] . '%)', $competences));
$experiences_txt = implode(' | ', array_map(fn($e) => $e['poste'] . ' chez ' . $e['entreprise'], $experiences));
$certif_txt      = implode(', ', array_map(fn($c) => $c['nom_certification'], $certifications));
$note_txt        = $avis['total'] > 0 
    ? number_format($avis['note_moy'], 1) . '/5 basé sur ' . $avis['total'] . ' avis'
    : 'Pas encore d\'avis';

$prenom = htmlspecialchars($user['prenom'] ?? 'l\'artisan');
$nom    = htmlspecialchars($user['nom'] ?? '');

// ─── Prompt système ───────────────────────────────────────

$system_prompt = <<<PROMPT
Tu es l'assistant virtuel de {$prenom} {$nom}, un professionnel inscrit sur la plateforme حرفة Tunisie.
Tu réponds aux visiteurs qui consultent son profil public. Sois chaleureux, professionnel et concis.

PROFIL :
- Spécialité : {$user['specialite']}
- Bio : {$user['bio']}
- Ville : {$user['ville']}
- Expérience : {$user['experience']}
- Compétences : {$competences_txt}
- Expériences : {$experiences_txt}
- Certifications : {$certif_txt}
- Note : {$note_txt}

INSTRUCTIONS :
- Parle à la première personne
- Réponds en 2-4 phrases max
- Ne donne pas email/tel directement
- Réponds en FR ou AR selon utilisateur
PROMPT;

// ─── Historique sécurisé ──────────────────────────────────

$safe_history = [];

foreach (array_slice($history, -10) as $msg) {
    if (in_array($msg['role'] ?? '', ['user', 'assistant']) && !empty($msg['content'])) {
        $safe_history[] = [
            'role' => $msg['role'],
            'content' => substr(strip_tags($msg['content']), 0, 500)
        ];
    }
}

// Ajouter message actuel
$safe_history[] = [
    'role' => 'user',
    'content' => substr($message, 0, 500)
];

// ─── GROK API (xAI) ───────────────────────────────────────

// 🔐 Utilise variable d'environnement
$GROK_API_KEY = getenv('XAI_API_KEY');

if (empty($GROK_API_KEY)) {
    echo json_encode([
        'reply' => "Chatbot non configuré. Ajoutez XAI_API_KEY côté serveur."
    ]);
    exit;
}

// Construire messages
$messages = [
    ['role' => 'system', 'content' => $system_prompt]
];

foreach ($safe_history as $msg) {
    $messages[] = $msg;
}

// Payload Grok
$payload = [
    'model' => 'grok-2-latest',
    'messages' => $messages,
    'max_tokens' => 300,
    'temperature' => 0.7
];

// CURL
$ch = curl_init('https://api.x.ai/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $GROK_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);



if ($http_code !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Erreur IA']);
    exit;
}

$data = json_decode($response, true);

$reply = $data['choices'][0]['message']['content'] 
    ?? 'Désolé, je n\'ai pas pu répondre.';

echo json_encode(['reply' => $reply]);

