<?php
// apply_offre.php
// Artisan-only apply form: insert into application + application_offre

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/Config.php';
require_once dirname(__DIR__) . '/partials/job_ui.php';
require_role('artisan');

$userNom = (string)($_SESSION['nom'] ?? 'Utilisateur');
$userPrenom = (string)($_SESSION['prenom'] ?? '');
$userId = (int)($_SESSION['user_id'] ?? 0);
$baseUrl = app_base_url();

if (!isset($_SESSION['apply_offer_csrf'])) {
    $_SESSION['apply_offer_csrf'] = bin2hex(random_bytes(16));
}
$applyCsrf = (string)$_SESSION['apply_offer_csrf'];

$idOffer = (int)($_GET['id_offer'] ?? $_POST['id_offer'] ?? 0);
if ($idOffer <= 0) {
    header('Location: ' . $baseUrl . 'controllers/offer_emploi/offres.php');
    exit();
}

$notice = trim((string)($_GET['notice'] ?? ''));

$userStmt = $pdo->prepare('SELECT email FROM user WHERE id_user = ? LIMIT 1');
$userStmt->execute([$userId]);
$currentUser = $userStmt->fetch() ?: [];

$offerStmt = $pdo->prepare('SELECT id_offer, titre, description FROM offre_emploi WHERE id_offer = ? LIMIT 1');
$offerStmt->execute([$idOffer]);
$offer = $offerStmt->fetch();

if (!$offer) {
    die('Offre introuvable.');
}

$hasParsedCvDataColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'parsed_cv_data'")->fetch();
$hasCvParsingStatusColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_parsing_status'")->fetch();
$hasCvParsedAtColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_parsed_at'")->fetch();
$hasCvFileNameColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_name'")->fetch();
$hasCvFileSizeColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_size'")->fetch();
$hasCvFileTypeColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_type'")->fetch();
$hasCvFileHashColumn = (bool)$pdo->query("SHOW COLUMNS FROM application LIKE 'cv_file_hash'")->fetch();

$formData = [
    'candidate_name' => trim($userPrenom . ' ' . $userNom),
    'candidate_email' => (string)($currentUser['email'] ?? ''),
    'skills' => '',
    'experience' => '',
    'education' => '',
    'lettre_de_motivation' => '',
    'cv_text' => '',
];

$errors = [];

$buildStructuredCv = static function (array $payload): array {
    $skills = array_values(array_filter(array_map(
        'trim',
        preg_split('/[,\n;]/', (string)($payload['skills'] ?? '')) ?: []
    )));

    return [
        'full_name' => trim((string)($payload['candidate_name'] ?? '')),
        'email' => trim((string)($payload['candidate_email'] ?? '')),
        'skills' => $skills,
        'experience' => trim((string)($payload['experience'] ?? '')),
        'education' => trim((string)($payload['education'] ?? '')),
    ];
};

