<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    die('Erreur : utilisateur non connecté.');
}

$pdo = getPDO();
$user_id = $_SESSION['user_id'];

if (!function_exists('getUserById')) {
    die('Erreur : fonction getUserById non trouvée.');
}

$user = getUserById($user_id);
if (!$user) {
    die('Erreur : utilisateur introuvable dans la base de données.');
}

$stats = getStats($user_id);
$competences = getUserCompetences($user_id);
$certifications = getUserCertifications($user_id);
$portfolioFiles = getUserPortfolioFiles($user_id);
$experiences = getUserExperiences($user_id);
$avis = getUserAvis($user_id);

$initials = strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? 'U', 0, 1));

$specialite = $user['specialite'] ?? 'Spécialité non renseignée';
$bio = $user['bio'] ?? 'Aucune biographie disponible.';
$ville = $user['ville'] ?? 'Tunisie';
$email = $user['email'] ?? 'Non renseigné';
$telephone = $user['telephone'] ?? 'Non renseigné';
$portfolio_url = $user['portfolio'] ?? '';
$total_projets = $stats['projets'] ?? 0;
$note_moyenne = $stats['note'] ?? '0.0';
$total_mentores = $stats['mentores'] ?? 0;

$flash = null;
$flashClass = 'flash-error';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    if (isset($flash['type']) && $flash['type'] === 'success') {
        $flashClass = 'flash-success';
    }
}

