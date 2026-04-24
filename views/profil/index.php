<?php
// views/profil/index.php
// Variables attendues du controller :
// $user, $stats, $competences, $certifications, $experiences, $portfolioFiles, $avis, $flash
$baseUrl = app_url();
$profileCssVersion = (string)(@filemtime(__DIR__ . '/../../public/css/style.css') ?: time());
$profileJsVersion = (string)(@filemtime(__DIR__ . '/../../public/js/index.js') ?: time());
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Professionnel — حرفة Tunisie</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(app_url('/public/css/style.css?v=' . $profileCssVersion)) ?>">
    <script src="<?= htmlspecialchars(app_url('/public/js/index.js?v=' . $profileJsVersion)) ?>" defer></script>
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
                    <?php $hasBio = trim((string)$bio) !== ''; ?>
                    <form id="bio-inline-form" action="<?= htmlspecialchars(app_url($hasBio ? '/profil/updateBio' : '/profil/addBio')) ?>" method="post" data-ajax-add="true" data-ajax-bio="true" style="background:#f8f5f0;padding:1rem;border-radius:1rem;">
                        <label for="bio-inline" style="display:block;font-weight:600;color:var(--marron);margin-bottom:.5rem;">Votre bio professionnelle</label>
                        <textarea id="bio-inline" name="bio" maxlength="2000" rows="6" placeholder="Saisissez votre bio ici..." style="width:100%;padding:.75rem;border-radius:.75rem;border:1px solid #e0d6c3;background:#fff7ee;resize:vertical;"><?= htmlspecialchars($hasBio ? $bio : '') ?></textarea>
                        <div style="display:flex;justify-content:flex-end;gap:.6rem;margin-top:.75rem;">
                            <?php if ($hasBio): ?>
                                <button type="submit" class="btn-primary" data-bio-submit="true">Modifier une bio existante</button>
                            <?php else: ?>
                                <button type="submit" class="btn-primary" data-bio-submit="true">Ajouter une bio</button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <?php if ($hasBio): ?>
                        <form action="<?= htmlspecialchars(app_url('/profil/deleteBio')) ?>" method="post" style="margin-top:.75rem;display:flex;justify-content:flex-end;" onsubmit="return confirm('Supprimer votre bio ?');">
                            <button type="submit" class="btn-secondary">Supprimer une bio</button>
                        </form>
                        <p class="bio-text" data-bio-display="true" style="margin-top:1rem;"><?= nl2br(htmlspecialchars($bio)) ?></p>
                    <?php else: ?>
                        <p class="bio-text" data-bio-display="true" style="margin-top:1rem;color:#777;">Aucune biographie disponible.</p>
                    <?php endif; ?>
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
                    <div class="smart-insights-grid">
                        <div class="smart-kpi smart-kpi-score">
                            <div class="smart-kpi-label">Score global du profil</div>
                            <div class="smart-kpi-value"><?= (int)$profileScore ?>%</div>
                            <div class="smart-progress">
                                <div class="smart-progress-fill" style="width: <?= (int)$profileScore ?>%"></div>
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

                <div class="card" style="margin-top:1.5rem;">
                    <div class="section-title">AI Career Intelligence</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
                        <div style="background:#f8f5f0;padding:1rem;border-radius:1rem;">
                            <div style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--marron);font-weight:700;">Résumé professionnel</div>
                            <div style="margin-top:.55rem;line-height:1.7;color:var(--brun);"><?= nl2br(htmlspecialchars((string)($professionalSummary ?? ''))) ?></div>
                            <div style="margin-top:1rem;font-size:.9rem;color:#6d6254;"><strong>Headline LinkedIn:</strong> <?= htmlspecialchars((string)($linkedinHeadline ?? '')) ?></div>
                            <div style="margin-top:.55rem;font-size:.9rem;color:#6d6254;"><strong>CV IA:</strong> <?= htmlspecialchars((string)($cvSummary ?? '')) ?></div>
                            <div style="margin-top:.55rem;font-size:.85rem;color:#8c7a67;">
                                <strong>Dernier calcul:</strong>
                                <?= !empty($insightLastCalcAt) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string)$insightLastCalcAt))) : 'En attente' ?>
                            </div>
                        </div>

                        <div style="background:#f8f5f0;padding:1rem;border-radius:1rem;">
                            <div style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--marron);font-weight:700;">Jobs suggérés</div>
                            <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.7rem;">
                                <?php foreach (($suggestedJobs ?? []) as $job): ?>
                                    <span class="badge"><?= htmlspecialchars((string)$job) ?></span>
                                <?php endforeach; ?>
                                <?php if (empty($suggestedJobs ?? [])): ?>
                                    <span class="badge">Aucune suggestion</span>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top:1rem;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--marron);font-weight:700;">Skill gaps</div>
                            <ul style="margin:.55rem 0 0 1rem;padding:0;color:var(--brun);line-height:1.7;">
                                <?php foreach (($skillGaps ?? []) as $gap): ?>
                                    <li><?= htmlspecialchars((string)$gap) ?></li>
                                <?php endforeach; ?>
                                <?php if (empty($skillGaps ?? [])): ?>
                                    <li>Aucun écart majeur détecté.</li>
                                <?php endif; ?>
                            </ul>

                            <div style="margin-top:1rem;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--marron);font-weight:700;">Career paths</div>
                            <ul style="margin:.55rem 0 0 1rem;padding:0;color:var(--brun);line-height:1.7;">
                                <?php foreach (($careerPaths ?? []) as $path): ?>
                                    <li><?= htmlspecialchars((string)$path) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div style="margin-top:1rem;background:#fff;border:1px solid #ece1d3;border-radius:1rem;padding:1rem;">
                        <div style="font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--marron);font-weight:700;margin-bottom:.35rem;">Lettre de motivation IA</div>
                        <div style="white-space:pre-line;line-height:1.7;color:var(--brun);">
                            <?= htmlspecialchars((string)($coverLetterTemplate ?? '')) ?>
                        </div>
                    </div>
                </div>

                <!-- ==========================================
                     MÉTIERS AVANCÉS AVEC IA (SLOT 1 & 2)
                     Persisté dans la table `metiers_avances`
                     ========================================== -->
                <?php
                // $metiersAvances est injecté par ProfilController::index()
                $metiersAvances = $metiersAvances ?? [];
                $metierBySlot = [];
                foreach ($metiersAvances as $m) {
                    $metierBySlot[(int)$m['slot']] = $m;
                }
                ?>
                <div class="card" id="metiers-avances-section" style="margin-top:1.5rem;">
                    <div class="section-title" style="display:flex;align-items:center;gap:.6rem;">
                        🏆 Métiers Avancés
                        <span style="font-size:.72rem;background:linear-gradient(135deg,#8b5a3a,#c8973f);color:#fff;padding:.2rem .6rem;border-radius:2rem;font-weight:700;letter-spacing:.06em;">IA</span>
                    </div>
                    <p style="color:#8c7a67;font-size:.85rem;margin-bottom:1.25rem;">
                        Définissez vos 2 métiers clés. L'IA analyse votre profil et génère des recommandations, projections salariales et tendances marché.
                    </p>

                    <!-- GRILLE SLOTS 1 ET 2 -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(290px,1fr));gap:1.25rem;" id="metiers-grid">
                    <?php for ($slot = 1; $slot <= 2; $slot++): ?>
                    <?php $m = $metierBySlot[$slot] ?? null; ?>
                    <div class="metier-avance-card" id="metier-slot-<?= $slot ?>" data-slot="<?= $slot ?>"
                         style="background:#f8f5f0;border-radius:1rem;padding:1.1rem;border:1.5px solid #ece1d3;position:relative;">

                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem;">
                            <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:var(--marron);font-weight:700;">
                                Métier <?= $slot ?>
                            </div>
                            <?php if ($m): ?>
                            <div style="display:flex;gap:.4rem;">
                                <button type="button"
                                    onclick="openMetierForm(<?= $slot ?>, <?= (int)$m['id_metier'] ?>)"
                                    style="background:none;border:1px solid var(--marron);color:var(--marron);padding:.2rem .55rem;border-radius:.4rem;font-size:.8rem;cursor:pointer;"
                                    title="Modifier">✏️</button>
                                <form action="<?= htmlspecialchars(app_url('/profil/analyseMetierAvance')) ?>" method="post" style="display:inline;"
                                      data-ajax-metier-analyse="true" data-metier-id="<?= (int)$m['id_metier'] ?>">
                                    <input type="hidden" name="id_metier" value="<?= (int)$m['id_metier'] ?>">
                                    <button type="submit" title="Relancer analyse IA"
                                        style="background:none;border:1px solid #c8973f;color:#c8973f;padding:.2rem .55rem;border-radius:.4rem;font-size:.8rem;cursor:pointer;">🤖 Analyser</button>
                                </form>
                                <form action="<?= htmlspecialchars(app_url('/profil/deleteMetierAvance')) ?>" method="post" style="display:inline;"
                                      onsubmit="return confirm('Supprimer ce métier avancé ?');">
                                    <input type="hidden" name="id_metier" value="<?= (int)$m['id_metier'] ?>">
                                    <button type="submit" style="background:none;border:1px solid #dc3545;color:#dc3545;padding:.2rem .55rem;border-radius:.4rem;font-size:.8rem;cursor:pointer;">🗑️</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($m): ?>
                        <!-- Métier renseigné -->
                        <div class="metier-display" id="metier-display-<?= $slot ?>">
                            <div style="font-size:1.05rem;font-weight:700;color:var(--brun);margin-bottom:.3rem;">
                                <?= htmlspecialchars((string)$m['titre']) ?>
                            </div>
                            <?php if (!empty($m['description'])): ?>
                            <div style="font-size:.83rem;color:#6d6254;margin-bottom:.65rem;line-height:1.55;">
                                <?= nl2br(htmlspecialchars((string)$m['description'])) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Niveau de maîtrise -->
                            <div style="margin-bottom:.7rem;">
                                <div style="display:flex;justify-content:space-between;font-size:.75rem;color:#8c7a67;margin-bottom:.3rem;">
                                    <span>Maîtrise</span>
                                    <strong><?= (int)$m['niveau_maitrise'] ?>%</strong>
                                </div>
                                <div style="background:#e8ddd0;border-radius:2rem;height:7px;">
                                    <div style="background:linear-gradient(90deg,var(--marron),#c8973f);border-radius:2rem;height:7px;width:<?= (int)$m['niveau_maitrise'] ?>%;transition:.4s;"></div>
                                </div>
                            </div>

                            <!-- Technologies -->
                            <?php if (!empty($m['technologies'])): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:.35rem;margin-bottom:.75rem;">
                                <?php foreach ((array)$m['technologies'] as $tech): ?>
                                    <span style="background:#fff7ee;border:1px solid #e0d6c3;color:var(--marron);padding:.15rem .55rem;border-radius:2rem;font-size:.75rem;font-weight:600;">
                                        <?= htmlspecialchars((string)$tech) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Bloc IA -->
                            <?php if (!empty($m['ia_tendance_marche']) || !empty($m['ia_projection_salaire']) || !empty($m['ia_recommandations'])): ?>
                            <div style="background:#fff;border:1px solid #ece1d3;border-radius:.75rem;padding:.8rem;margin-top:.5rem;">
                                <div style="font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--marron);font-weight:700;margin-bottom:.5rem;">🤖 Analyse IA</div>

                                <?php if ($m['ia_score_adequation'] !== null): ?>
                                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem;">
                                    <div style="font-size:.78rem;color:#6d6254;">Score adéquation</div>
                                    <div style="background:linear-gradient(135deg,#8b5a3a,#c8973f);color:#fff;padding:.15rem .55rem;border-radius:2rem;font-size:.75rem;font-weight:700;">
                                        <?= (int)$m['ia_score_adequation'] ?>%
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($m['ia_tendance_marche']): ?>
                                <div style="font-size:.8rem;color:#5a4a3a;margin-bottom:.3rem;">
                                    <strong>Tendance :</strong> <?= htmlspecialchars((string)$m['ia_tendance_marche']) ?>
                                </div>
                                <?php endif; ?>

                                <?php if ($m['ia_projection_salaire']): ?>
                                <div style="font-size:.8rem;color:#5a4a3a;margin-bottom:.5rem;">
                                    <strong>Projection :</strong> <?= htmlspecialchars((string)$m['ia_projection_salaire']) ?>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($m['ia_recommandations'])): ?>
                                <ul style="margin:.3rem 0 0 1rem;padding:0;font-size:.78rem;color:var(--brun);line-height:1.65;">
                                    <?php foreach ((array)$m['ia_recommandations'] as $rec): ?>
                                        <li><?= htmlspecialchars((string)$rec) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>

                                <?php if ($m['ia_derniere_analyse']): ?>
                                <div style="font-size:.68rem;color:#aaa;margin-top:.5rem;text-align:right;">
                                    Dernière analyse : <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$m['ia_derniere_analyse']))) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div style="font-size:.8rem;color:#aaa;font-style:italic;margin-top:.5rem;">
                                Cliquez sur 🤖 Analyser pour générer l'analyse IA.
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php else: ?>
                        <!-- Slot vide -->
                        <div class="metier-empty" id="metier-empty-<?= $slot ?>">
                            <div style="text-align:center;padding:1rem 0;color:#b0a090;">
                                <div style="font-size:2rem;margin-bottom:.4rem;">🎯</div>
                                <div style="font-size:.85rem;margin-bottom:.8rem;">Aucun métier défini</div>
                                <button type="button"
                                    onclick="openMetierForm(<?= $slot ?>, null)"
                                    style="background:var(--marron);color:#fff;padding:.5rem 1.2rem;border:none;border-radius:.6rem;font-size:.85rem;font-weight:600;cursor:pointer;">
                                    + Définir ce métier
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                    </div><!-- /metiers-grid -->
                </div><!-- /metiers-avances-section -->

                <!-- MODAL FORMULAIRE MÉTIER AVANCÉ -->
                <div id="modal-metier-avance" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center;">
                    <div style="background:#fff;border-radius:1.25rem;padding:2rem;width:min(92vw,520px);max-height:90vh;overflow-y:auto;box-shadow:0 8px 40px #0004;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
                            <h3 style="color:var(--marron);margin:0;font-size:1.1rem;" id="modal-metier-title">Métier Avancé</h3>
                            <button type="button" onclick="closeMetierModal()" style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#888;">✕</button>
                        </div>
                        <form id="form-metier-avance" action="<?= htmlspecialchars(app_url('/profil/upsertMetierAvance')) ?>" method="post" data-ajax-metier="true">
                            <input type="hidden" name="slot" id="metier-slot-input" value="1">
                            <input type="hidden" name="id_metier" id="metier-id-input" value="">

                            <div style="margin-bottom:1rem;">
                                <label style="font-weight:600;color:var(--marron);font-size:.88rem;display:block;margin-bottom:.35rem;">Titre du métier <span style="color:#dc3545;">*</span></label>
                                <input type="text" name="titre" id="metier-titre" maxlength="150" required placeholder="ex : Développeur Full Stack, Artisan Céramiste..."
                                    style="width:100%;padding:.6rem .8rem;border-radius:.65rem;border:1.5px solid #e0d6c3;background:#fff7ee;font-size:.9rem;">
                            </div>

                            <div style="margin-bottom:1rem;">
                                <label style="font-weight:600;color:var(--marron);font-size:.88rem;display:block;margin-bottom:.35rem;">Description</label>
                                <textarea name="description" id="metier-description" maxlength="1000" rows="3" placeholder="Décrivez votre positionnement dans ce métier..."
                                    style="width:100%;padding:.6rem .8rem;border-radius:.65rem;border:1.5px solid #e0d6c3;background:#fff7ee;font-size:.88rem;resize:vertical;"></textarea>
                            </div>

                            <div style="margin-bottom:1rem;">
                                <label style="font-weight:600;color:var(--marron);font-size:.88rem;display:block;margin-bottom:.35rem;">
                                    Niveau de maîtrise : <span id="metier-niveau-display">50</span>%
                                </label>
                                <input type="range" name="niveau_maitrise" id="metier-niveau" min="0" max="100" value="50"
                                    style="width:100%;accent-color:var(--marron);"
                                    oninput="document.getElementById('metier-niveau-display').textContent=this.value">
                            </div>

                            <div style="margin-bottom:1.25rem;">
                                <label style="font-weight:600;color:var(--marron);font-size:.88rem;display:block;margin-bottom:.35rem;">
                                    Technologies / Compétences clés
                                    <span style="font-weight:400;color:#aaa;">(séparées par des virgules)</span>
                                </label>
                                <input type="text" name="technologies" id="metier-technologies" maxlength="500"
                                    placeholder="ex : PHP, Laravel, React, MySQL..."
                                    style="width:100%;padding:.6rem .8rem;border-radius:.65rem;border:1.5px solid #e0d6c3;background:#fff7ee;font-size:.88rem;">
                            </div>

                            <div style="display:flex;gap:.7rem;justify-content:flex-end;">
                                <button type="button" onclick="closeMetierModal()"
                                    style="background:#f0ece5;color:var(--marron);border:none;padding:.65rem 1.3rem;border-radius:.65rem;font-weight:600;cursor:pointer;">
                                    Annuler
                                </button>
                                <button type="submit"
                                    style="background:linear-gradient(135deg,var(--marron),#c8973f);color:#fff;border:none;padding:.65rem 1.6rem;border-radius:.65rem;font-weight:700;cursor:pointer;">
                                    💾 Sauvegarder + Analyse IA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /MODAL MÉTIER AVANCÉ -->

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
                    <table style="width:100%;border-collapse:separate;border-spacing:0 .75rem;">
                        <thead>
                            <tr style="color:var(--marron);font-size:.95rem;background:#f8f5f0;">
                                <th>TITRE</th><th>REALISATION</th><th>FICHIER</th><th>DATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($portfolioFiles)): ?>
                                <?php foreach ($portfolioFiles as $file): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($file['titre'] ?: $file['file_name']) ?></td>
                                        <td><?= htmlspecialchars((string)($file['realisation'] ?? '-')) ?></td>
                                        <td>
                                            <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" style="color:var(--marron);text-decoration:none;">Voir</a>
                                        </td>
                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($file['created_at'] ?? 'now'))) ?></td>
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

