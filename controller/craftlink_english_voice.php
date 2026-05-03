<?php
/**
 * CraftLink Tunisie — English Voice Module
 * Mirrors the architecture of craftlink_darija_voice.php exactly.
 */

// ============================================================
// LOAD CONFIG IF NOT ALREADY LOADED (safe standalone or included)
// ============================================================
if (!defined('GROQ_API_KEY')) {
    $configPath = __DIR__ . '/../model/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }
}

// ============================================================
// TTS PROXY — fetches English audio from Google Translate and
// streams it back to the browser, bypassing CORS restrictions.
// Called via GET: ?action=tts_proxy_en&tl=en&q=<encoded text>
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'tts_proxy_en') {
    $text = trim($_GET['q'] ?? '');
    $lang = preg_replace('/[^a-z\-]/', '', $_GET['tl'] ?? 'en');

    if ($text === '') {
        http_response_code(400);
        exit;
    }

    $url = 'https://translate.google.com/translate_tts'
         . '?ie=UTF-8'
         . '&tl=' . urlencode($lang)
         . '&client=tw-ob'
         . '&q=' . urlencode($text);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) '
                . 'AppleWebKit/537.36 (KHTML, like Gecko) '
                . 'Chrome/120.0.0.0 Safari/537.36',
            'Referer: https://translate.google.com/',
        ],
    ]);

    $audio = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($audio === false || $httpCode !== 200) {
        http_response_code(502);
        exit;
    }

    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    header('Cache-Control: no-store');
    echo $audio;
    exit;
}

// ============================================================
// ENDPOINT AJAX
// ============================================================
if (isset($_POST['action']) && $_POST['action'] === 'explain_formation_en') {
    header('Content-Type: application/json; charset=utf-8');
    $formationText    = trim($_POST['formation_text']   ?? '');
    $formationTitle   = trim($_POST['formation_title']  ?? '');
    $formationNiveau  = trim($_POST['formation_niveau'] ?? '');
    $formationDuree   = trim($_POST['formation_duree']  ?? '');
    $formationPrix    = trim($_POST['formation_prix']   ?? '');
    $formationCertif  = trim($_POST['formation_certif'] ?? '');
    // Fallback: use the title as the text source when description is empty
    if ($formationText === '' && $formationTitle !== '') {
        $formationText = $formationTitle;
    }
    echo json_encode(explainFormationInEnglish(
        $formationText,
        $formationTitle,
        $formationNiveau,
        $formationDuree,
        $formationPrix,
        $formationCertif
    ));
    exit;
}

// ============================================================
// Generate Detailed English Explanation using GROQ
// ============================================================
function explainFormationInEnglish($formationText, $formationTitle, $formationNiveau, $formationDuree, $formationPrix, $formationCertif)
{
    // Use title as fallback when description is empty (e.g. newly added formations)
    if (empty($formationText)) {
        if (!empty($formationTitle)) {
            $formationText = $formationTitle;
        } else {
            return ['error' => 'Formation text is empty'];
        }
    }

    // Check if GROQ API key exists
    if (!defined('GROQ_API_KEY') || GROQ_API_KEY === '') {
        return ['error' => 'GROQ API key not configured in config.php'];
    }

    // Map niveau to English label
    $niveauEn = '';
    if ($formationNiveau === 'debutant') {
        $niveauEn = 'Beginner';
    } elseif ($formationNiveau === 'intermediaire') {
        $niveauEn = 'Intermediate';
    } elseif ($formationNiveau === 'avance') {
        $niveauEn = 'Advanced';
    }

    $certifEn = ($formationCertif === 'oui')
        ? 'you will receive an official CraftLink certificate'
        : 'you will receive an official participation acknowledgment';

    $prompt = <<<PROMPT
You are an educational guide for traditional Tunisian crafts on the CraftLink platform.

Training information:
- Title: {$formationTitle}
- Level: {$niveauEn}
- Duration: {$formationDuree} hours
- Price: {$formationPrix} Tunisian Dinar
- Certification: {$certifEn}
- Description: {$formationText}

Write a clear, engaging English explanation (6 to 8 sentences) for an artisanal learner. Cover:
1. What this training is and why it matters
2. What skills and techniques they will learn
3. Why it is suitable for their level
4. Duration and price value
5. What they receive upon completion

Use an encouraging, professional tone. Keep it concise and under 250 words.

Example of the expected style: Welcome to this exceptional training in traditional Tunisian pottery — a craft rooted in centuries of artisanal heritage... You will learn how to prepare clay, shape it by hand, and apply authentic decorative techniques... Designed for beginners who want to master the craft from the ground up... The training spans 40 hours at an affordable price... Upon completion you will receive an official CraftLink certificate recognised by the Tunisian craft community...
PROMPT;

    $payload = [
        'model'       => 'llama-3.3-70b-versatile',
        'messages'    => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.8,
        'max_tokens'  => 600,
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        // Fallback detailed explanation
        $fallbackText = generateEnglishFallback(
            $formationTitle, $formationNiveau, $formationDuree,
            $formationPrix, $formationCertif, $formationText
        );
        return [
            'success'      => true,
            'english_text' => $fallbackText,
        ];
    }

    $data = json_decode($response, true);
    $englishText = $data['choices'][0]['message']['content'] ?? '';

    if (empty($englishText)) {
        $englishText = generateEnglishFallback(
            $formationTitle, $formationNiveau, $formationDuree,
            $formationPrix, $formationCertif, $formationText
        );
    }

    return [
        'success'      => true,
        'english_text' => $englishText,
    ];
}

// ============================================================
// Fallback English explanation — no API needed
// ============================================================
function generateEnglishFallback($title, $niveau, $duree, $prix, $certif, $description)
{
    $niveauText = ($niveau === 'debutant')
        ? 'beginners with no prior experience'
        : (($niveau === 'intermediaire')
            ? 'intermediate learners who want to build on their existing skills'
            : 'advanced artisans looking to refine and elevate their craft');

    $certifText = ($certif === 'oui')
        ? 'an official CraftLink certificate recognised by the Tunisian craft community'
        : 'an official participation acknowledgment';

    $descShort = mb_substr($description, 0, 200);

    return "Welcome to \"{$title}\" — a hands-on training programme designed for {$niveauText} in Tunisian traditional crafts. "
         . "Over {$duree} hours of practical learning, you will master authentic techniques passed down through generations of Tunisian artisans. "
         . "{$descShort}... "
         . "The full training is available for just {$prix} Tunisian Dinar, offering outstanding value for professional skill development. "
         . "Upon successful completion, you will receive {$certifText}. "
         . "Don't miss this opportunity — enrol today and take your artisanal career to the next level!";
}