<?php
// views/profil/index.php
// Variables attendues du controller :
// $user, $stats, $competences, $certifications, $experiences, $portfolioFiles, $avis, $flash
$baseUrl = app_url();
$profileCssVersion = (string)(@filemtime(__DIR__ . '/../../public/css/style.css') ?: time());
$profileJsVersion = (string)(@filemtime(__DIR__ . '/../../public/js/index.js') ?: time());
$cvBuilderJsVersion = (string)(@filemtime(__DIR__ . '/../../public/js/cv-builder.js') ?: time());
$chatbotJsVersion = (string)(@filemtime(__DIR__ . '/../../public/js/chatbot-widget.js') ?: time());
$bioHandlerJsVersion = (string)(@filemtime(__DIR__ . '/../../public/js/bio-handler.js') ?: time());
$chatbotProfileId = (int)($user['id_user'] ?? ($_SESSION['user_id'] ?? 0));
$cvSeedData = [
    'personal' => [
        'fullName' => trim((string)(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))),
        'title' => (string)($specialite ?? ''),
        'email' => (string)($email ?? ''),
        'phone' => (string)($telephone ?? ''),
        'city' => (string)($ville ?? ''),
        'summary' => (string)($cvSummary ?? '')
    ],
    'skills' => array_map(static function (array $item): array {
        return [
            'name' => (string)($item['nom_competence'] ?? ''),
            'level' => (int)($item['niveau'] ?? 50)
        ];
    }, (array)($competences ?? [])),
    'experiences' => array_map(static function (array $item): array {
        return [
            'role' => (string)($item['poste'] ?? ''),
            'company' => (string)($item['entreprise'] ?? ''),
            'start' => (string)($item['date_debut'] ?? ''),
            'end' => (string)($item['date_fin'] ?? ''),
            'description' => (string)($item['description'] ?? '')
        ];
    }, (array)($experiences ?? [])),
    'education' => array_map(static function (array $item): array {
        return [
            'degree' => (string)($item['nom_certification'] ?? 'Certification'),
            'school' => 'Certification professionnelle',
            'start' => '',
            'end' => '',
            'description' => 'Niveau: ' . (int)($item['niveau'] ?? 0) . '%'
        ];
    }, (array)($certifications ?? [])),
    'scores' => [
        'ats' => (int)($profileScore ?? 0),
        'impact' => (int)($popularityScore ?? 0),
        'readability' => 78
    ],
    'tips' => [],
    'language' => 'fr',
    'template' => 'moderne',
    'primaryColor' => '#2E6B3E'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Professionnel — حرفة Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css?v=' . $profileCssVersion)) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/cv-studio.css?v=' . time())) ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/cv-print.css?v=' . time())) ?>" media="print, screen">
    <script src="<?= htmlspecialchars(app_url('/public/js/index.js?v=' . $profileJsVersion)) ?>" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>
    <script src="<?= htmlspecialchars(app_url('/public/js/cv-builder.js?v=' . $cvBuilderJsVersion)) ?>" defer></script>
    <script>
        window.ChatbotConfig = {
            endpoint: <?= json_encode(app_url('/profil/chatbot')) ?>,
            profilId: <?= (int)$chatbotProfileId ?>,
            provider: 'ollama'
        };
    </script>
    <script src="<?= htmlspecialchars(app_url('/public/js/chatbot-widget.js?v=' . $chatbotJsVersion)) ?>" defer></script>
    <script src="<?= htmlspecialchars(app_url('/public/js/bio-handler.js?v=' . $bioHandlerJsVersion)) ?>" defer></script>
    <script type="application/json" id="cv-ai-seed">