<script>
// ============================================================
// MÉTIERS AVANCÉS — JavaScript
// ============================================================
function openMetierForm(slot, idMetier) {
    document.getElementById('modal-metier-title').textContent = 'Métier Avancé ' + slot;
    document.getElementById('metier-slot-input').value = slot;
    document.getElementById('metier-id-input').value = idMetier || '';

    // Pré-remplir si édition
    if (idMetier) {
        var card = document.getElementById('metier-slot-' + slot);
        var titreEl = card ? card.querySelector('[data-metier-titre]') : null;
        // Lecture depuis le DOM affichage
        var titleText = card ? (card.querySelector('.metier-display > div:first-child') || {}).textContent : '';
        document.getElementById('metier-titre').value = titleText ? titleText.trim() : '';
    } else {
        document.getElementById('metier-titre').value = '';
        document.getElementById('metier-description').value = '';
        document.getElementById('metier-niveau').value = 50;
        document.getElementById('metier-niveau-display').textContent = '50';
        document.getElementById('metier-technologies').value = '';
    }

    var modal = document.getElementById('modal-metier-avance');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMetierModal() {
    var modal = document.getElementById('modal-metier-avance');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Fermer en cliquant à l'extérieur
document.getElementById('modal-metier-avance').addEventListener('click', function(e) {
    if (e.target === this) closeMetierModal();
});

// Soumission AJAX du formulaire métier
var formMetier = document.getElementById('form-metier-avance');
if (formMetier) {
    formMetier.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('[type=submit]');
        var origText = btn.textContent;
        btn.textContent = '⏳ Analyse IA en cours...';
        btn.disabled = true;

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.textContent = origText;
            btn.disabled = false;
            if (data.success) {
                closeMetierModal();
                // Rafraîchir la page pour voir les données persistées
                window.location.reload();
            } else {
                alert('Erreur : ' + (data.message || 'Impossible de sauvegarder.'));
            }
        })
        .catch(function() {
            btn.textContent = origText;
            btn.disabled = false;
            alert('Erreur réseau lors de la sauvegarde.');
        });
    });
}

// AJAX pour relancer l'analyse IA
document.querySelectorAll('[data-ajax-metier-analyse="true"]').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = this.querySelector('[type=submit]');
        var origText = btn.textContent;
        btn.textContent = '⏳ Analyse...';
        btn.disabled = true;

        fetch(this.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new FormData(this)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.textContent = origText;
            btn.disabled = false;
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erreur analyse IA : ' + (data.message || ''));
            }
        })
        .catch(function() {
            btn.textContent = origText;
            btn.disabled = false;
        });
    });
});
</script>
</body>
</html>