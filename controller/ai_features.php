<?php
/**
 * ai_features.php — Fonctionnalités IA pour le module Formations & Certifs
 * CraftLink Tunisie
 *
 * Fonctionnalités implémentées :
 *   1. Générateur IA de description de formation (AJAX endpoint)
 *   2. Génération automatique de questions de quiz par l'IA (AJAX endpoint)
 *   3. Assistant IA de recommandation de formations (AJAX endpoint)
 *   4. Analyse IA du profil apprenant et parcours personnalisé (AJAX endpoint)
 *   5. Résumé IA d'une formation avec mots-clés (AJAX endpoint)
 *
 * Usage : appelé en AJAX depuis front.php et back.php via fetch()
 * Nécessite : GROQ_API_KEY dans config.php ou variable d'environnement
 */

require_once '../model/config.php';

header('Content-Type: application/json; charset=utf-8');

// ── helpers ────────────────────────────────────────────────────────────────

function jsonError(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function jsonOk(array $data): never
{
    echo json_encode($data);
    exit;
}

/**
 * Calls the Groq API (OpenAI-compatible endpoint).
 *
 * @param string $system   System prompt
 * @param string $userMsg  User message
 * @param int    $tokens   Max tokens for the response
 * @return string          Text content of the response
 */
function callClaude(string $system, string $userMsg, int $tokens = 600): string
{
    $apiKey = '';

    // Try runtime config constant first, then environment variable.
    if (defined('GROQ_API_KEY')) {
        $apiKey = GROQ_API_KEY;
    } elseif (getenv('GROQ_API_KEY') !== false) {
        $apiKey = (string) getenv('GROQ_API_KEY');
    }

    if ($apiKey === '') {
        throw new RuntimeException('GROQ_API_KEY non configurée.');
    }

    $payload = json_encode([
        'model'      => 'llama-3.3-70b-versatile',
        'max_tokens' => $tokens,
        'messages'   => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $userMsg]
        ]
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
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

// ── route dispatcher ───────────────────────────────────────────────────────

$action = isset($_POST['ai_action']) ? trim((string) $_POST['ai_action']) : '';

if ($action === '') {
    jsonError('Paramètre ai_action manquant.');
}

// ── 1. Génération de description ──────────────────────────────────────────
if ($action === 'generate_description') {
    $titre  = trim((string) ($_POST['titre']  ?? ''));
    $niveau = trim((string) ($_POST['niveau'] ?? ''));
    $duree  = trim((string) ($_POST['duree']  ?? ''));

    if ($titre === '') {
        jsonError('Titre requis.');
    }

    $system = <<<SYS
Tu es un expert en formations artisanales tunisiennes pour CraftLink Tunisie.
Tu rédiges des descriptions de formation courtes (3-5 phrases), engageantes, en français.
Ton ton est chaleureux, professionnel et inspirant.
Tu mets en avant les compétences pratiques acquises et la valeur du savoir-faire artisanal.
Réponds UNIQUEMENT avec la description, sans titre ni introduction.
SYS;

    $userMsg = "Formation : « {$titre} »\nNiveau : {$niveau}\nDurée : {$duree} heures\n\nGénère une description attrayante pour cette formation.";

    try {
        $description = callClaude($system, $userMsg, 300);
        jsonOk(['description' => $description]);
    } catch (RuntimeException $e) {
        jsonError($e->getMessage(), 500);
    }
}

// ── 2. Génération de questions de quiz ────────────────────────────────────
if ($action === 'generate_quiz_questions') {
    $titre       = trim((string) ($_POST['titre']       ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $niveau      = trim((string) ($_POST['niveau']      ?? 'debutant'));
    $nb          = max(1, min(10, (int) ($_POST['nb'] ?? 5)));

    if ($titre === '') {
        jsonError('Titre requis.');
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
    $userMsg = "Formation : « {$titre} »\nNiveau : {$niveau}{$descSnippet}\n\nGénère {$nb} questions de quiz variées et pertinentes (mix choix_unique, vrai_faux).";

    try {
        $raw = callClaude($system, $userMsg, 1200);

        // Strip potential markdown fences.
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/\s*```$/m', '', $raw);
        $raw = trim($raw);

        $parsed = json_decode($raw, true);
        if (!is_array($parsed) || !isset($parsed['questions']) || !is_array($parsed['questions'])) {
            throw new RuntimeException('Format JSON invalide retourné par l\'IA.');
        }

        jsonOk(['questions' => $parsed['questions']]);
    } catch (RuntimeException $e) {
        jsonError($e->getMessage(), 500);
    }
}

// ── 3. Recommandation de formations ──────────────────────────────────────
if ($action === 'recommend_formations') {
    $interets    = trim((string) ($_POST['interets']    ?? ''));
    $niveau      = trim((string) ($_POST['niveau']      ?? ''));
    $budget      = trim((string) ($_POST['budget']      ?? ''));
    $disponible  = trim((string) ($_POST['disponible']  ?? ''));

    // Fetch formations list from DB.
    $formations = [];
    try {
        $stmt = $pdo->query('SELECT id_formation, domaine, niveau, duree, prix, certification FROM formations WHERE etat = \'Actif\' ORDER BY id_formation DESC LIMIT 30');
        $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        jsonError('Impossible de charger les formations.', 500);
    }

    if (count($formations) === 0) {
        jsonOk(['recommendations' => [], 'message' => 'Aucune formation active disponible.']);
    }

    $formationsText = '';
    foreach ($formations as $f) {
        $certif = ($f['certification'] === 'oui' || $f['certification'] === 'yes' || $f['certification'] === '1') ? 'oui' : 'non';
        $formationsText .= "- ID {$f['id_formation']} | {$f['domaine']} | niveau:{$f['niveau']} | {$f['duree']}h | {$f['prix']} TND | certif:{$certif}\n";
    }

    $system = <<<SYS
Tu es un conseiller en formation artisanale pour CraftLink Tunisie.
On te donne un profil apprenant et une liste de formations disponibles.
Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown).
Format :
{
  "recommendations": [
    {"id_formation": 12, "raison": "Courte explication pourquoi cette formation correspond (1-2 phrases)"},
    ...
  ],
  "conseil": "Un conseil personnalisé global pour cet apprenant (2-3 phrases)."
}
Sélectionne 2 à 4 formations pertinentes maximum, triées par pertinence décroissante.
SYS;

    $profilParts = [];
    if ($interets !== '') {
        $profilParts[] = "Centres d'intérêt : {$interets}";
    }
    if ($niveau !== '') {
        $profilParts[] = "Niveau actuel : {$niveau}";
    }
    if ($budget !== '') {
        $profilParts[] = "Budget max : {$budget} TND";
    }
    if ($disponible !== '') {
        $profilParts[] = "Disponibilité : {$disponible}h/semaine";
    }

    $profil  = implode("\n", $profilParts);
    $userMsg = "Profil apprenant :\n{$profil}\n\nFormations disponibles :\n{$formationsText}\n\nRecommande les formations les plus adaptées.";

    try {
        $raw = callClaude($system, $userMsg, 800);
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/\s*```$/m', '', $raw);
        $raw = trim($raw);

        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Format JSON invalide.');
        }

        jsonOk($parsed);
    } catch (RuntimeException $e) {
        // Fallback deterministic recommendations when external AI is unavailable.
        $niveauUser = strtolower($niveau);
        $budgetUser = is_numeric($budget) ? (float) $budget : null;
        $dispoUser = is_numeric($disponible) ? (float) $disponible : null;

        $tokens = preg_split('/[,\s;]+/u', strtolower($interets));
        if (!is_array($tokens)) {
            $tokens = [];
        }

        $scored = [];
        foreach ($formations as $f) {
            $score = 0;
            $reasons = [];

            $domaine = strtolower((string) ($f['domaine'] ?? ''));
            $niveauFormation = strtolower((string) ($f['niveau'] ?? ''));
            $prixFormation = is_numeric($f['prix'] ?? null) ? (float) $f['prix'] : null;
            $dureeFormation = is_numeric($f['duree'] ?? null) ? (float) $f['duree'] : null;

            $hits = 0;
            foreach ($tokens as $token) {
                $token = trim((string) $token);
                if (strlen($token) < 3) {
                    continue;
                }

                if (strpos($domaine, $token) !== false) {
                    $hits++;
                }
            }

            if ($hits > 0) {
                $score += 35 + (10 * min(3, $hits));
                $reasons[] = 'le domaine correspond à vos centres d\'intérêt';
            }

            if ($niveauUser !== '' && $niveauFormation === $niveauUser) {
                $score += 25;
                $reasons[] = 'le niveau est adapté à votre profil';
            }

            if ($budgetUser !== null && $prixFormation !== null) {
                if ($prixFormation <= $budgetUser) {
                    $score += 18;
                    $reasons[] = 'le prix respecte votre budget';
                } else {
                    $score -= 12;
                }
            }

            if ($dispoUser !== null && $dureeFormation !== null && $dispoUser > 0) {
                $nbSemaines = (int) ceil($dureeFormation / $dispoUser);

                if ($nbSemaines <= 6) {
                    $score += 12;
                    $reasons[] = 'la charge hebdomadaire reste confortable';
                } elseif ($nbSemaines <= 10) {
                    $score += 6;
                    $reasons[] = 'la progression reste faisable avec votre disponibilité';
                }
            }

            $certif = strtolower((string) ($f['certification'] ?? ''));
            if ($certif === 'oui' || $certif === 'yes' || $certif === '1') {
                $score += 4;
            }

            if (count($reasons) === 0) {
                $reasons[] = 'formation globalement cohérente avec votre profil';
            }

            $scored[] = [
                'id_formation' => (int) $f['id_formation'],
                'score' => $score,
                'prix' => $prixFormation ?? 0.0,
                'raison' => ucfirst($reasons[0]) . '.',
            ];
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return $a['prix'] <=> $b['prix'];
            }

            return $b['score'] <=> $a['score'];
        });

        $scored = array_slice($scored, 0, 4);
        $recommendations = [];

        foreach ($scored as $item) {
            $recommendations[] = [
                'id_formation' => $item['id_formation'],
                'raison' => $item['raison'],
            ];
        }

        $conseil = 'Commencez par la première recommandation, puis passez aux suivantes pour progresser étape par étape.';
        if ($budgetUser !== null && $budgetUser < 200) {
            $conseil = 'Avec ce budget, privilégiez d\'abord les formats courts puis élargissez progressivement votre parcours.';
        }

        jsonOk([
            'recommendations' => $recommendations,
            'conseil' => $conseil,
            'fallback' => true,
            'fallback_reason' => $e->getMessage(),
        ]);
    }
}

// ── 4. Parcours personnalisé ──────────────────────────────────────────────
if ($action === 'learning_path') {
    $objectif    = trim((string) ($_POST['objectif']    ?? ''));
    $niveauActuel = trim((string) ($_POST['niveau_actuel'] ?? 'debutant'));

    if ($objectif === '') {
        jsonError('Objectif requis.');
    }

    $formations = [];
    try {
        $stmt = $pdo->query('SELECT id_formation, domaine, niveau, duree, prix, certification FROM formations WHERE etat = \'Actif\' ORDER BY niveau ASC, id_formation DESC LIMIT 50');
        $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        jsonError('Impossible de charger les formations.', 500);
    }

    $formationsText = '';
    foreach ($formations as $f) {
        $certif = ($f['certification'] === 'oui' || $f['certification'] === 'yes' || $f['certification'] === '1') ? 'oui' : 'non';
        $formationsText .= "- ID {$f['id_formation']} | {$f['domaine']} | niveau:{$f['niveau']} | {$f['duree']}h | certif:{$certif}\n";
    }

    $system = <<<SYS
Tu es un expert en conception de parcours pédagogiques artisanaux pour CraftLink Tunisie.
Crée un parcours d'apprentissage structuré à partir des formations disponibles.
Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown).
Format :
{
  "titre_parcours": "Nom accrocheur du parcours",
  "duree_totale": "X semaines / Y heures",
  "etapes": [
    {
      "ordre": 1,
      "titre": "Étape 1 : ...",
      "id_formation": 5,
      "nom_formation": "...",
      "raison": "Pourquoi commencer par là (1 phrase)",
      "objectif_etape": "Ce que l'apprenant maîtrisera"
    }
  ],
  "conseils": "Conseils pratiques pour réussir ce parcours (2-3 phrases)"
}
SYS;

    $userMsg = "Objectif de l'apprenant : {$objectif}\nNiveau actuel : {$niveauActuel}\n\nFormations disponibles :\n{$formationsText}\n\nConçois un parcours d'apprentissage cohérent et progressif.";

    try {
        $raw = callClaude($system, $userMsg, 1000);
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/\s*```$/m', '', $raw);
        $raw = trim($raw);

        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Format JSON invalide.');
        }

        jsonOk($parsed);
    } catch (RuntimeException $e) {
        jsonError($e->getMessage(), 500);
    }
}

// ── 5. Résumé et mots-clés d'une formation ───────────────────────────────
if ($action === 'summarize_formation') {
    $titre       = trim((string) ($_POST['titre']       ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $niveau      = trim((string) ($_POST['niveau']      ?? ''));
    $duree       = trim((string) ($_POST['duree']       ?? ''));

    if ($titre === '') {
        jsonError('Titre requis.');
    }

    $system = <<<SYS
Tu es un expert SEO et pédagogique pour une plateforme de formations artisanales tunisiennes (CraftLink).
Génère un résumé et des métadonnées utiles pour une formation.
Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown).
Format :
{
  "resume_court": "Une phrase de 15 mots max qui capture l'essence de la formation",
  "points_cles": ["Point 1", "Point 2", "Point 3"],
  "mots_cles": ["mot1", "mot2", "mot3", "mot4", "mot5"],
  "public_cible": "Description du public idéal (1 phrase)",
  "prerequis": "Ce qu'il faut savoir avant (1 phrase, ou Aucun prérequis)",
  "debouches": ["Débouché 1", "Débouché 2", "Débouché 3"]
}
SYS;

    $userMsg = "Formation : « {$titre} »\nNiveau : {$niveau}\nDurée : {$duree}h\nDescription : {$description}\n\nGénère les métadonnées de cette formation.";

    try {
        $raw = callClaude($system, $userMsg, 600);
        $raw = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $raw = preg_replace('/\s*```$/m', '', $raw);
        $raw = trim($raw);

        $parsed = json_decode($raw, true);
        if (!is_array($parsed)) {
            throw new RuntimeException('Format JSON invalide.');
        }

        jsonOk($parsed);
    } catch (RuntimeException $e) {
        jsonError($e->getMessage(), 500);
    }
}

jsonError('Action inconnue : ' . htmlspecialchars($action));