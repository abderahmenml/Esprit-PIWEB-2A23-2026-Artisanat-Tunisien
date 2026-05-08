<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Ne pas afficher les erreurs directement

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Vérifier l'authentification
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Vérifier la requête POST avec fichier
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['pdf'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fichier manquant'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES['pdf'];

    // Validation du fichier
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Erreur lors du téléchargement: ' . $file['error']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10 MB max
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 10 MB)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Vérifier le type MIME
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        $mimeType = $_FILES['pdf']['type'];
    }

    if ($mimeType !== 'application/pdf') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Seuls les fichiers PDF sont acceptés (détecté: ' . $mimeType . ')'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Extraire le texte du PDF
    $pdfText = extractPdfText($file['tmp_name']);

    if ($pdfText === null || trim($pdfText) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Impossible d\'extraire le texte du PDF'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Parser le texte pour extraire les informations
    $cvData = parseCVFromText($pdfText);

    echo json_encode([
        'success' => true,
        'message' => 'PDF importé avec succès',
        'data' => $cvData
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
    echo json_encode(['success' => false, 'message' => 'Impossible d\'extraire le texte du PDF']);
    exit;
}

// Parser le texte pour extraire les informations
$cvData = parseCVFromText($pdfText);

echo json_encode([
    'success' => true,
    'message' => 'PDF importé avec succès',
    'data' => $cvData
]);

/**
 * Extrait le texte d'un fichier PDF
 */
function extractPdfText(string $filePath): ?string
{
    // Utiliser pdftotext si disponible (Linux/Mac)
    if (function_exists('exec') && shell_exec('which pdftotext 2>&1')) {
        $output = null;
        $return = 0;
        exec('pdftotext ' . escapeshellarg($filePath) . ' -', $output, $return);
        if ($return === 0 && !empty($output)) {
            return implode("\n", $output);
        }
    }

    // Fallback: Utiliser une méthode basique avec regex pour les PDFs texte
    $content = file_get_contents($filePath);
    if ($content === false) {
        return null;
    }

    // Décoder les flux PDF basiques
    $text = extractPdfTextRegex($content);
    return !empty($text) ? $text : null;
}

/**
 * Extrait le texte d'un PDF avec regex (fallback)
 */
function extractPdfTextRegex(string $pdfContent): string
{
    // Chercher les objets de texte dans le PDF
    $text = '';

    // Pattern pour les textes encodés en clair
    preg_match_all('/BT\s+(.+?)\s+ET/s', $pdfContent, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $match) {
            // Extraire les chaînes de texte
            preg_match_all('/\((.*?)\)\s*Tj/', $match, $strings);
            foreach ($strings[1] as $str) {
                $decoded = decodeSimplePdfString($str);
                $text .= $decoded . ' ';
            }
        }
    }

    // Si rien trouvé, essayer une extraction brute
    if (empty(trim($text))) {
        $text = preg_replace('/[^a-zA-Z0-9\s\-@\.\,\:\;\'\"\/\n]/', '', $pdfContent);
    }

    return trim($text);
}

/**
 * Décode une chaîne simple du PDF
 */
function decodeSimplePdfString(string $str): string
{
    // Remplacer les codes d'échappement courants
    $str = str_replace('\\n', "\n", $str);
    $str = str_replace('\\r', "\r", $str);
    $str = str_replace('\\t', "\t", $str);
    $str = str_replace('\\\\', '\\', $str);
    $str = preg_replace('/\\[0-7]{1,3}/', '', $str);

    return $str;
}

/**
 * Parse le texte du CV pour extraire les informations structurées
 */
function parseCVFromText(string $text): array
{
    $cvData = [
        'personal' => [
            'fullName' => '',
            'title' => '',
            'email' => '',
            'phone' => '',
            'city' => '',
            'summary' => ''
        ],
        'skills' => [],
        'experiences' => [],
        'education' => []
    ];

    // Normaliser le texte
    $text = normalize_text($text);
    $lines = array_filter(array_map('trim', explode("\n", $text)));

    // Extraire l'email
    if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i', $text, $match)) {
        $cvData['personal']['email'] = $match[1];
    }

    // Extraire le téléphone
    if (preg_match('/(\+?\d{1,3}[\s.-]?\(?\d{1,4}\)?[\s.-]?\d{1,4}[\s.-]?\d{1,9})/i', $text, $match)) {
        $cvData['personal']['phone'] = trim($match[1]);
    }

    // Le premier nom est généralement au début
    $firstLine = reset($lines);
    if ($firstLine && strlen($firstLine) < 100) {
        $cvData['personal']['fullName'] = $firstLine;
    }

    // Chercher les sections courantes
    $experienceMode = false;
    $educationMode = false;
    $skillsMode = false;
    $summaryMode = false;

    $currentExp = null;
    $currentEdu = null;

    foreach ($lines as $line) {
        $lower = strtolower($line);

        // Détecter les sections
        if (preg_match('/^(expérience|experiences|professional experience|emploi|travail)/i', $line)) {
            $experienceMode = true;
            $educationMode = false;
            $skillsMode = false;
            $summaryMode = false;
            continue;
        }

        if (preg_match('/^(formation|éducation|education|études|diplôme|diplome|qualifications)/i', $line)) {
            $experienceMode = false;
            $educationMode = true;
            $skillsMode = false;
            $summaryMode = false;
            if ($currentExp) {
                $cvData['experiences'][] = $currentExp;
                $currentExp = null;
            }
            continue;
        }

        if (preg_match('/^(compétence|competences|skills|savoir-faire)/i', $line)) {
            $experienceMode = false;
            $educationMode = false;
            $skillsMode = true;
            $summaryMode = false;
            if ($currentExp) {
                $cvData['experiences'][] = $currentExp;
                $currentExp = null;
            }
            if ($currentEdu) {
                $cvData['education'][] = $currentEdu;
                $currentEdu = null;
            }
            continue;
        }

        if (preg_match('/^(résumé|resume|profil|à propos|about)/i', $line)) {
            $experienceMode = false;
            $educationMode = false;
            $skillsMode = false;
            $summaryMode = true;
            continue;
        }

        // Traiter le contenu
        if ($summaryMode && !empty($line)) {
            $cvData['personal']['summary'] .= $line . ' ';
        } elseif ($skillsMode && !empty($line)) {
            $skills = array_map('trim', explode(',', $line));
            foreach ($skills as $skill) {
                if (!empty($skill) && strlen($skill) < 50) {
                    $cvData['skills'][] = ['name' => $skill, 'level' => 70];
                }
            }
        } elseif ($experienceMode && !empty($line)) {
            // Chercher les dates (YYYY-MM ou YYYY)
            if (preg_match('/(\d{4}|\d{4}-\d{2})/i', $line)) {
                if ($currentExp) {
                    $cvData['experiences'][] = $currentExp;
                }
                $currentExp = [
                    'role' => '',
                    'company' => '',
                    'start' => '',
                    'end' => '',
                    'description' => ''
                ];
                // Extraire les dates
                preg_match('/(\d{4}(?:-\d{2})?)\s*[-–]\s*(\d{4}(?:-\d{2})?|présent|present|aujourd|actual)/i', $line, $dates);
                if (!empty($dates)) {
                    $currentExp['start'] = $dates[1];
                    $currentExp['end'] = $dates[2];
                }
                $currentExp['role'] = preg_replace('/\d{4}.*/', '', $line);
            } elseif ($currentExp) {
                if (empty($currentExp['company'])) {
                    $currentExp['company'] = $line;
                } else {
                    $currentExp['description'] .= $line . ' ';
                }
            }
        } elseif ($educationMode && !empty($line)) {
            if (preg_match('/(\d{4})/i', $line)) {
                if ($currentEdu) {
                    $cvData['education'][] = $currentEdu;
                }
                $currentEdu = [
                    'degree' => '',
                    'school' => '',
                    'start' => '',
                    'end' => '',
                    'description' => ''
                ];
                preg_match('/(\d{4}(?:-\d{2})?)\s*[-–]\s*(\d{4}(?:-\d{2})?|présent|present)/i', $line, $dates);
                if (!empty($dates)) {
                    $currentEdu['start'] = $dates[1];
                    $currentEdu['end'] = $dates[2];
                }
                $currentEdu['degree'] = preg_replace('/\d{4}.*/', '', $line);
            } elseif ($currentEdu) {
                if (empty($currentEdu['school'])) {
                    $currentEdu['school'] = $line;
                } else {
                    $currentEdu['description'] .= $line . ' ';
                }
            }
        }
    }

    // Ajouter les derniers éléments
    if ($currentExp) {
        $cvData['experiences'][] = $currentExp;
    }
    if ($currentEdu) {
        $cvData['education'][] = $currentEdu;
    }

    // Nettoyer et limiter les résumés
    $cvData['personal']['summary'] = trim($cvData['personal']['summary']);
    if (strlen($cvData['personal']['summary']) > 500) {
        $cvData['personal']['summary'] = substr($cvData['personal']['summary'], 0, 500) . '...';
    }

    // Filtrer les compétences doublons
    $uniqueSkills = [];
    foreach ($cvData['skills'] as $skill) {
        $exists = false;
        foreach ($uniqueSkills as $existing) {
            if (strtolower($existing['name']) === strtolower($skill['name'])) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $uniqueSkills[] = $skill;
        }
    }
    $cvData['skills'] = array_slice($uniqueSkills, 0, 20);

    // Limiter experiences et formations
    $cvData['experiences'] = array_slice(array_filter($cvData['experiences'], function (array $e) {
        return !empty($e['role']);
    }), 0, 10);

    $cvData['education'] = array_slice(array_filter($cvData['education'], function (array $e) {
        return !empty($e['degree']);
    }), 0, 10);

    return $cvData;
}

/**
 * Normalise le texte
 */
function normalize_text(string $text): string
{
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    return $text;
}