<?= json_encode($cvSeedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
    </script>
</head>
<body>
    <!-- HEADER -->
    <header>
        <div class="logo">
            <span class="ar">حرفة</span>
            <span style="color:#aaa;font-size:.8rem;font-weight:400;margin-left:2px">Tunisie</span>
        </div>
        <nav>
            <a href="<?= htmlspecialchars(app_url('/dashboard')) ?>">🏠 Accueil</a>
            <a href="<?= htmlspecialchars(app_url('/profil')) ?>">🪪 Mon Profil</a>
            <a href="<?= htmlspecialchars(app_url('/dashboard/annuaire')) ?>">👥 Annuaire</a>
            <a href="#">💡 Projets</a>
            <a href="#">🎓 Formations</a>
            <a href="#">📈 Investissement</a>
            <a href="<?= htmlspecialchars(app_url('/admin')) ?>">🛡️ Admin</a>
            <button class="btn-logout" onclick="handleLogout()">Déconnexion</button>
        </nav>
    </header>
    <!-- PAGE PROFIL -->
    <div class="page" id="section-profil">
        <!-- En-tête de page -->
        <div class="page-header">
            <h1>Mon <span>Profil Professionnel</span></h1>
            <nav class="breadcrumb">
                <a href="<?= htmlspecialchars(app_url('/dashboard')) ?>">Accueil</a> › Profil Professionnel
            </nav>
        </div>
        <?php if ($flash && !empty($flash['msg'])): ?>
            <div class="card flash flash-<?= $flash['type'] ?>">
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
                    <?php if (trim((string)$bio) !== ''): ?>
                        <div class="profile-bio"><?= nl2br(htmlspecialchars((string)$bio)) ?></div>
                    <?php endif; ?>
                    <div class="profile-title"><?= htmlspecialchars($specialite) ?></div>
                    <div class="profile-location">📍 <?= htmlspecialchars($ville) ?></div>
                    <?php
                        $disponibiliteBadgeClass = 'green';
                        if (($disponibilite ?? 'disponible') === 'occupe') {
                            $disponibiliteBadgeClass = 'orange';
                        } elseif (($disponibilite ?? 'disponible') === 'indisponible') {
                            $disponibiliteBadgeClass = 'red';
                        }
                    ?>
                    <div class="badge-row">
                        <span class="badge">Entrepreneur</span>
                        <span class="badge <?= htmlspecialchars($disponibiliteBadgeClass) ?>"><?= htmlspecialchars($disponibiliteLabel ?? 'Disponible') ?></span>
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
            <div class="card">
                <div class="section-title">PPÉI</div>
                <div class="stats-row" style="margin-top:.3rem;">
                    <div class="stat-item">
                        <div class="stat-num"><?= (int)$profileScore ?>%</div>
                        <div class="stat-label">Score</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-num"><?= (int)$completionScore ?>%</div>
                        <div class="stat-label">Complétion</div>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat-item">
                        <div class="stat-num"><?= (int)$popularityScore ?>%</div>
                        <div class="stat-label">Impact</div>
                    </div>
                </div>
                <div id="ppei-summary-sidebar" style="margin-top:.9rem;line-height:1.6;color:var(--marron);font-size:.95rem;">
                    <?= htmlspecialchars((string)$professionalSummary) ?>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.9rem;">
                    <span class="badge <?= $isTrending ? 'green' : 'orange' ?>"><?= $isTrending ? 'Profil en tendance' : 'Potentiel à renforcer' ?></span>
                    <span class="badge green"><?= (int)$suggestedOpportunities ?> opportunités</span>
                    <span class="badge"><?= (int)count($skillGaps) ?> écarts de compétences</span>
                </div>
            </div>
            <!-- Disponibilité -->
            <div class="card">
                <div class="section-title">Disponibilité</div>
                <div class="availability-row availability-<?= htmlspecialchars($disponibilite ?? 'disponible') ?>" data-availability-visitor="true">
                    <div class="avail-dot avail-dot-<?= htmlspecialchars($disponibilite ?? 'disponible') ?>" data-visitor-dot="true"></div>
                    <div class="avail-text avail-text-<?= htmlspecialchars($disponibilite ?? 'disponible') ?>" data-visitor-label="true"><?= htmlspecialchars($disponibiliteLabel ?? 'Disponible') ?></div>
                    <div class="avail-sub" data-visitor-hours="true"><?= htmlspecialchars($disponibilite_horaire ?? 'Lun - Sam · 8h-17h') ?></div>
                </div>
                <div class="avail-note" data-visitor-message="true" <?= trim((string)($disponibilite_message ?? '')) === '' ? 'style="display:none;"' : '' ?>>
                    <?= htmlspecialchars($disponibilite_message ?? '') ?>
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
                    <div class="tab" onclick="switchTab('ppei')">🧠 PPÉI</div>
                    <div class="tab active" onclick="switchTab('bio')">📋 Bio</div>
                    <div class="tab" onclick="switchTab('portfolio')">🖼️ Portfolio</div>
                    <div class="tab" onclick="switchTab('experiences')">💼 Expériences</div>
                    <div class="tab" onclick="switchTab('avis')">⭐ Avis</div>
                </div>
            </div>
            <div class="tab-panel" id="panel-ppei">
                <div class="card">
                    <div class="section-title">PPÉI - Profil Professionnel Évolutif Intelligent</div>
                    <div class="stats-row" style="margin-top:.5rem;">
                        <div class="stat-item">
                            <div class="stat-num"><?= (int)$profileScore ?>%</div>
                            <div class="stat-label">Score global</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-num"><?= (int)$completionScore ?>%</div>
                            <div class="stat-label">Profil complété</div>
                        </div>
                        <div class="stat-divider"></div>
                        <div class="stat-item">
                            <div class="stat-num"><?= (int)$popularityScore ?>%</div>
                            <div class="stat-label">Impact marché</div>
                        </div>
                    </div>

                    <div id="ppei-summary-panel" style="margin-top:1rem;padding:1rem;border-radius:1rem;background:#f8f5f0;border:1px solid #eee0cb;line-height:1.8;color:var(--marron);">
                        <?= htmlspecialchars((string)$professionalSummary) ?>
                    </div>

                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-top:1rem;">
                        <div style="padding:1rem;border-radius:1rem;background:#fff;border:1px solid #eee0cb;">
                            <div class="section-title" style="margin-top:0;">Compétences à renforcer</div>
                            <?php if (!empty($skillGaps)): ?>
                                <ul style="margin:.5rem 0 0;padding-left:1.2rem;line-height:1.7;">
                                    <?php foreach (array_slice((array)$skillGaps, 0, 4) as $gap): ?>
                                        <li><?= htmlspecialchars((string)$gap) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="empty-text" style="margin-bottom:0;">Aucun écart critique détecté.</p>
                            <?php endif; ?>
                        </div>
                        <div style="padding:1rem;border-radius:1rem;background:#fff;border:1px solid #eee0cb;">
                            <div class="section-title" style="margin-top:0;">Pistes de carrière</div>
                            <?php if (!empty($careerPaths)): ?>
                                <ul style="margin:.5rem 0 0;padding-left:1.2rem;line-height:1.7;">
                                    <?php foreach (array_slice((array)$careerPaths, 0, 4) as $path): ?>
                                        <li><?= htmlspecialchars((string)$path) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="empty-text" style="margin-bottom:0;">Aucune piste prioritaire détectée.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="margin-top:1rem;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
                        <div style="padding:1rem;border-radius:1rem;background:#fff7ee;border:1px solid #eee0cb;">
                            <div class="section-title" style="margin-top:0;">Jobs suggérés</div>
                            <?php if (!empty($suggestedJobs)): ?>
                                <div style="display:flex;flex-wrap:wrap;gap:.45rem;">
                                    <?php foreach (array_slice((array)$suggestedJobs, 0, 6) as $job): ?>
                                        <span class="badge green"><?= htmlspecialchars((string)$job) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="empty-text" style="margin-bottom:0;">Aucune suggestion disponible.</p>
                            <?php endif; ?>
                        </div>
                        <div style="padding:1rem;border-radius:1rem;background:#fff;border:1px solid #eee0cb;">
                            <div class="section-title" style="margin-top:0;">État du profil</div>
                            <p style="margin:.4rem 0 0;line-height:1.7;">
                                <?= $isTrending ? 'Votre profil montre une dynamique forte sur le marché.' : 'Votre profil est solide, mais certains signaux peuvent encore progresser.' ?>
                            </p>
                            <p style="margin:.75rem 0 0;color:#8b5a3a;">
                                Dernière synchronisation : <?= htmlspecialchars((string)($insightLastCalcAt ?? 'mise à jour à la volée')) ?>
                            </p>
                        </div>
                    </div>

                            <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:.55rem;">
                                        <button type="button" id="ppei-generate-btn" class="cv-ia-mini-btn" onclick="if(document.getElementById('bio-generate')) { document.getElementById('bio-generate').click(); switchTab('bio'); } else { alert('Allez dans l\"onglet Bio pour générer'); }">🚀 Générer bio professionnelle</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="window.location.href='<?= htmlspecialchars(app_url('/profil/gestion_competences')) ?>';">💡 Compétences</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="window.location.href='<?= htmlspecialchars(app_url('/profil/gestion_certifications')) ?>';">🎓 Certifications</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="switchTab('experiences');document.querySelector('#panel-experiences .card').scrollIntoView({behavior:'smooth',block:'start'});">💼 Expériences</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="switchTab('portfolio');document.querySelector('[data-doc-tools=\"true\"]').scrollIntoView({behavior:'smooth',block:'start'});">🖼️ Portfolio</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="switchTab('bio');document.getElementById('cv-ia-studio').scrollIntoView({behavior:'smooth',block:'start'});">🧠 IA intégrée</button>
                                <button type="button" class="cv-ia-mini-btn" onclick="document.querySelector('[data-completion-submit=\"true\"]').click();">📈 Recalcul IA</button>
                            </div>
                </div>
            </div>
            <!-- Panel Bio -->
            <div class="tab-panel active" id="panel-bio">
                <div class="card">
                    <div class="section-title">À propos</div>
                    <?php $hasBio = trim((string)$bio) !== ''; ?>
                    <!-- Zone d'affichage de la bio -->
                    <div id="bio-display" style="margin-bottom:1rem; padding:1rem; background:#f8f5f0; border-radius:0.75rem;">
                        <div style="font-weight:600; color:var(--vert); margin-bottom:0.5rem;">📝 Bio professionnelle</div>
                        <div id="bio-text" style="line-height:1.6; white-space:pre-wrap;">
                            <?= $hasBio ? htmlspecialchars((string)$bio) : 'Aucune bio pour le moment. Cliquez sur "Générer" pour en créer une.' ?>
                        </div>
                    </div>

                    <!-- Zone d'édition (cachée par défaut) -->
                    <div id="bio-edit" style="display:none;">
                        <textarea id="bio-textarea" rows="4" style="width:100%; padding:0.75rem; border-radius:0.75rem; border:1px solid #e0d6c3;"><?= $hasBio ? htmlspecialchars((string)$bio) : '' ?></textarea>
                        <button id="bio-save" class="btn-secondary" style="margin-top:0.5rem;">💾 Sauvegarder</button>
                        <button id="bio-cancel" class="btn-secondary" style="margin-top:0.5rem;">❌ Annuler</button>
                    </div>

                    <button id="bio-edit-btn" class="btn-secondary" style="margin-bottom:1rem;">✏️ Modifier la bio</button>
                    <button id="bio-generate" class="btn-primary">🚀 Générer une version professionnelle (Ollama)</button>

                    <div id="bio-status" style="margin-top:0.75rem; font-size:0.9rem; color:#666;"></div>
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

                <div class="card smart-insights-card" id="smart-insights-panel">
                    <div class="section-title">Smart Insights Panel</div>
                    <form method="post" action="<?= htmlspecialchars(app_url('/profil/recalculateCompletion')) ?>" data-completion-form="true" style="display:flex;justify-content:flex-end;margin:-.2rem 0 .85rem;">
                        <button type="submit" class="btn-primary" data-completion-submit="true">Calculer ma progression</button>
                    </form>
                    <div class="smart-insights-grid">
                        <div class="smart-kpi smart-kpi-score">
                            <div class="smart-kpi-label">Progression du profil</div>
                            <div class="smart-kpi-value" data-completion-value="true"><?= (int)$completionScore ?>%</div>
                            <div class="smart-progress">
                                <div class="smart-progress-fill" data-completion-fill="true" style="width: <?= (int)$completionScore ?>%"></div>
                            </div>
                            <div class="smart-kpi-hint" data-completion-detail="true">
                                Description <?= !empty($completionBreakdown['description']['completed']) ? '✅' : '❌' ?> (+20%) ·
                                Competences <?= !empty($completionBreakdown['competences']['completed']) ? '✅' : '❌' ?> (+30%) ·
                                Portfolio <?= !empty($completionBreakdown['portfolio']['completed']) ? '✅' : '❌' ?> (+50%)
                            </div>
                        </div>

                        <div class="smart-kpi smart-kpi-trending">
                            <div class="smart-kpi-label">Popularite</div>
                            <div class="smart-kpi-value"><?= (int)$popularityScore ?>%</div>
                            <div class="smart-trending-line">
                                <?php if (!empty($isTrending)): ?>
                                    <span class="smart-pill is-hot">🔥 Trending</span>
                                <?php else: ?>
                                    <span class="smart-pill">Stable</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="smart-kpi smart-kpi-jobs">
                            <div class="smart-kpi-label">Opportunites suggerees</div>
                            <div class="smart-kpi-value">💼 <?= (int)$suggestedOpportunities ?> nouvelles</div>
                            <div class="smart-kpi-hint">Base sur completude, competences et engagement.</div>
                        </div>
                    </div>

                    <div class="smart-activity-card">
                        <div class="smart-activity-header">
                            <span>📈 Activite</span>
                            <span class="smart-activity-sub">Indicateurs du profil</span>
                        </div>
                        <div class="smart-bars-wrap" aria-label="Graphique d'activite">
                            <?php foreach ($activityBars as $bar): ?>
                                <div class="smart-bar-item">
                                    <div class="smart-bar-value"><?= (int)$bar['value'] ?></div>
                                    <div class="smart-bar-track">
                                        <div class="smart-bar-fill" style="height: <?= (int)$bar['height'] ?>%"></div>
                                    </div>
                                    <div class="smart-bar-label"><?= htmlspecialchars((string)$bar['label']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                

                <div class="card cv-ia-studio" id="cv-ia-studio" style="margin-top:1.5rem;">
                    <div class="cv-ia-header-row">
                        <div>
                            <div class="section-title" style="margin-bottom:.3rem;">🤖 CV Studio Intelligent avec IA intégrée</div>
                            <p class="cv-ia-subtitle">Générateur de CV intelligent alimenté par une IA locale. Personnalisez vos informations, générez avec IA, optimisez et téléchargez en PDF professionnel.</p>
                        </div>
                        <div class="cv-ia-badges">
                            <span class="cv-ia-badge" title="Moteur IA local">🧠 IA locale</span>
                            <span class="cv-ia-badge" title="ATS Optimized">✅ ATS</span>
                            <span class="cv-ia-badge" title="PDF Export">📄 PDF</span>
                            <span class="cv-ia-badge" title="Multi-Language">🌍 Multilingue</span>
                        </div>
                    </div>

                    <div class="cv-ia-actions-row">
                        <button type="button" class="cv-ia-btn cv-ia-btn-generate" id="cv-ia-generate">🚀 Générer avec IA intégrée</button>
                        <button type="button" class="cv-ia-btn cv-ia-btn-optimize" id="cv-ia-optimize">⚡ Optimiser IA</button>
                        <button type="button" class="cv-ia-btn cv-ia-btn-download" id="cv-ia-download">📥 Télécharger (PDF)</button>
                        <span class="cv-ia-status" id="cv-ia-status"></span>
                    </div>

                    <div class="cv-ia-layout">
                        <div class="cv-ia-editor">
                            <div class="cv-ia-tabs">
                                <button type="button" class="cv-ia-tab is-active" data-cv-tab="infos">ℹ️ Infos</button>
                                <button type="button" class="cv-ia-tab" data-cv-tab="skills">💡 Compétences</button>
                                <button type="button" class="cv-ia-tab" data-cv-tab="exp">💼 Experiences</button>
                                <button type="button" class="cv-ia-tab" data-cv-tab="design">🎨 Design & IA</button>
                            </div>

                            <div class="cv-ia-tab-panel is-active" data-cv-panel="infos">
                                <div class="cv-ia-grid-two">
                                    <div>
                                        <label>Nom complet *</label>
                                        <input type="text" id="cv-full-name" placeholder="Prénom Nom" required>
                                    </div>
                                    <div>
                                        <label>Titre professionnel *</label>
                                        <input type="text" id="cv-title" placeholder="Ex: Designer UX/UI" required>
                                    </div>
                                    <div>
                                        <label>Email</label>
                                        <input type="email" id="cv-email" placeholder="votremail@example.com">
                                    </div>
                                    <div>
                                        <label>Téléphone</label>
                                        <input type="tel" id="cv-phone" placeholder="+216...">
                                    </div>
                                </div>
                                <div style="margin-top:.75rem;">
                                    <label>Ville</label>
                                    <input type="text" id="cv-city" placeholder="Tunis">
                                </div>
                                <div style="margin-top:.75rem;">
                                    <label>Résumé professionnel</label>
                                    <textarea id="cv-summary" rows="5" placeholder="Décrivez vos qualifications, expériences clés et aspirations professionnelles..."></textarea>
                                </div>
                            </div>

                            <div class="cv-ia-tab-panel" data-cv-panel="skills">
                                <p style="font-size:.85rem;color:#666;margin-bottom:.75rem;">Ajoutez vos compétences professionnelles. L'IA locale les reformulera de manière plus impactante.</p>
                                <div class="cv-ia-inline-tools">
                                    <button type="button" class="cv-ia-mini-btn" id="cv-add-skill">➕ Ajouter compétence</button>
                                </div>
                                <div id="cv-skills-editor"></div>
                            </div>

                            <div class="cv-ia-tab-panel" data-cv-panel="exp">
                                <p style="font-size:.85rem;color:#666;margin-bottom:.75rem;">Entrez vos expériences et formations. L'IA les optimisera pour les recruteurs.</p>
                                <div class="cv-ia-inline-tools">
                                    <button type="button" class="cv-ia-mini-btn" id="cv-add-exp">➕ Ajouter expérience</button>
                                    <button type="button" class="cv-ia-mini-btn" id="cv-add-edu">➕ Ajouter certification</button>
                                </div>
                                <div class="cv-ia-subsection-title">Expériences professionnelles</div>
                                <div id="cv-experiences-editor"></div>
                                <div class="cv-ia-subsection-title" style="margin-top:1rem;">Formations & Certifications</div>
                                <div id="cv-education-editor"></div>
                            </div>

                            <div class="cv-ia-tab-panel" data-cv-panel="design">
                                <div class="cv-ia-grid-two">
                                    <div>
                                        <label>Template</label>
                                        <select id="cv-template">
                                            <option value="moderne">Moderne (recommandé)</option>
                                            <option value="classique">Classique</option>
                                            <option value="epure">Épuré</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Langue</label>
                                        <select id="cv-language">
                                            <option value="fr" selected>Français</option>
                                            <option value="en">English</option>
                                            <option value="ar">العربية</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="cv-ia-grid-two" style="margin-top:.75rem;">
                                    <div>
                                        <label>Modèle IA locale</label>
                                        <select id="cv-model">
                                            <option value="mistral" selected>Mistral (rapide)</option>
                                            <option value="llama3">Llama 3 (précis)</option>
                                            <option value="llama2">Llama 2</option>
                                            <option value="neural-chat">Neural Chat</option>
                                            <option value="starling-lm">Starling LM</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label>Couleur principale</label>
                                        <input type="color" id="cv-primary-color" value="#2E6B3E">
                                    </div>
                                </div>
                                <div style="margin-top:1rem;padding:.75rem;background:#f8f5f0;border-radius:.75rem;border-left:4px solid #2E6B3E;">
                                    <p style="font-size:.85rem;color:#555;"><strong>💡 Conseil:</strong> Le moteur IA local doit être lancé sur <code>localhost:11434</code> pour que la génération fonctionne.</p>
                                </div>
                            </div>
                        </div>

                        <div class="cv-ia-preview-wrap">
                            <div class="cv-ia-score-row" id="cv-score-row">
                                <div class="cv-score"><span class="cv-score-label">ATS</span> <strong>—</strong></div>
                                <div class="cv-score"><span class="cv-score-label">Impact</span> <strong>—</strong></div>
                                <div class="cv-score"><span class="cv-score-label">Lisibilité</span> <strong>—</strong></div>
                            </div>
                            <div class="cv-ia-tips" id="cv-ia-tips">
                                <div class="cv-tip">💡 Cliquez sur « Générer PPÉI (Ollama) » pour obtenir des conseils IA personnalisés.</div>
                            </div>
                            <div class="cv-ia-preview cv-template-moderne" id="cv-preview">
                                <p style="padding:2rem;text-align:center;color:#999;">Aperçu du CV apparaîtra ici...</p>
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
                    <form action="<?= htmlspecialchars(app_url('/profil/addPortfolioFile')) ?>" method="post" enctype="multipart/form-data" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:#f8f5f0;padding:1.2rem 1rem;border-radius:1rem;margin-bottom:1rem;">
                        <div style="flex:2;">
                            <label style="font-weight:600;color:var(--marron);">Titre (optionnel)</label>
                            <input type="text" name="titre" maxlength="120" placeholder="ex : Catalogue 2026" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                        </div>
                        <div style="flex:2;min-width:240px;">
                            <label style="font-weight:600;color:var(--marron);">Realisation</label>
                            <select name="realisation_default" data-realisation-select required style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                <option value="">Choisir une realisation</option>
                                <?php foreach (($defaultRealisations ?? []) as $realisationOption): ?>
                                    <option value="<?= htmlspecialchars((string)$realisationOption) ?>"><?= htmlspecialchars((string)$realisationOption) ?></option>
                                <?php endforeach; ?>
                                <option value="__custom__">Autre (nouvelle realisation)</option>
                            </select>
                            <input type="text" name="realisation_custom" maxlength="120" data-realisation-custom placeholder="Saisissez une nouvelle realisation" style="display:none;width:100%;padding:.5rem;margin-top:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                        </div>
                        <div style="flex:2;">
                            <label style="font-weight:600;color:var(--marron);">Fichier PDF</label>
                            <input type="file" name="portfolio_file" accept=".pdf" required style="width:100%;padding:.45rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                        </div>
                        <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
                    </form>
                    <div class="table-tools-row" data-doc-tools="true">
                        <input
                            type="text"
                            class="table-search-input"
                            placeholder="Rechercher un document ou une realisation..."
                            data-doc-search="true"
                        >
                        <select class="table-sort-select" data-doc-sort="true">
                            <option value="date-desc">Trier: Plus recents</option>
                            <option value="date-asc">Trier: Plus anciens</option>
                            <option value="titre-asc">Trier: Titre (A-Z)</option>
                            <option value="realisation-asc">Trier: Realisation (A-Z)</option>
                        </select>
                        <button type="button" class="stats-toggle-btn" data-doc-stats-toggle="true">📊 Statistiques</button>
                    </div>
                    <div class="table-stats-panel" data-doc-stats-panel="true" style="display:none;">
                        <div class="stats-chip"><span>Total</span><strong data-doc-stat-total="true">0</strong></div>
                        <div class="stats-chip"><span>Realisations</span><strong data-doc-stat-realisations="true">0</strong></div>
                        <div class="stats-chip"><span>Ajoutes cette annee</span><strong data-doc-stat-year="true">0</strong></div>
                        <div class="stats-chip"><span>Affiches</span><strong data-doc-stat-visible="true">0</strong></div>
                    </div>
                    <table style="width:100%;border-collapse:separate;border-spacing:0 .75rem;">
                        <thead>
                            <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                <th>TITRE</th><th>REALISATION</th><th>FICHIER</th><th>DATE</th>
                            </tr>
                        </thead>
                        <tbody id="portfolio-docs-list">
                            <?php if (!empty($portfolioFiles)): ?>
                                <?php foreach ($portfolioFiles as $index => $file): ?>
                                    <tr data-id="<?= (int)$index + 1 ?>" data-created-at="<?= htmlspecialchars((string)($file['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <td class="doc-title"><?= htmlspecialchars($file['titre'] ?: $file['file_name']) ?></td>
                                        <td class="doc-realisation"><?= htmlspecialchars((string)($file['realisation'] ?? '-')) ?></td>
                                        <td class="doc-file">
                                            <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" style="color:var(--marron);text-decoration:none;">Voir</a>
                                        </td>
                                        <td class="doc-date"><?= htmlspecialchars(date('d/m/Y', strtotime($file['created_at'] ?? 'now'))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; padding:1rem;">Aucun fichier ajouté</td></tr>
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
                            <div class="reviewer-name"><?= htmlspecialchars($a['prenom'] ?? 'Anonyme') ?> <?= htmlspecialchars($a['nom'] ?? '') ?></div>
                            <div class="stars"><?= str_repeat('★', floor($a['note'] ?? 0)) . str_repeat('☆', 5 - floor($a['note'] ?? 0)) ?></div>
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
            <div class="card portfolio-gestion" style="margin-bottom:2rem;display:none;">
              <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des compétences</h2>
              <div style="color:#888;margin-bottom:1.5rem;">Choisissez une compétence existante du catalogue ou ajoutez-en une nouvelle si elle n'existe pas encore.</div>
              <!-- Formulaire d'ajout -->
                <form action="<?= htmlspecialchars(app_url('/profil/addCompetence')) ?>" method="post" data-ajax-add="true" style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:1rem;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                    <div style="flex:2;min-width:220px;">
                        <label style="font-weight:600;color:var(--marron);">Catalogue des compétences</label>
                        <select name="competence_catalog_choice" style="width:100%;padding:.55rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                            <option value="">-- Choisir une compétence existante --</option>
                            <?php foreach (($competenceCatalog ?? []) as $catalog): ?>
                                <?php $catalogChoiceValue = !empty($catalog['id_competence_catalog']) ? 'id:' . (int)$catalog['id_competence_catalog'] : 'name:' . (string)($catalog['nom_competence'] ?? ''); ?>
                                <option value="<?= htmlspecialchars($catalogChoiceValue, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string)($catalog['nom_competence'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:2;min-width:220px;">
                        <label style="font-weight:600;color:var(--marron);">Ou nouvelle compétence</label>
                        <input type="text" id="competence-nom" name="nom" maxlength="80" placeholder="ex : Tournage, Raku, Émaillage…" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                    </div>
                    <div style="flex:3;min-width:260px;">
                        <label style="font-weight:600;color:var(--marron);">Description</label>
                        <input type="text" name="description" maxlength="500" placeholder="ex : Techniques et matériaux" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                    </div>
                    <div style="flex:1;min-width:180px;">
                        <label style="font-weight:600;color:var(--marron);">Niveau (0-100)</label>
                        <input type="range" min="0" max="100" value="50" id="competence-niveau" name="niveau" style="width:100%;accent-color:var(--marron);">
                    </div>
                    <div style="flex-basis:100%;font-size:.85rem;color:#7b6a58;">
                        Si vous choisissez une compétence du catalogue, le nom et la description peuvent être repris automatiquement.
                    </div>
                    <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
              </form>
                            <div class="table-tools-row" data-comp-tools="true">
                                <input
                                    type="text"
                                    class="table-search-input"
                                    placeholder="Rechercher une competence ou description..."
                                    data-comp-search="true"
                                >
                                <select class="table-sort-select" data-comp-sort="true">
                                    <option value="niveau-desc">Trier: Niveau (decroissant)</option>
                                    <option value="niveau-asc">Trier: Niveau (croissant)</option>
                                    <option value="nom-asc">Trier: Nom (A-Z)</option>
                                    <option value="nom-desc">Trier: Nom (Z-A)</option>
                                </select>
                                <button type="button" class="stats-toggle-btn" data-comp-stats-toggle="true">📊 Statistiques</button>
                            </div>
                            <div class="table-stats-panel" data-comp-stats-panel="true" style="display:none;">
                                <div class="stats-chip"><span>Total</span><strong data-comp-stat-total="true">0</strong></div>
                                <div class="stats-chip"><span>Niveau moyen</span><strong data-comp-stat-average="true">0%</strong></div>
                                <div class="stats-chip"><span>Avancees</span><strong data-comp-stat-advanced="true">0</strong></div>
                                <div class="stats-chip"><span>Affichees</span><strong data-comp-stat-visible="true">0</strong></div>
                            </div>
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
                                                <button class="modif-btn" type="button" title="Modifier" onclick="editCompetence(<?= (int)$c['id_competence'] ?>)">✏️</button>
                                                <form id="edit-comp-<?= (int)$c['id_competence'] ?>" action="<?= htmlspecialchars(app_url('/profil/updateCompetence')) ?>" method="post" style="display:none;">
                                                    <input type="hidden" name="id" value="<?= (int)$c['id_competence'] ?>">
                                                    <input type="hidden" name="nom" value="<?= htmlspecialchars((string)($c['nom_competence'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($c['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="niveau" value="<?= (int)($c['niveau'] ?? 0) ?>">
                                                </form>
                                                <form action="<?= htmlspecialchars(app_url('/profil/deleteCompetence')) ?>" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette compétence ?');">
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
            <div class="card portfolio-gestion" style="margin-bottom:2rem;display:none;">
              <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des certifications</h2>
              <div style="color:#888;margin-bottom:1.5rem;">Ajoutez, modifiez ou supprimez vos certifications.</div>
              <!-- Formulaire d'ajout -->
                                                        <form action="<?= htmlspecialchars(app_url('/profil/addCertification')) ?>" method="post" data-ajax-add="true" style="display:flex;align-items:center;gap:1rem;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                <div style="flex:2;">
                  <label style="font-weight:600;color:var(--marron);">Nom de la certification</label>
                                    <input type="text" id="certification-nom" name="nom" minlength="2" maxlength="100" required placeholder="ex : Certification en poterie" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                </div>
                <div style="flex:1;">
                                    <label style="font-weight:600;color:var(--marron);">Niveau (0-100)</label>
                                    <input type="range" min="0" max="100" value="50" id="certification-niveau" name="niveau" style="width:100%;accent-color:var(--marron);">
                </div>
                                <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
              </form>
                            <div class="table-tools-row" data-cert-tools="true">
                                <input
                                    type="text"
                                    class="table-search-input"
                                    placeholder="Rechercher une certification..."
                                    data-cert-search="true"
                                >
                                <select class="table-sort-select" data-cert-sort="true">
                                    <option value="niveau-desc">Trier: Niveau (decroissant)</option>
                                    <option value="niveau-asc">Trier: Niveau (croissant)</option>
                                    <option value="nom-asc">Trier: Nom (A-Z)</option>
                                    <option value="nom-desc">Trier: Nom (Z-A)</option>
                                </select>
                                <button type="button" class="stats-toggle-btn" data-cert-stats-toggle="true">📊 Statistiques</button>
                            </div>
                            <div class="table-stats-panel" data-cert-stats-panel="true" style="display:none;">
                                <div class="stats-chip"><span>Total</span><strong data-cert-stat-total="true">0</strong></div>
                                <div class="stats-chip"><span>Niveau moyen</span><strong data-cert-stat-average="true">0%</strong></div>
                                <div class="stats-chip"><span>Avancees</span><strong data-cert-stat-advanced="true">0</strong></div>
                                <div class="stats-chip"><span>Affichees</span><strong data-cert-stat-visible="true">0</strong></div>
                            </div>
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
                                                <button class="modif-btn" type="button" title="Modifier" onclick="editCertification(<?= (int)$cert['id_certification'] ?>)">✏️</button>
                                                <form id="edit-cert-<?= (int)$cert['id_certification'] ?>" action="<?= htmlspecialchars(app_url('/profil/updateCertification')) ?>" method="post" style="display:none;">
                                                    <input type="hidden" name="id" value="<?= (int)$cert['id_certification'] ?>">
                                                    <input type="hidden" name="nom" value="<?= htmlspecialchars((string)($cert['nom_certification'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="niveau" value="<?= (int)($cert['niveau'] ?? 0) ?>">
                                                </form>
                                                <form action="<?= htmlspecialchars(app_url('/profil/deleteCertification')) ?>" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette certification ?');">
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
                        <div class="card portfolio-gestion" style="margin-bottom:2rem;display:none;">
                            <h2 style="font-size:2rem;margin-bottom:.5rem;">Gestion des expériences</h2>
                            <div style="color:#888;margin-bottom:1.5rem;">Ajoutez, modifiez ou supprimez vos expériences.</div>
                            <form action="<?= htmlspecialchars(app_url('/profil/addExperience')) ?>" method="post" data-ajax-add="true" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;background:#f8f5f0;padding:1.5rem 1rem;border-radius:1rem;margin-bottom:1.5rem;">
                                <div style="flex:2;min-width:180px;">
                                    <label style="font-weight:600;color:var(--marron);">Poste</label>
                                    <input type="text" name="poste" minlength="2" maxlength="100" required placeholder="ex : Artisan potier" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <div style="flex:2;min-width:180px;">
                                    <label style="font-weight:600;color:var(--marron);">Entreprise</label>
                                    <input type="text" name="entreprise" maxlength="120" placeholder="ex : Atelier Zellige" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
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
                                    <input type="text" name="description" maxlength="1000" placeholder="ex : Responsabilités et missions" style="width:100%;padding:.5rem;border-radius:.5rem;border:1px solid #e0d6c3;background:#fff7ee;">
                                </div>
                                <button type="submit" style="background:var(--marron);color:#fff;padding:.7rem 1.5rem;border:none;border-radius:.5rem;font-weight:600;font-size:1rem;box-shadow:0 2px 8px #8b5a3a22;transition:.2s;">+ Ajouter</button>
                            </form>
                            <div class="table-tools-row" data-exp-tools="true">
                                <input
                                    type="text"
                                    class="table-search-input"
                                    placeholder="Rechercher un poste, entreprise ou description..."
                                    data-exp-search="true"
                                >
                                <select class="table-sort-select" data-exp-sort="true">
                                    <option value="date-desc">Trier: Plus recentes</option>
                                    <option value="date-asc">Trier: Plus anciennes</option>
                                    <option value="poste-asc">Trier: Poste (A-Z)</option>
                                    <option value="entreprise-asc">Trier: Entreprise (A-Z)</option>
                                </select>
                                <button type="button" class="stats-toggle-btn" data-exp-stats-toggle="true">📊 Statistiques</button>
                            </div>
                            <div class="table-stats-panel" data-exp-stats-panel="true" style="display:none;">
                                <div class="stats-chip"><span>Total</span><strong data-exp-stat-total="true">0</strong></div>
                                <div class="stats-chip"><span>En cours</span><strong data-exp-stat-active="true">0</strong></div>
                                <div class="stats-chip"><span>Entreprises</span><strong data-exp-stat-companies="true">0</strong></div>
                                <div class="stats-chip"><span>Affichees</span><strong data-exp-stat-visible="true">0</strong></div>
                            </div>
                            <table style="width:100%;border-collapse:separate;border-spacing:0 1rem;">
                                <thead>
                                    <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                        <th>POSTE</th><th>ENTREPRISE</th><th>PÉRIODE</th><th>DESCRIPTION</th><th>ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody id="experiences-list">
                                    <?php if (!empty($experiences)): ?>
                                        <?php foreach ($experiences as $exp): ?>
                                        <tr data-id="<?= (int)($exp['id_experience'] ?? 0) ?>">
                                            <td class="exp-poste"><?= htmlspecialchars($exp['poste'] ?? '') ?></td>
                                            <td class="exp-entreprise"><?= htmlspecialchars($exp['entreprise'] ?? '') ?></td>
                                            <?php $dateFin = $exp['date_fin'] ?? ''; ?>
                                            <td class="exp-periode"><?= htmlspecialchars($exp['date_debut'] ?? '') ?> - <?= $dateFin ? htmlspecialchars($dateFin) : 'Présent' ?></td>
                                            <td class="exp-description"><?= htmlspecialchars($exp['description'] ?? '') ?></td>
                                            <td class="exp-actions">
                                                <button class="modif-btn" type="button" title="Modifier" onclick="editExperience(<?= (int)($exp['id_experience'] ?? 0) ?>)">✏️</button>
                                                <form id="edit-exp-<?= (int)($exp['id_experience'] ?? 0) ?>" action="<?= htmlspecialchars(app_url('/profil/updateExperience')) ?>" method="post" style="display:none;">
                                                    <input type="hidden" name="id" value="<?= (int)($exp['id_experience'] ?? 0) ?>">
                                                    <input type="hidden" name="poste" value="<?= htmlspecialchars((string)($exp['poste'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="entreprise" value="<?= htmlspecialchars((string)($exp['entreprise'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="date_debut" value="<?= htmlspecialchars((string)($exp['date_debut'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="date_fin" value="<?= htmlspecialchars((string)($exp['date_fin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="description" value="<?= htmlspecialchars((string)($exp['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                </form>
                                                <form action="<?= htmlspecialchars(app_url('/profil/deleteExperience')) ?>" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette expérience ?');">
                                                    <input type="hidden" name="id" value="<?= (int)($exp['id_experience'] ?? 0) ?>">
                                                    <button class="suppr-btn" type="submit">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr class="empty-experience-row"><td colspan="5" style="text-align:center; padding:1rem;">Aucune expérience ajoutée</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
        </main>
    </div>
    <script>
        (function(){
            function setBusy(on){
                var btn = document.getElementById('ppei-generate-btn');
                var inline = document.getElementById('ppei-generate-inline');
                if(btn) btn.disabled = on;
                if(inline) inline.disabled = on;
            }

            async function generatePpei(){
                setBusy(true);
                try{
                    var resp = await fetch('<?= htmlspecialchars(app_url('/profil/generatePpeiAi')) ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'X-Requested-With':'XMLHttpRequest'},
                        body: new URLSearchParams({language: 'fr', model: 'mistral'})
                    });
                    var json = await resp.json().catch(function(){return null;});
                    if(!json || !json.success){
                        alert('Erreur: génération IA impossible.');
                        return;
                    }
                    var bio = (json.data && json.data.bio) ? json.data.bio : '';
                    // Update displays
                    var display = document.getElementById('bio-generated-display');
                    if(display){ display.innerHTML = bio ? bio.replace(/\n/g, '<br>') : '<span style="color:#777;">Aucune biographie disponible.</span>'; }
                    var side = document.getElementById('ppei-summary-sidebar');
                    if(side){ side.textContent = bio || ''; }
                    var panel = document.getElementById('ppei-summary-panel');
                    if(panel){ panel.textContent = bio || ''; }
                    // Update IA tips if provided
                    try{
                        var tipsEl = document.getElementById('cv-ia-tips');
                        if(tipsEl){
                            tipsEl.innerHTML = '';
                            var tips = json.data && json.data.tips ? json.data.tips : [];
                            if(Array.isArray(tips) && tips.length > 0){
                                tips.forEach(function(t){
                                    var d = document.createElement('div');
                                    d.className = 'cv-tip';
                                    d.textContent = '• ' + t;
                                    tipsEl.appendChild(d);
                                });
                            } else {
                                tipsEl.innerHTML = '<div class="cv-tip">💡 Aucun conseil IA généré.</div>';
                            }
                        }
                    }catch(e){
                        console.error('Erreur mise à jour conseils IA', e);
                    }

                    alert('PPÉI généré avec Ollama.');
                }catch(e){
                    console.error(e);
                    alert('Erreur réseau lors de la génération IA.');
                }finally{
                    setBusy(false);
                }
            }

            var btn = document.getElementById('ppei-generate-btn');
            if(btn){ btn.addEventListener('click', function(){ if(confirm('Générer le PPÉI avec Ollama ? Le texte sera enregistré automatiquement.')) generatePpei(); }); }
            var inlineBtn = document.getElementById('ppei-generate-inline');
            if(inlineBtn){ inlineBtn.addEventListener('click', function(){ if(confirm('Générer le PPÉI avec Ollama ? Le texte sera enregistré automatiquement.')) generatePpei(); }); }
        })();
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var bioDisplay = document.getElementById('bio-display');
            var bioEdit = document.getElementById('bio-edit');
            var bioText = document.getElementById('bio-text');
            var bioTextarea = document.getElementById('bio-textarea');
            var bioEditBtn = document.getElementById('bio-edit-btn');
            var bioSave = document.getElementById('bio-save');
            var bioCancel = document.getElementById('bio-cancel');
            var bioGenerate = document.getElementById('bio-generate');
            var bioStatus = document.getElementById('bio-status');

            if (!bioDisplay || !bioEdit || !bioText || !bioTextarea || !bioEditBtn || !bioSave || !bioCancel || !bioGenerate || !bioStatus) {
                return;
            }

            function setStatus(message, color) {
                bioStatus.textContent = message || '';
                if (color) {
                    bioStatus.style.color = color;
                } else {
                    bioStatus.style.color = '#666';
                }
            }

            bioEditBtn.addEventListener('click', function() {
                bioDisplay.style.display = 'none';
                bioEdit.style.display = 'block';
                bioTextarea.value = bioText.innerText.trim();
                setStatus('');
            });

            bioCancel.addEventListener('click', function() {
                bioDisplay.style.display = 'block';
                bioEdit.style.display = 'none';
                setStatus('');
            });

            bioSave.addEventListener('click', function() {
                var newBio = bioTextarea.value.trim();
                if (!newBio) {
                    setStatus('❌ La bio ne peut pas être vide', '#ef4444');
                    return;
                }

                bioSave.disabled = true;
                bioSave.textContent = 'Sauvegarde...';
                setStatus('');

                var formData = new URLSearchParams();
                formData.append('bio', newBio);

                fetch(appUrl('/profil/updateBio'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('Erreur HTTP ' + resp.status);
                    }
                    var contentType = resp.headers.get('content-type') || '';
                    if (contentType.indexOf('application/json') !== -1) {
                        return resp.json();
                    }
                    return resp.text();
                })
                .then(function(data) {
                    var isSuccess = true;
                    if (data && typeof data === 'object' && typeof data.success !== 'undefined') {
                        isSuccess = !!data.success;
                    }
                    if (isSuccess) {
                        bioText.innerText = newBio;
                        bioDisplay.style.display = 'block';
                        bioEdit.style.display = 'none';
                        setStatus('✅ Bio sauvegardée', '#10b981');
                        setTimeout(function() { setStatus(''); }, 3000);
                    } else {
                        setStatus('❌ Erreur lors de la sauvegarde', '#ef4444');
                    }
                })
                .catch(function() {
                    setStatus('❌ Erreur lors de la sauvegarde', '#ef4444');
                })
                .finally(function() {
                    bioSave.disabled = false;
                    bioSave.textContent = '💾 Sauvegarder';
                });
            });

            bioGenerate.addEventListener('click', function() {
                bioGenerate.disabled = true;
                bioGenerate.textContent = 'Génération en cours...';
                setStatus('⏳ Connexion à Ollama...', '#666');

                var formData = new URLSearchParams();
                formData.append('language', 'fr');
                formData.append('model', 'mistral');

                fetch(appUrl('/profil/generatePpeiAi'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(function(resp) {
                    if (!resp.ok) {
                        throw new Error('Erreur HTTP ' + resp.status);
                    }
                    return resp.json();
                })
                .then(function(data) {
                    if (data.success && data.data && data.data.bio) {
                        var newBio = data.data.bio;
                        bioText.innerText = newBio;
                        bioTextarea.value = newBio;
                        setStatus('✅ Bio générée avec succès !', '#10b981');
                        bioDisplay.style.display = 'block';
                        bioEdit.style.display = 'none';
                    } else {
                        throw new Error(data.message || 'Erreur');
                    }
                })
                .catch(function(err) {
                    setStatus('❌ ' + (err.message || 'Erreur de génération'), '#ef4444');
                })
                .finally(function() {
                    bioGenerate.disabled = false;
                    bioGenerate.textContent = '🚀 Générer une version professionnelle (Ollama)';
                    setTimeout(function() {
                        if (bioStatus.textContent.indexOf('succès') !== -1) {
                            setStatus('');
                        }
                    }, 5000);
                });
            });
        });
    </script>

    <div id="chatbot-widget" class="chatbot-widget" aria-live="polite">
        <button type="button" id="chatbot-toggle" class="chatbot-toggle" aria-label="Ouvrir assistant">
            💬
        </button>

        <section id="chatbot-panel" class="chatbot-panel" aria-hidden="true">
            <header class="chatbot-header">
                <div>
                    <div class="chatbot-title">Assistant IA</div>
                    <div class="chatbot-subtitle" id="chatbot-provider-label">IA intégrée</div>
                </div>
                <button type="button" id="chatbot-close" class="chatbot-close" aria-label="Fermer assistant">✕</button>
            </header>

            <div id="chatbot-suggestions" class="chatbot-suggestions"></div>

            <div id="chatbot-messages" class="chatbot-messages">
                <div class="chatbot-msg bot">Bonjour, je peux proposer des solutions concretes. Posez votre question.</div>
            </div>

            <form id="chatbot-form" class="chatbot-form">
                <input type="text" id="chatbot-input" maxlength="400" placeholder="Posez votre question..." autocomplete="off">
                <button type="submit">Envoyer</button>
            </form>
        </section>
    </div>

    <!-- MODAL MODIFICATION -->
    <div class="modal-overlay" id="modal-edit" style="display:none">
        <div class="modal">
            <div class="modal-header">
                <h2>✏️ Modifier mon profil</h2>
                <button type="button" class="modal-close" onclick="closeModal('edit')">✕</button>
            </div>
            <?php
                $availabilitySlotsJson = trim((string)($disponibilite_slots ?? '')) !== '' ? (string)$disponibilite_slots : '[]';
                $availabilityExceptionsJson = trim((string)($disponibilite_exceptions ?? '')) !== '' ? (string)$disponibilite_exceptions : '[]';
                $availabilityCongesJson = trim((string)($disponibilite_conges ?? '')) !== '' ? (string)$disponibilite_conges : '[]';
            ?>
            <form action="<?= htmlspecialchars(app_url('/profil/update')) ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Spécialité</label>
                    <input type="text" name="specialite" maxlength="120" value="<?= htmlspecialchars($specialite ?? '') ?>">
                </div>
                <div
                    class="availability-designer"
                    data-availability-designer="true"
                    data-initial-status="<?= htmlspecialchars($disponibilite ?? 'disponible') ?>"
                    data-initial-summary="<?= htmlspecialchars($disponibilite_horaire ?? '') ?>"
                    data-initial-message="<?= htmlspecialchars($disponibilite_message ?? '') ?>"
                    data-initial-slots="<?= htmlspecialchars($availabilitySlotsJson, ENT_QUOTES, 'UTF-8') ?>"
                    data-initial-exceptions="<?= htmlspecialchars($availabilityExceptionsJson, ENT_QUOTES, 'UTF-8') ?>"
                    data-initial-conges="<?= htmlspecialchars($availabilityCongesJson, ENT_QUOTES, 'UTF-8') ?>"
                >
                    <div class="availability-designer-tabs" role="tablist" aria-label="Configuration de disponibilite">
                        <button type="button" class="availability-tab-btn is-active" data-availability-tab-btn="horaires">Horaires</button>
                        <button type="button" class="availability-tab-btn" data-availability-tab-btn="statut">Statut</button>
                        <button type="button" class="availability-tab-btn" data-availability-tab-btn="exceptions">Exceptions</button>
                    </div>

                    <div class="availability-preview-box">
                        <div class="availability-preview-badge availability-preview-badge-disponible" data-availability-preview-badge="true">
                            <span class="availability-preview-dot" data-availability-preview-dot="true"></span>
                            <span data-availability-preview-label="true">Disponible</span>
                        </div>
                        <div class="availability-preview-hours" data-availability-preview-hours="true">Lun - Sam · 8h-17h</div>
                        <div class="availability-preview-message" data-availability-preview-message="true"></div>
                    </div>

                    <div class="availability-tab-panel is-active" data-availability-tab-panel="horaires">
                        <p class="availability-helper-text">Ajoutez des creneaux recurrents avec jours et horaires.</p>
                        <div class="availability-days">
                            <label class="availability-day-chip"><input type="checkbox" value="Lun" data-slot-day="true">Lun</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Mar" data-slot-day="true">Mar</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Mer" data-slot-day="true">Mer</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Jeu" data-slot-day="true">Jeu</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Ven" data-slot-day="true">Ven</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Sam" data-slot-day="true">Sam</label>
                            <label class="availability-day-chip"><input type="checkbox" value="Dim" data-slot-day="true">Dim</label>
                        </div>
                        <div class="availability-time-row">
                            <input type="time" data-slot-start="true" value="08:00">
                            <span class="availability-time-sep">a</span>
                            <input type="time" data-slot-end="true" value="17:00">
                            <button type="button" class="availability-btn-add" data-slot-add="true">Ajouter creneau</button>
                        </div>
                        <div class="availability-tags" data-slot-tags="true"></div>
                    </div>

                    <div class="availability-tab-panel" data-availability-tab-panel="statut">
                        <p class="availability-helper-text">Choisissez un statut et un message personnalise.</p>
                        <div class="availability-status-grid">
                            <button type="button" class="availability-status-btn is-green" data-status-value="disponible">Disponible</button>
                            <button type="button" class="availability-status-btn is-orange" data-status-value="occupe">Absent momentanement</button>
                            <button type="button" class="availability-status-btn is-red" data-status-value="indisponible">Indisponible</button>
                        </div>
                        <div class="form-group availability-message-group">
                            <label>Message personnalise</label>
                            <input type="text" maxlength="120" placeholder="ex : En reunion jusqu'a 14h" data-status-message="true">
                        </div>
                    </div>

                    <div class="availability-tab-panel" data-availability-tab-panel="exceptions">
                        <p class="availability-helper-text">Ajoutez des jours ponctuels fermes ou a horaires speciaux.</p>
                        <div class="availability-exception-row">
                            <input type="date" data-exception-date="true">
                            <select data-exception-type="true">
                                <option value="ferme">Ferme</option>
                                <option value="reduits">Horaires reduits</option>
                                <option value="speciaux">Horaires speciaux</option>
                            </select>
                            <button type="button" class="availability-btn-add" data-exception-add="true">Ajouter</button>
                        </div>
                        <div class="availability-conges-row">
                            <input type="date" data-conges-start="true">
                            <span class="availability-time-sep">a</span>
                            <input type="date" data-conges-end="true">
                            <button type="button" class="availability-btn-add" data-conges-add="true">Ajouter conges</button>
                        </div>
                        <div class="availability-tags" data-exception-tags="true"></div>
                    </div>
                </div>

                <input type="hidden" name="disponibilite" value="<?= htmlspecialchars($disponibilite ?? 'disponible') ?>">
                <input type="hidden" name="disponibilite_horaire" value="<?= htmlspecialchars($disponibilite_horaire ?? '') ?>">
                <input type="hidden" name="disponibilite_message" value="<?= htmlspecialchars($disponibilite_message ?? '') ?>">
                <input type="hidden" name="disponibilite_slots" value="<?= htmlspecialchars($availabilitySlotsJson, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="disponibilite_exceptions" value="<?= htmlspecialchars($availabilityExceptionsJson, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="disponibilite_conges" value="<?= htmlspecialchars($availabilityCongesJson, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" name="nom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Prénom</label>
                        <input type="text" name="prenom" minlength="2" maxlength="60" pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]+" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" maxlength="120" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" maxlength="30" pattern="^\+?[0-9][0-9\s().-]{7,19}$" value="<?= htmlspecialchars(($telephone ?? '') === 'Non renseigne' ? '' : ($telephone ?? '')) ?>" placeholder="ex : +216 20 000 000">
                </div>
                <div class="form-group">
                    <label>Ville</label>
                    <input type="text" name="ville" maxlength="120" value="<?= htmlspecialchars($ville ?? '') ?>">
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

    .table-tools-row {
        display: flex;
        flex-wrap: wrap;
        gap: .7rem;
        margin: .4rem 0 1rem;
        align-items: center;
    }

    .table-search-input,
    .table-sort-select {
        border: 1px solid #e0d6c3;
        background: #fff7ee;
        border-radius: .55rem;
        padding: .52rem .65rem;
        font-size: .86rem;
        color: var(--brun);
    }

    .table-search-input {
        flex: 1;
        min-width: 220px;
    }

    .table-sort-select {
        min-width: 220px;
    }

    .stats-toggle-btn {
        border: 1px solid #d8c8b0;
        background: #f5ecdd;
        color: var(--marron);
        border-radius: .55rem;
        padding: .5rem .8rem;
        font-size: .82rem;
        font-weight: 700;
        cursor: pointer;
    }

    .stats-toggle-btn:hover {
        background: #f0e3d1;
    }

    .table-stats-panel {
        border: 1px solid #eadfcd;
        background: #fff;
        border-radius: .8rem;
        padding: .8rem;
        margin: 0 0 1rem;
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
    }

    .stats-chip {
        border: 1px solid #eadfcd;
        background: #f8f5ef;
        border-radius: .65rem;
        padding: .45rem .65rem;
        min-width: 130px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
    }

    .stats-chip span {
        color: #87745e;
        font-size: .74rem;
    }

    .stats-chip strong {
        color: var(--brun);
        font-size: .86rem;
    }

    .table-empty-search {
        text-align: center;
        color: #888;
        padding: .9rem;
        font-size: .85rem;
    }

    @media (max-width: 900px) {
        .table-search-input,
        .table-sort-select {
            min-width: 100%;
        }

        .stats-toggle-btn {
            width: 100%;
        }
    }

    .smart-insights-card {
        border: 1px solid rgba(196, 154, 108, .25);
        background: linear-gradient(165deg, #fff 0%, #fcf8f1 100%);
    }

    .smart-insights-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .8rem;
        margin-bottom: 1rem;
    }

    .smart-kpi {
        background: #f8f5ef;
        border: 1px solid #eadfcd;
        border-radius: .95rem;
        padding: .85rem;
    }

    .smart-kpi-label {
        color: #8f7a61;
        font-size: .75rem;
        margin-bottom: .25rem;
    }

    .smart-kpi-value {
        color: var(--brun);
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: .45rem;
    }

    .smart-progress {
        height: 8px;
        background: #eee5d8;
        border-radius: 999px;
        overflow: hidden;
    }

    .smart-progress-fill {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--marron), var(--caramel));
    }

    .smart-trending-line {
        display: flex;
        align-items: center;
        gap: .35rem;
    }

    .smart-pill {
        font-size: .72rem;
        border-radius: 999px;
        padding: .2rem .6rem;
        background: #efe6d8;
        color: #78634b;
    }

    .smart-pill.is-hot {
        background: #ffefe3;
        color: #b85c16;
    }

    .smart-kpi-hint {
        color: #98846b;
        font-size: .73rem;
        line-height: 1.35;
    }

    .smart-activity-card {
        border: 1px solid #eadfcd;
        border-radius: .95rem;
        padding: .9rem;
        background: #fff;
    }

    .smart-activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: var(--brun);
        font-weight: 700;
        margin-bottom: .75rem;
    }

    .smart-activity-sub {
        color: #9a876e;
        font-size: .72rem;
        font-weight: 600;
    }

    .smart-bars-wrap {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: .55rem;
        min-height: 140px;
    }

    .smart-bar-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex: 1;
        min-width: 0;
        gap: .25rem;
    }

    .smart-bar-value {
        color: #8a7458;
        font-size: .72rem;
        font-weight: 700;
    }

    .smart-bar-track {
        width: 18px;
        height: 95px;
        border-radius: 999px;
        background: #f2e9db;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
    }

    .smart-bar-fill {
        width: 100%;
        border-radius: inherit;
        background: linear-gradient(180deg, var(--caramel), var(--marron));
    }

    .smart-bar-label {
        color: #8e7b63;
        font-size: .68rem;
        text-align: center;
        line-height: 1.2;
        word-break: break-word;
    }

    @media (max-width: 980px) {
        .smart-insights-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>

</body>
</html>