$autoFillFromCvText = static function (string $text): array {
    $result = [
        'candidate_name' => '',
        'candidate_email' => '',
        'skills' => '',
        'experience' => '',
        'education' => '',
    ];

    if (preg_match('/(?:name|nom)\s*[:\-]\s*(.+)/i', $text, $m)) {
        $result['candidate_name'] = trim($m[1]);
    }
    if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
        $result['candidate_email'] = trim($m[0]);
    }
    if (preg_match('/(?:skills|comp[ée]tences)\s*[:\-]\s*(.+)/i', $text, $m)) {
        $result['skills'] = trim($m[1]);
    }
    if (preg_match('/(?:experience|exp[ée]rience)\s*[:\-]\s*(.+)/i', $text, $m)) {
        $result['experience'] = trim($m[1]);
    }
    if (preg_match('/(?:education|formation|[ée]tudes)\s*[:\-]\s*(.+)/i', $text, $m)) {
        $result['education'] = trim($m[1]);
    }

    return $result;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = (string)($_POST['csrf'] ?? '');
    if ($applyCsrf === '' || !hash_equals($applyCsrf, $postedCsrf)) {
        $errors[] = 'Session expirée. Veuillez réessayer.';
    }

    $lettre = trim((string)($_POST['lettre_de_motivation'] ?? ''));
    $cvText = trim((string)($_POST['cv_text'] ?? ''));
    $formData['candidate_name'] = trim((string)($_POST['candidate_name'] ?? $formData['candidate_name']));
    $formData['candidate_email'] = trim((string)($_POST['candidate_email'] ?? $formData['candidate_email']));
    $formData['skills'] = trim((string)($_POST['skills'] ?? ''));
    $formData['experience'] = trim((string)($_POST['experience'] ?? ''));
    $formData['education'] = trim((string)($_POST['education'] ?? ''));
    $formData['lettre_de_motivation'] = $lettre;
    $formData['cv_text'] = $cvText;

    if ($cvText !== '') {
        $auto = $autoFillFromCvText($cvText);
        foreach ($auto as $key => $value) {
            if (($formData[$key] ?? '') === '' && $value !== '') {
                $formData[$key] = $value;
            }
        }
    }

    $cvPath = '';
    $cvFileMeta = [
        'name' => null,
        'size' => null,
        'type' => null,
        'hash' => null,
    ];

    $alreadyStmt = $pdo->prepare(
        'SELECT a.id
         FROM application a
         JOIN application_offre ao ON ao.id_application = a.id
         WHERE a.id_user = ? AND ao.id_offre = ?
         LIMIT 1'
    );
    $alreadyStmt->execute([$userId, $idOffer]);
    if ($alreadyStmt->fetch()) {
        header('Location: ' . $baseUrl . 'controllers/offer_emploi/apply_offre.php?id_offer=' . $idOffer . '&notice=exists');
        exit();
    }

    if ($lettre === '') {
        $errors[] = 'La lettre de motivation est obligatoire.';
    }

    if ($formData['candidate_email'] !== '' && filter_var($formData['candidate_email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'Email candidat invalide.';
    }

    // CV upload
    if (isset($_FILES['cv_file']) && ($_FILES['cv_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmp = (string)($_FILES['cv_file']['tmp_name'] ?? '');
        $originalName = basename((string)($_FILES['cv_file']['name'] ?? ''));
        $fileSize = (int)($_FILES['cv_file']['size'] ?? 0);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowedExt = ['pdf', 'doc', 'docx'];
        $allowedMime = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmp !== '' ? (string)$finfo->file($tmp) : '';

        if ($fileSize <= 0 || $fileSize > (8 * 1024 * 1024)) {
            $errors[] = 'Le CV doit faire entre 1 Ko et 8 Mo.';
        } elseif (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
            $errors[] = 'Format CV non supporté. Utilisez PDF, DOC ou DOCX.';
        } else {
            $uploadDir = dirname(__DIR__, 2) . '/uploads/cv/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'cv_' . $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $uploadDir . $filename;

            if (move_uploaded_file($tmp, $target)) {
                $cvPath = 'uploads/cv/' . $filename;
                $cvFileMeta['name'] = $originalName;
                $cvFileMeta['size'] = $fileSize;
                $cvFileMeta['type'] = $mime;
                $cvFileMeta['hash'] = hash_file('sha256', $target) ?: null;
            } else {
                $errors[] = 'Impossible de sauvegarder le CV téléversé.';
            }
        }
    }

    $cvValue = $cvPath !== '' ? $cvPath : $cvText;
    if ($cvValue === '') {
        $errors[] = 'Ajoutez un CV (fichier ou texte).';
    }

    $structuredCv = $buildStructuredCv($formData);

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $columns = ['id_user', 'id_offer', 'lettre_de_motivation', 'cv', 'status', 'date_creation'];
            $values = [$userId, $idOffer, $lettre, $cvValue, 'pending'];
            $placeholders = ['?', '?', '?', '?', '?', 'NOW()'];

            if ($hasParsedCvDataColumn) {
                $columns[] = 'parsed_cv_data';
                $values[] = json_encode($structuredCv, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $placeholders[] = '?';
            }
            if ($hasCvParsingStatusColumn) {
                $columns[] = 'cv_parsing_status';
                $values[] = $cvText !== '' ? 'success' : 'manual_entry';
                $placeholders[] = '?';
            }
            if ($hasCvParsedAtColumn) {
                $columns[] = 'cv_parsed_at';
                $placeholders[] = 'NOW()';
            }
            if ($hasCvFileNameColumn) {
                $columns[] = 'cv_file_name';
                $values[] = $cvFileMeta['name'];
                $placeholders[] = '?';
            }
            if ($hasCvFileSizeColumn) {
                $columns[] = 'cv_file_size';
                $values[] = $cvFileMeta['size'];
                $placeholders[] = '?';
            }
            if ($hasCvFileTypeColumn) {
                $columns[] = 'cv_file_type';
                $values[] = $cvFileMeta['type'];
                $placeholders[] = '?';
            }
            if ($hasCvFileHashColumn) {
                $columns[] = 'cv_file_hash';
                $values[] = $cvFileMeta['hash'];
                $placeholders[] = '?';
            }

            $insertApp = $pdo->prepare(
                'INSERT INTO application (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')'
            );
            $insertApp->execute($values);
            $applicationId = (int)$pdo->lastInsertId();

            $insertPivot = $pdo->prepare('INSERT INTO application_offre (id_offre, id_application) VALUES (?, ?)');
            $insertPivot->execute([$idOffer, $applicationId]);

            $pdo->commit();
            header('Location: ' . $baseUrl . 'controllers/offer_emploi/my_applications.php?notice=applied');
            exit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $errors[] = 'Erreur lors de l\'envoi de candidature.';
        }
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Postuler | حرفة Tunisie</title>
    <link rel="icon" type="image/x-icon" href="<?php echo h(job_asset('assets/favicon.ico')); ?>" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="<?php echo h(job_asset('assets/css/styles.css')); ?>" rel="stylesheet" />
    <style>
        .dashboard-navbar { background-color: rgba(59, 35, 20, 0.96) !important; padding: 0.8rem 0; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .dashboard-navbar .nav-link { color: #F5ECD7 !important; font-weight: 600; margin: 0 0.35rem; }
        .dashboard-navbar .nav-link:hover { color: #C49A6C !important; }
        .user-greeting { color: #C49A6C; font-weight: bold; margin-right: 1rem; }
        .btn-logout { background: #2E6B3E !important; color: white !important; padding: 0.45rem 0.9rem; border-radius: 4px; text-decoration: none; font-weight: 600; }
        .hero-banner { background: linear-gradient(135deg, rgba(139, 90, 58, 0.75), rgba(46, 107, 62, 0.75)), url('<?php echo h(job_asset('assets/img/item_pics/IMG_3043.JPG')); ?>'); background-size: cover; background-position: center; color: #F5ECD7; padding: 3.2rem 0; text-align: center; border-bottom: 5px solid #8B5A3A; margin-bottom: 2rem; }
        .footer-section { background: #3B2314; color: #F5ECD7; padding: 3rem 0 2rem; margin-top: 3rem; }
        .footer-section a { color: #F5ECD7; text-decoration: none; }
        .footer-section a:hover { color: #C49A6C; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg dashboard-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>">
            <img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="40" style="margin-right: 0.8rem;">
            <span style="color: #F5ECD7; font-weight: 700; font-size: 1.3rem;">حرفة Tunisie</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-2">
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/home.php'); ?>"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link active" href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>"><i class="fas fa-briefcase me-1"></i>Emplois</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/offer_emploi/my_applications.php'); ?>"><i class="fas fa-list-check me-1"></i>Mes candidatures</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo h($baseUrl . 'controllers/user/profile.php'); ?>"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><span class="user-greeting"><i class="fas fa-user-circle me-1"></i><?php echo h(trim($userPrenom . ' ' . $userNom)); ?></span></li>
                <li class="nav-item"><a class="btn-logout" href="<?php echo h($baseUrl . 'controllers/session_status.php?action=logout'); ?>"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-banner"><div class="container"><h2 class="mb-0">Postuler à une offre</h2></div></section>

<section class="page-section cta">
    <div class="container">
        <div class="cta-inner bg-faded rounded p-5">
            <?php if ($notice === 'exists'): ?>
                <div class="alert alert-warning">Vous avez déjà postulé à cette offre.</div>
            <?php endif; ?>
            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo h($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <h3><?php echo htmlspecialchars($offer['titre']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($offer['description'])); ?></p>
            <form method="post" enctype="multipart/form-data" class="row g-3">
                <input type="hidden" name="id_offer" value="<?php echo (int)$idOffer; ?>" />
                <input type="hidden" name="csrf" value="<?php echo h($applyCsrf); ?>" />
                <div class="col-md-6">
                    <label class="form-label">Nom complet</label>
                    <input class="form-control" name="candidate_name" value="<?php echo h($formData['candidate_name']); ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input class="form-control" type="email" name="candidate_email" value="<?php echo h($formData['candidate_email']); ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Compétences</label>
                    <input class="form-control" name="skills" value="<?php echo h($formData['skills']); ?>" placeholder="Ex: PHP, Laravel, UI/UX" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Expérience</label>
                    <input class="form-control" name="experience" value="<?php echo h($formData['experience']); ?>" placeholder="Ex: 3 ans en développement web" />
                </div>
                <div class="col-12">
                    <label class="form-label">Formation</label>
                    <textarea class="form-control" name="education" rows="2"><?php echo h($formData['education']); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Lettre de motivation</label>
                    <textarea class="form-control" name="lettre_de_motivation" rows="6" required><?php echo h($formData['lettre_de_motivation']); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">CV (texte, pour auto-remplissage si possible)</label>
                    <textarea class="form-control" name="cv_text" rows="4" placeholder="Collez ici un extrait de CV pour auto-remplir les champs."><?php echo h($formData['cv_text']); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">CV (fichier)</label>
                    <input class="form-control" type="file" name="cv_file" accept=".pdf,.doc,.docx" />
                    <small class="text-muted">Formats acceptés: PDF/DOC/DOCX (8 Mo max).</small>
                </div>
                <div class="col-12 text-center">
                    <button class="btn btn-primary btn-xl" type="submit">Envoyer candidature</button>
                </div>
            </form>
        </div>
    </div>
</section>

<footer class="footer-section">
    <div class="container"><div class="row"><div class="col-md-4 mb-4 mb-md-0"><img src="<?php echo h(job_asset('assets/img/logo_herfa.png')); ?>" alt="Logo" height="50" class="mb-3"><p class="small">حرفة Tunisie - La plateforme dédiée à l'artisanat tunisien et à l'entrepreneuriat responsable.</p></div><div class="col-md-2 mb-4 mb-md-0"><h6 class="mb-3">Liens rapides</h6><ul class="list-unstyled small"><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/home.php'); ?>">Accueil</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/projects/projets.php'); ?>">Projets</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/formation/formations.php'); ?>">Formations</a></li><li class="mb-2"><a href="<?php echo h($baseUrl . 'controllers/offer_emploi/offres.php'); ?>">Emplois</a></li></ul></div><div class="col-md-3 mb-4 mb-md-0"><h6 class="mb-3">Ressources</h6><ul class="list-unstyled small"><li class="mb-2"><a href="#">Blog</a></li><li class="mb-2"><a href="#">FAQ</a></li><li class="mb-2"><a href="#">Support</a></li><li class="mb-2"><a href="#">Mentions légales</a></li></ul></div><div class="col-md-3"><h6 class="mb-3">Contact</h6><ul class="list-unstyled small"><li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@herfa.tn</li><li class="mb-2"><i class="fas fa-phone me-2"></i> +216 70 000 000</li><li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Tunis, Tunisie</li></ul></div></div><hr class="mt-4 mb-3" style="border-color: rgba(245,236,215,0.2);"><div class="text-center small"><p class="mb-0">© <?php echo date('Y'); ?> حرفة Tunisie - Tous droits réservés</p></div></div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo h(job_asset('assets/js/scripts.js')); ?>"></script>
</body>
</html>
