<?php
/**
 * CraftLink Tunisie — Module Vocal Darija with Browser TTS
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
// TTS PROXY — fetches Arabic audio from Google Translate and
// streams it back to the browser, bypassing CORS restrictions.
// Called via GET: ?action=tts_proxy&tl=ar&q=<encoded text>
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'tts_proxy') {
    $text = trim($_GET['q'] ?? '');
    $lang = preg_replace('/[^a-z\-]/', '', $_GET['tl'] ?? 'ar');

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
if (isset($_POST['action']) && $_POST['action'] === 'explain_formation') {
    header('Content-Type: application/json; charset=utf-8');
    $formationText   = trim($_POST['formation_text']   ?? '');
    $formationTitle  = trim($_POST['formation_title']  ?? '');
    $formationNiveau = trim($_POST['formation_niveau'] ?? '');
    $formationDuree  = trim($_POST['formation_duree']  ?? '');
    $formationPrix   = trim($_POST['formation_prix']   ?? '');
    $formationCertif = trim($_POST['formation_certif'] ?? '');
    // Fallback: use the title as the text source when description is empty
    if ($formationText === '' && $formationTitle !== '') {
        $formationText = $formationTitle;
    }
    echo json_encode(explainFormationInDarija($formationText, $formationTitle, $formationNiveau, $formationDuree, $formationPrix, $formationCertif));
    exit;
}

// ============================================================
// Generate Detailed Darija Explanation using GROQ
// ============================================================
function explainFormationInDarija($formationText, $formationTitle, $formationNiveau, $formationDuree, $formationPrix, $formationCertif)
{
    // Use title as fallback when description is empty (e.g. newly added formations)
    if (empty($formationText)) {
        if (!empty($formationTitle)) {
            $formationText = $formationTitle;
        } else {
            return ['error' => 'Texte de formation vide'];
        }
    }
    
    // Check if GROQ API key exists
    if (!defined('GROQ_API_KEY') || GROQ_API_KEY === '') {
        return ['error' => 'GROQ API key non configurée dans config.php'];
    }

    // Translate niveau to Darija
    $niveauDarija = '';
    if ($formationNiveau === 'debutant') {
        $niveauDarija = 'للمبتدئين';
    } elseif ($formationNiveau === 'intermediaire') {
        $niveauDarija = 'للمتوسطين';
    } elseif ($formationNiveau === 'avance') {
        $niveauDarija = 'للمتقدمين';
    }
    
    $certifDarija = ($formationCertif === 'oui') ? 'تحصل على شهادة معتمدة' : 'تحصل على إشعار مشاركة';
    
    // Detailed Darija prompt — strictly Arabic only, zero French words
    $prompt = <<<PROMPT
أنت مرشد تعليمي متخصص في الحرف التقليدية التونسية على منصة كرافت لينك.

معلومات التكوين:
- عنوان التكوين: {$formationTitle}
- المستوى: {$niveauDarija}
- المدة: {$formationDuree} ساعة
- السعر: {$formationPrix} دينار تونسي
- الشهادة: {$certifDarija}
- وصف التكوين: {$formationText}

تعليمات صارمة:
- اكتب بالدارجة التونسية فقط — ممنوع تماما استخدام أي كلمة فرنسية.
- لا تكتب أي كلمة بالحروف اللاتينية.
- الرد كله بالعربية فقط.

شرح مفصل وجذاب (6 إلى 8 جمل) يتضمن:
1. شنوة هو هذا التكوين وعلاش هو مهم
2. أش باش يتعلم الحرفي فيه من مهارات وتقنيات
3. علاش ينصح بهذا التكوين حسب المستوى المطلوب
4. قداش المدة وقداش السعر
5. شنوة باش يتحصل الحرفي في الآخر

أسلوب تحفيزي يشجع الحرفي على المشاركة، واستخدم المصطلحات الحرفية التونسية الأصيلة.

مثال للأسلوب المطلوب: هذا تكوين رائع في صناعة الفخار التقليدي اللي يخدم بالطين... راح تتعلم كيفاش تحضر الطين وتشكله باليدين... مناسب للمبتدئين اللي حبو يتعلموا الحرفة من الصفر... المدة أربعين ساعة بسعر معقول... وفي الآخر تتحصل على شهادة معتمدة من كرافت لينك...
PROMPT;

    $payload = [
        'model'      => 'llama-3.3-70b-versatile',
        'messages'   => [
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
        $fallbackText = generateDetailedFallback($formationTitle, $formationNiveau, $formationDuree, $formationPrix, $formationCertif, $formationText);
        return [
            'success' => true,
            'darija_text' => $fallbackText,
            'text_only' => false
        ];
    }

    $data = json_decode($response, true);
    $darijaText = $data['choices'][0]['message']['content'] ?? '';
    
    if (empty($darijaText)) {
        $darijaText = generateDetailedFallback($formationTitle, $formationNiveau, $formationDuree, $formationPrix, $formationCertif, $formationText);
    }
    
    return [
        'success' => true,
        'darija_text' => $darijaText,
    ];
}

// Fallback detailed explanation — strictly in Tunisian Arabic Darija, zero French
function generateDetailedFallback($title, $niveau, $duree, $prix, $certif, $description)
{
    $niveauText = ($niveau === 'debutant')
        ? 'للمبتدئين اللي ما عندهمش تجربة سابقة'
        : (($niveau === 'intermediaire')
            ? 'للمتوسطين اللي عندهم شوية تجربة ويحبو يكمّلوا'
            : 'للمتقدمين اللي عندهم خبرة ويحبو يرقّوا مهاراتهم');

    $certifText = ($certif === 'oui')
        ? 'تتحصل على شهادة معتمدة من منصة كرافت لينك تشهد على كفاءتك'
        : 'تتحصل على إشعار مشاركة رسمي';

    $descShort = mb_substr($description, 0, 200);

    return "أهلا بيك في تكوين «{$title}»! هذا التكوين موجه {$niveauText} في مجال الحرف التقليدية التونسية. "
         . "المدّة {$duree} ساعة كاملة من التعلّم العملي، والسعر {$prix} دينار تونسي بس. "
         . "{$descShort}... "
         . "في نهاية التكوين {$certifText}. "
         . "ما تضيّعش الفرصة وسجّل اليوم — إن شاء الله تخرج بالمعرفة والخبرة اللي تحتاجها!";
}

// HTML and JavaScript with Browser TTS
?>