$portfolio_items = [
    ['titre' => 'Vase Amazigh', 'description' => 'Décoration', 'icon' => '🏺'],
    ['titre' => 'Service à Tajine', 'description' => 'Arts de la table', 'icon' => '🍽️'],
    ['titre' => 'Carreaux Zellige', 'description' => 'Architecture', 'icon' => '🎨'],
    ['titre' => 'Fontaine en céramique', 'description' => 'Jardin & Ext.', 'icon' => '💧'],
    ['titre' => 'Collection Printemps', 'description' => 'Décoration', 'icon' => '🌸'],
    ['titre' => 'Motifs Islamiques', 'description' => 'Art sacré', 'icon' => '🕌']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Professionnel — <?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?> | حرفة Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="index.js" defer></script>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="logo">
            <span class="ar">حرفة</span>
            <span style="color:#aaa;font-size:.8rem;font-weight:400;margin-left:2px">Tunisie</span>
        </div>
        <nav>
            <a href="dashboard.php">🏠 Accueil</a>
            <a href="profil_professionnel.php">🪪 Mon Profil</a>
            <a href="annuaire.php">👥 Annuaire</a>
            <a href="#">💡 Projets</a>
            <a href="#">🎓 Formations</a>
            <a href="#">📈 Investissement</a>
            <button class="btn-logout" onclick="handleLogout()">Déconnexion</button>
        </nav>
    </header>
    <!-- PAGE PROFIL -->
    <div class="page" id="section-profil">
        <!-- En-tête de page -->
        <div class="page-header">
            <h1>Mon <span>Profil Professionnel</span></h1>
            <nav class="breadcrumb">
                <a href="dashboard.php">Accueil</a> › Profil Professionnel
            </nav>
        </div>
        <?php if ($flash && !empty($flash['msg'])): ?>
            <div class="card flash <?= $flashClass ?>">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <!-- Carte identité -->
            <div class="card profile-card">
                <div class="profile-top">
                    <div class="avatar-wrap">
                        <div class="avatar"><?= htmlspecialchars($initials) ?></div>
                        <div class="avatar-badge"></div>
                    </div>
                    <div class="profile-name"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></div>
                    <div class="profile-title"><?= htmlspecialchars($specialite) ?></div>
                    <div class="profile-location">📍 <?= htmlspecialchars($ville) ?></div>
                    <div class="badge-row">
                        <span class="badge">Entrepreneur</span>
                        <span class="badge green">Disponible</span>
                        <span class="badge">Mentor</span>
                    </div>
                    <button class="edit-btn" onclick="openModal('edit')">✏️ Modifier mon profil</button>
                </div>
            </div>
            <!-- Statistiques -->
            <div class="card">
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="stat-num"><?= $total_projets ?></div>
                        <div class="stat-label">Projets</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-num"><?= $note_moyenne ?></div>
                        <div class="stat-label">Note</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-num"><?= $total_mentores ?></div>
                        <div class="stat-label">Mentorés</div>
                    </div>
                </div>
            </div>
            <!-- Compétences -->
            <div class="card">
                <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Compétences</span>
                    <a href="gestion_competences.php" class="btn-mini" style="margin-left:10px;">Gérer</a>
                </div>
                <?php if (count($competences) > 0): ?>
                    <?php foreach ($competences as $skill): ?>
                    <div class="skill-item">
                        <div class="skill-name">
                            <span><?= htmlspecialchars($skill['nom_competence']) ?></span>
                            <span><?= $skill['niveau'] ?? 50 ?>%</span>
                        </div>
                        <div class="skill-bar">
                            <div class="skill-fill" style="width: <?= $skill['niveau'] ?? 50 ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted" style="color: #888; font-size: 0.8rem;">Aucune compétence renseignée</p>
                <?php endif; ?>
            </div>
            <!-- Certifications -->
            <div class="card">
                <div class="section-title" style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Certifications</span>
                    <a href="gestion_certifications.php" class="btn-mini" style="margin-left:10px;">Gérer</a>
                </div>
                <?php if (count($certifications) > 0): ?>
                    <?php foreach ($certifications as $cert): ?>
                    <div class="cert-item">
                        <div class="cert-icon">🏛️</div>
                        <div>
                            <div class="cert-name"><?= htmlspecialchars($cert['nom_certification'] ?? 'Certification') ?></div>
                            <div class="cert-date">Niveau : <?= (int)($cert['niveau'] ?? 0) ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted" style="color: #888; font-size: 0.8rem;">Aucune certification</p>
                <?php endif; ?>
            </div>
            <!-- Disponibilité -->
            <div class="card">
                <div class="section-title">Disponibilité</div>
                <div class="availability-row">
                    <div class="avail-dot"></div>
                    <div class="avail-text">Disponible</div>
                    <div class="avail-sub">Lun – Sam · 8h–17h</div>
                </div>
            </div>
            <!-- Contact -->
            <div class="card">
                <div class="section-title">Contact</div>
                <div class="contact-row">
                    <div class="contact-icon">📧</div>
                    <?= htmlspecialchars($email) ?>
                </div>
                <div class="contact-row">
                    <div class="contact-icon">📞</div>
                    <?= htmlspecialchars((string)($user['num_tel'] ?? $telephone), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <?php if ($portfolio_url): ?>
                <div class="contact-row">
                    <div class="contact-icon">🌐</div>
                    <a href="<?= htmlspecialchars($portfolio_url) ?>" target="_blank" style="color: var(--caramel); text-decoration: none;">Portfolio</a>
                </div>
                <?php endif; ?>
                <div class="contact-row">
                    <div class="contact-icon">📍</div>
                    <?= htmlspecialchars($ville) ?>
                </div>
            </div>
        </aside>
        <!-- MAIN COL -->
        <main class="main-col">
            <!-- Onglets -->
            <div class="card" style="padding:.75rem 1rem;">
                <div class="tabs" id="profile-tabs">
                    <div class="tab active" onclick="switchTab('bio')">📋 Bio</div>
                    <div class="tab" onclick="switchTab('portfolio')">🖼️ Portfolio</div>
                    <div class="tab" onclick="switchTab('experiences')">💼 Expériences</div>
                    <div class="tab" onclick="switchTab('avis')">⭐ Avis</div>
                </div>
            </div>
            <!-- Panel Bio -->
            <div class="tab-panel active" id="panel-bio">
                <div class="card">
                    <div class="section-title">À propos</div>
                    <p class="bio-text"><?= nl2br(htmlspecialchars($bio)) ?></p>
                </div>
                <div class="card">
                    <div class="section-title">Informations professionnelles</div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem 2rem;">
                        <div class="contact-row" style="border-bottom:none;padding:.4rem 0;">
                            <div class="contact-icon">🏷️</div>
                            <div>
                                <div style="font-size:.7rem;color:#aaa;">Spécialité</div>
                                <div style="font-size:.83rem;font-weight:600;color:var(--brun)"><?= htmlspecialchars($specialite) ?></div>
                            </div>
                        </div>
                        <div class="contact-row" style="border-bottom:none;padding:.4rem 0;">
                            <div class="contact-icon">📅</div>
                            <div>
                                <div style="font-size:.7rem;color:#aaa;">Membre depuis</div>
                                <div style="font-size:.83rem;font-weight:600;color:var(--brun)"><?= date('Y', strtotime($user['date_creation'] ?? 'now')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Panel Portfolio -->
            <div class="tab-panel" id="panel-portfolio">
                <div class="card">
                    <div class="section-title">Mes réalisations</div>
                    <div class="portfolio-grid">
                        <?php foreach ($portfolio_items as $item): ?>
                        <div class="portfolio-item">
                            <div class="portfolio-thumb"><?= $item['icon'] ?></div>
                            <div class="portfolio-info">
                                <div class="portfolio-title"><?= htmlspecialchars($item['titre']) ?></div>
                                <div class="portfolio-cat"><?= htmlspecialchars($item['description']) ?></div>
                            </div>
                            <div class="portfolio-overlay">Voir le projet</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card" style="margin-top:1.5rem;">
                    <div class="section-title">Documents (PDF)</div>
                    <form action="add_portfolio_file.php" method="post" enctype="multipart/form-data" style="display:flex;align-items:center;gap:1rem;background:#f8f5f0;padding:1.2rem 1rem;border-radius:1rem;margin-bottom:1rem;">
                        <div style="flex:2;">
                            <label style="font-weight:600;color:var(--marron);">Titre (optionnel)</label>
                            <input type="text" name="titre" placeholder="ex : Catalogue 2026" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                        </div>
                        <div style="flex:2;">
                            <label style="font-weight:600;color:var(--marron);">Fichier PDF</label>
                            <input type="file" name="portfolio_file" accept=".pdf" required style="width:100%;padding:.45rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                        </div>
                        <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
                    </form>
                    <table style="width:100%;border-collapse:separate;border-spacing:0 .75rem;">
                        <thead>
                            <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                <th>TITRE</th><th>FICHIER</th><th>DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($portfolioFiles)): ?>
                                <?php foreach ($portfolioFiles as $file): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($file['titre'] ?: $file['file_name']) ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" style="color:var(--marron);text-decoration:none;">Voir</a>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($file['created_at'] ?? 'now'))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; padding:1rem;">Aucun fichier ajoute</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Panel Expériences -->
            <div class="tab-panel" id="panel-experiences">
                <div class="card">
                    <div class="section-title">Parcours professionnel</div>
                    <?php if (count($experiences) > 0): ?>
                        <?php foreach ($experiences as $exp): ?>
                        <div class="exp-item">
                            <div class="exp-dot">💼</div>
                            <div>
                                <div class="exp-role"><?= htmlspecialchars($exp['poste'] ?? 'Expérience') ?></div>
                                <div class="exp-company"><?= htmlspecialchars($exp['entreprise'] ?? '') ?></div>
                                <div class="exp-period">
                                    <?= date('Y', strtotime($exp['date_debut'] ?? 'now')) ?> – 
                                    <?= isset($exp['date_fin']) && $exp['date_fin'] ? date('Y', strtotime($exp['date_fin'])) : 'Présent' ?>
                                </div>
                                <div class="exp-desc"><?= htmlspecialchars($exp['description'] ?? '') ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted" style="color: #888; text-align: center; padding: 1rem;">Aucune expérience renseignée</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Panel Avis -->
            <div class="tab-panel" id="panel-avis">
                <div class="card">
                    <div class="section-title">Avis clients & mentorés</div>
                    <?php if (count($avis) > 0): ?>
                        <?php foreach ($avis as $a): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-name"><?= htmlspecialchars($a['auteur'] ?? 'Anonyme') ?></div>
                                <div class="stars"><?= str_repeat('★', floor($a['note'] ?? 0)) . str_repeat('☆', 5 - floor($a['note'] ?? 0)) ?></div>
                            </div>
                            <div class="review-text">« <?= htmlspecialchars($a['commentaire'] ?? '') ?> »</div>
                            <div class="review-date"><?= date('F Y', strtotime($a['date_creation'] ?? 'now')) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted" style="color: #888; text-align: center; padding: 1rem;">Aucun avis pour le moment</p>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Section Compétences améliorée -->
            <div class="card" style="margin-bottom:2rem;">
              <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des compétences</h2>
              <div style="color:#888;margin-bottom:1.5rem;">Ajoutez, modifiez ou réorganisez les compétences affichées sur votre profil.</div>
              <!-- Formulaire d'ajout -->
                            <form action="add_competence.php" method="post" style="display:flex;align-items:center;gap:1rem;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                                <div style="flex:2;">
                  <label style="font-weight:600;color:var(--marron);">Nom</label>
                                    <input type="text" id="competence-nom" name="nom" required placeholder="ex : Tournage, Raku, Émaillage…" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                </div>
                                <div style="flex:3;">
                                    <label style="font-weight:600;color:var(--marron);">Description</label>
                                    <input type="text" name="description" placeholder="ex : Techniques et materiaux" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                <div style="flex:1;">
                                    <label style="font-weight:600;color:var(--marron);">Niveau (0-100)</label>
                                    <input type="range" min="0" max="100" value="50" id="competence-niveau" name="niveau" style="width:100%;accent-color:var(--marron);">
                </div>
                                <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
              </form>
              <!-- Tableau compétences -->
              <table style="width:100%;border-collapse:separate;border-spacing:0 1rem;">
                <thead>
                                    <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                        <th></th><th>COMPÉTENCE</th><th>DESCRIPTION</th><th>NIVEAU</th><th>BARRE</th><th>CATEGORIE</th><th>ACTIONS</th>
                                    </tr>
                </thead>
                <tbody id="competences-list">
                  <?php if (!empty($competences)): ?>
                    <?php foreach ($competences as $c): ?>
                                        <tr data-id="<?= $c['id_competence'] ?>">
                      <td style="text-align:center;">
                        <button class="ordre-btn up" type="button">▲</button>
                        <button class="ordre-btn down" type="button">▼</button>
                      </td>
                      <td class="comp-nom"><?= htmlspecialchars($c['nom_competence']) ?></td>
                                            <td class="comp-desc"><?= htmlspecialchars($c['description'] ?? '') ?></td>
                      <td class="comp-niveau"><?= (int)($c['niveau'] ?? 0) ?>%</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" style="width:<?= (int)($c['niveau'] ?? 0) ?>%;"></div>
                        </div>
                      </td>
                      <td class="comp-cat">
                        <?php $niveauComp = (int)($c['niveau'] ?? 0); ?>
                        <?php if ($niveauComp >= 80): ?>
                          <span class="badge-advanced">Avancé</span>
                        <?php elseif ($niveauComp >= 50): ?>
                          <span class="badge-intermediate">Intermédiaire</span>
                        <?php else: ?>
                          <span class="badge-beginner">Débutant</span>
                        <?php endif; ?>
                      </td>
                      <td>
                                                <form action="delete_competance.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette competence ?');">
                                                    <input type="hidden" name="id" value="<?= (int)$c['id_competence'] ?>">
                                                    <button class="suppr-btn" type="submit">🗑️</button>
                                                </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="7" style="text-align:center; padding:1rem;">Aucune compétence ajoutée</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <!-- Section Certifications améliorée -->
            <div class="card" style="margin-bottom:2rem;">
              <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des certifications</h2>
              <div style="color:#888;margin-bottom:1.5rem;">Ajoutez, modifiez ou supprimez vos certifications.</div>
              <!-- Formulaire d'ajout -->
                            <form action="add_certification.php" method="post" style="display:flex;align-items:center;gap:1rem;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                <div style="flex:2;">
                  <label style="font-weight:600;color:var(--marron);">Nom de la certification</label>
                                    <input type="text" id="certification-nom" name="nom" required placeholder="ex : Certification en poterie" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                </div>
                <div style="flex:1;">
                                    <label style="font-weight:600;color:var(--marron);">Niveau (0-100)</label>
                                    <input type="range" min="0" max="100" value="50" id="certification-niveau" name="niveau" style="width:100%;accent-color:var(--marron);">
                </div>
                                <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
              </form>
              <!-- Tableau certifications -->
              <table style="width:100%;border-collapse:separate;border-spacing:0 1rem;">
                <thead>
                  <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                    <th></th><th>CERTIFICATION</th><th>NIVEAU</th><th>BARRE</th><th>CATEGORIE</th><th>ACTIONS</th>
                  </tr>
                </thead>
                <tbody id="certifications-list">
                  <?php if (!empty($certifications)): ?>
                    <?php foreach ($certifications as $cert): ?>
                    <tr data-id="<?= $cert['id_certification'] ?>">
                      <td style="text-align:center;">
                        <button class="ordre-btn up" type="button">▲</button>
                        <button class="ordre-btn down" type="button">▼</button>
                      </td>
                      <td class="cert-nom"><?= htmlspecialchars($cert['nom_certification']) ?></td>
                      <td class="cert-niveau"><?= (int)($cert['niveau'] ?? 0) ?>%</td>
                      <td>
                        <div class="progress-bar">
                          <div class="progress-fill" style="width:<?= (int)($cert['niveau'] ?? 0) ?>%;"></div>
                        </div>
                      </td>
                      <td class="cert-cat">
                        <?php $niveau = (int)($cert['niveau'] ?? 0); ?>
                        <?php if ($niveau >= 80): ?>
                          <span class="badge-advanced">Avancé</span>
                        <?php elseif ($niveau >= 50): ?>
                          <span class="badge-intermediate">Intermédiaire</span>
                        <?php else: ?>
                          <span class="badge-beginner">Débutant</span>
                        <?php endif; ?>
                      </td>
                      <td>
                                                <form action="delete_certification.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette certification ?');">
                                                    <input type="hidden" name="id" value="<?= (int)$cert['id_certification'] ?>">
                                                    <button class="suppr-btn" type="submit">🗑️</button>
                                                </form>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; padding:1rem;">Aucune certification ajoutée</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
                        <!-- Section Expériences améliorée -->
                        <div class="card" style="margin-bottom:2rem;">
                            <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des expériences</h2>
                            <div style="color:#888;margin-bottom:1.5rem;">Ajoutez ou supprimez vos expériences.</div>
                            <form action="add_experience.php" method="post" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                                <div style="flex:2;min-width:180px;">
                                    <label style="font-weight:600;color:var(--marron);">Poste</label>
                                    <input type="text" name="poste" required placeholder="ex : Artisan potier" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <div style="flex:2;min-width:180px;">
                                    <label style="font-weight:600;color:var(--marron);">Entreprise</label>
                                    <input type="text" name="entreprise" placeholder="ex : Atelier Zellige" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <div style="flex:1;min-width:140px;">
                                    <label style="font-weight:600;color:var(--marron);">Début</label>
                                    <input type="date" name="date_debut" required style="width:100%;padding:.45rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <div style="flex:1;min-width:140px;">
                                    <label style="font-weight:600;color:var(--marron);">Fin</label>
                                    <input type="date" name="date_fin" style="width:100%;padding:.45rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <div style="flex:3;min-width:220px;">
                                    <label style="font-weight:600;color:var(--marron);">Description</label>
                                    <input type="text" name="description" placeholder="ex : Responsabilités et missions" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
                            </form>
                            <table style="width:100%;border-collapse:separate;border-spacing:0 1rem;">
                                <thead>
                                    <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                        <th>POSTE</th><th>ENTREPRISE</th><th>PÉRIODE</th><th>DESCRIPTION</th><th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($experiences)): ?>
                                        <?php foreach ($experiences as $exp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($exp['poste'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($exp['entreprise'] ?? '') ?></td>
                                            <?php $dateFin = $exp['date_fin'] ?? ''; ?>
                                            <td><?= htmlspecialchars($exp['date_debut'] ?? '') ?> - <?= $dateFin ? htmlspecialchars($dateFin) : 'Présent' ?></td>
                                            <td><?= htmlspecialchars($exp['description'] ?? '') ?></td>
                                            <td>
                                                <form action="delete_experience.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette expérience ?');">
                                                    <input type="hidden" name="id" value="<?= (int)($exp['id_experience'] ?? 0) ?>">
                                                    <button class="suppr-btn" type="submit">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" style="text-align:center; padding:1rem;">Aucune expérience ajoutée</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
        </main>
    </div>
    <!-- MODAL MODIFICATION -->
    <div class="modal-overlay" id="modal-edit" style="display:none">
        <div class="modal">
            <div class="modal-header">
                <h2>✏️ Modifier mon profil</h2>
                <button type="button" class="modal-close" onclick="closeModal('edit')">✕</button>
            </div>
            <form action="profilcontroller.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Spécialité</label>
                    <input type="text" name="specialite" value="<?= htmlspecialchars($specialite ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" value="<?= htmlspecialchars($telephone ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Ville</label>
                    <input type="text" name="ville" value="<?= htmlspecialchars($ville ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio"><?= htmlspecialchars($bio ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Expérience</label>
                    <textarea name="experience" placeholder="Décrivez votre expérience professionnelle..."><?= htmlspecialchars($user['experience'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Portfolio (URL)</label>
                    <input type="url" name="portfolio" value="<?= htmlspecialchars($portfolio_url ?? '') ?>">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('edit')">Annuler</button>
                    <button type="submit" class="btn-primary">💾 Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>
    <style>
    .card {box-shadow:0 2px 16px #0001;border-radius:1.2rem;padding:2rem 2.5rem;background:#fff;}
    .ordre-btn {background:none;border:none;font-size:1.1rem;cursor:pointer;color:#bfa77a;margin:0 2px;}
    .modif-btn, .suppr-btn {border:none;border-radius:.5rem;padding:.3rem .8rem;margin:0 2px;cursor:pointer;}
    .modif-btn {background:#ececff;color:var(--marron);}
    .suppr-btn {background:#ffeaea;color:#b00;}
    .badge-advanced {background:#e6f7e6;color:#2e7d32;padding:.2rem .7rem;border-radius:1rem;font-size:.9rem;}
    .badge-intermediate {background:#ececff;color:var(--marron);padding:.2rem .7rem;border-radius:1rem;font-size:.9rem;}
    .badge-beginner {background:#f8f5f0;color:var(--marron);padding:.2rem .7rem;border-radius:1rem;font-size:.9rem;}
    .progress-bar {background:#eee;width:100px;height:8px;border-radius:4px;overflow:hidden;}
    .progress-fill {background:var(--marron);height:100%;border-radius:4px;}
    .flash {margin-bottom:1rem;padding:.8rem 1rem;border-radius:.8rem;}
    .flash-success {background:#e8f5e9;color:#2e7d32;}
    .flash-error {background:#ffebee;color:#b71c1c;}
    </style>
</body>
</html>