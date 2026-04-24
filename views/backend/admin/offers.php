<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Verification des offres | Admin</title>
    <link href="<?php echo admin_h(admin_asset_url('vendor/fontawesome-free/css/all.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="<?php echo admin_h(admin_asset_url('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <style>
        :root {
            --herfa-brown: #8B5A3A;
            --herfa-green: #2E6B3E;
            --herfa-dark: #3B2314;
            --ui-bg: #f7f8fb;
            --ui-border: #e6e9f0;
            --ui-muted: #7c8498;
            --ui-text: #1f2430;
        }
        body { background: var(--ui-bg); }
        .bg-gradient-primary { background-image: linear-gradient(180deg, var(--herfa-dark) 10%, var(--herfa-brown) 100%) !important; }
        .topbar { border-bottom: 1px solid var(--ui-border); }
        .page-header {
            background: linear-gradient(135deg, #ffffff, #f3f5fb);
            border: 1px solid var(--ui-border);
            border-radius: 14px;
            padding: 1.1rem 1.2rem;
            margin-bottom: 1.4rem;
        }
        .page-header h1 {
            color: var(--ui-text);
            font-size: 1.35rem;
            font-weight: 800;
            margin: 0 0 0.2rem;
        }
        .page-subtitle {
            color: var(--ui-muted);
            font-size: 0.92rem;
            margin: 0;
        }
        .panel-card {
            border: 1px solid var(--ui-border);
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(20, 33, 61, 0.04);
            margin-bottom: 1.5rem;
        }
        .panel-card .card-header {
            border-bottom: 1px solid var(--ui-border);
            background: #fff;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            padding: 0.9rem 1rem;
        }
        .panel-title {
            display: inline-flex;
            align-items: center;
            margin: 0;
            font-size: 0.98rem;
            font-weight: 700;
            color: var(--ui-text);
        }
        .panel-title i { color: var(--herfa-green); margin-right: 0.45rem; }
        .filter-toolbar {
            background: #f8f9fd;
            border: 1px solid var(--ui-border);
            border-radius: 12px;
            padding: 0.85rem;
            margin-bottom: 1rem;
        }
        .admin-table th {
            background: #f4f6fb;
            color: #485065;
            border-color: var(--ui-border);
            font-size: 0.76rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-weight: 700;
        }
        .admin-table td { border-color: #edf0f5; vertical-align: top; }
        .offer-thumb {
            width: 64px;
            height: 64px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #dfe5f0;
        }
        .note-input { min-width: 150px; }
        .details-card { border-left: 4px solid var(--herfa-green); }
        .details-line {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px dashed #e8ebf2;
            padding: 0.45rem 0;
            font-size: 0.86rem;
        }
        .details-label { color: var(--ui-muted); margin-right: 0.6rem; }
        .details-value { color: #2a3144; font-weight: 600; text-align: right; word-break: break-word; }
        .details-description {
            margin-top: 0.9rem;
            background: #fafbfe;
            border: 1px solid var(--ui-border);
            border-radius: 10px;
            padding: 0.8rem;
            color: #3f465a;
            font-size: 0.88rem;
            line-height: 1.5;
        }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>">
            <div class="sidebar-brand-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="sidebar-brand-text mx-2">Herfa Admin</div>
        </a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item active"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('offers.php')); ?>"><i class="fas fa-fw fa-briefcase"></i><span>Offres a verifier</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(app_base_url() . 'controllers/home.php'); ?>"><i class="fas fa-fw fa-home"></i><span>Retour application</span></a></li>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                            <span class="mr-2 d-none d-lg-inline text-gray-700 small"><?php echo admin_h($adminName !== '' ? $adminName : 'Admin'); ?></span>
                            <img class="img-profile rounded-circle" src="<?php echo admin_h(admin_asset_url('img/undraw_profile.svg')); ?>" alt="admin">
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                            <a class="dropdown-item" href="<?php echo admin_h(app_base_url() . 'controllers/session_status.php?action=logout'); ?>">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <div class="page-header">
                    <h1>Gestion et verification des offres</h1>
                    <p class="page-subtitle">Controle qualite des offres publiees et suivi des decisions de moderation.</p>
                </div>

                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?php echo admin_h($noticeType); ?> alert-dismissible fade show" role="alert">
                        <?php echo admin_h($notice); ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card panel-card">
                            <div class="card-header">
                                <h6 class="panel-title"><i class="fas fa-list"></i>Liste complete des offres</h6>
                            </div>
                            <div class="card-body">
                                <form method="get" class="filter-toolbar">
                                    <div class="row">
                                        <div class="col-md-5 mb-2"><input type="text" class="form-control" name="q" value="<?php echo admin_h($search); ?>" placeholder="Recherche: titre, recruteur, email, projet"></div>
                                        <div class="col-md-3 mb-2"><select class="form-control" name="verification"><option value="">Tous les statuts</option><option value="verified" <?php echo $verificationFilter === 'verified' ? 'selected' : ''; ?>>Verifiees</option><option value="not_verified" <?php echo $verificationFilter === 'not_verified' ? 'selected' : ''; ?>>Non verifiees</option></select></div>
                                        <div class="col-md-2 mb-2"><select class="form-control" name="status"><option value="">Tous cycles</option><option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Brouillon</option><option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Publiee</option><option value="paused" <?php echo $statusFilter === 'paused' ? 'selected' : ''; ?>>En pause</option><option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Cloturee</option></select></div>
                                        <div class="col-md-1 mb-2"><select class="form-control" name="sort"><option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Rec</option><option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Anc</option><option value="budget_high" <?php echo $sort === 'budget_high' ? 'selected' : ''; ?>>B+</option><option value="budget_low" <?php echo $sort === 'budget_low' ? 'selected' : ''; ?>>B-</option></select></div>
                                        <div class="col-md-1 mb-2"><button class="btn btn-success btn-block" type="submit"><i class="fas fa-filter"></i></button></div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-hover admin-table" width="100%" cellspacing="0">
                                        <thead><tr><th>#</th><th>Offre</th><th>Auteur</th><th>Cycle</th><th>Verification</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($offers as $offer): ?>
                                            <?php $img = admin_offer_image_url((string)($offer['image_path'] ?? '')); ?>
                                            <tr>
                                                <td><?php echo (int)$offer['id_offer']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-start">
                                                        <?php if ($img !== ''): ?><img src="<?php echo admin_h($img); ?>" class="offer-thumb mr-2" alt="offer"><?php endif; ?>
                                                        <div>
                                                            <div class="font-weight-bold"><?php echo admin_h((string)$offer['titre']); ?></div>
                                                            <small class="text-muted">Budget: <?php echo admin_h((string)$offer['budget']); ?> TND | Duree: <?php echo admin_h((string)$offer['duree']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><div><?php echo admin_h(trim(((string)$offer['prenom']) . ' ' . ((string)$offer['nom']))); ?></div><small class="text-muted"><?php echo admin_h((string)$offer['email']); ?></small></td>
                                                <td><span class="badge <?php echo admin_h(admin_offer_status_badge_class((string)($offer['status'] ?? 'draft'))); ?>"><?php echo admin_h(admin_offer_status_label((string)($offer['status'] ?? 'draft'))); ?></span></td>
                                                <td><span class="badge <?php echo admin_h(admin_verification_badge_class((string)$offer['verification_status'])); ?>"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Verifiee' : 'Non verifiee'; ?></span></td>
                                                <td>
                                                    <a href="<?php echo admin_h(admin_controller_url('offers.php?id_offer=' . (int)$offer['id_offer'] . '&q=' . urlencode($search) . '&verification=' . urlencode($verificationFilter) . '&status=' . urlencode($statusFilter) . '&sort=' . urlencode($sort))); ?>" class="btn btn-sm btn-outline-info mb-1"><i class="fas fa-eye mr-1"></i>Details</a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf" value="<?php echo admin_h($csrf); ?>">
                                                        <input type="hidden" name="offer_id" value="<?php echo (int)$offer['id_offer']; ?>">
                                                        <input type="hidden" name="target" value="<?php echo (string)$offer['verification_status'] === 'verified' ? 'not_verified' : 'verified'; ?>">
                                                        <input type="text" name="moderation_note" class="form-control form-control-sm my-1 note-input" placeholder="Raison moderation / suppression">
                                                        <button class="btn btn-sm <?php echo (string)$offer['verification_status'] === 'verified' ? 'btn-outline-warning' : 'btn-success'; ?>" type="submit" name="action" value="set_verification"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Annuler' : 'Verifier'; ?></button>
                                                        <button class="btn btn-sm btn-outline-danger ml-1" type="submit" name="action" value="delete_offer" onclick="return confirm('Supprimer cette offre ? Le recruteur recevra la raison saisie.');">Supprimer</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 mb-4">
                        <div class="card panel-card details-card">
                            <div class="card-header">
                                <h6 class="panel-title"><i class="fas fa-search"></i>Details complets de l'offre</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!$selectedOffer): ?>
                                    <p class="text-muted mb-0">Selectionnez une offre depuis la liste pour afficher ses details.</p>
                                <?php else: ?>
                                    <?php $selectedImg = admin_offer_image_url((string)($selectedOffer['image_path'] ?? '')); ?>
                                    <?php if ($selectedImg !== ''): ?><img src="<?php echo admin_h($selectedImg); ?>" alt="offer image" class="img-fluid rounded mb-3"><?php endif; ?>
                                    <h5 class="font-weight-bold mb-3"><?php echo admin_h((string)$selectedOffer['titre']); ?></h5>

                                    <div class="details-line"><span class="details-label">Projet</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['projet_titre'] ?? 'N/A')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Recruteur</span><span class="details-value"><?php echo admin_h(trim(((string)$selectedOffer['prenom']) . ' ' . ((string)$selectedOffer['nom']))); ?></span></div>
                                    <div class="details-line"><span class="details-label">Email</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['email'] ?? '')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Role</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['role'] ?? '')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Budget</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['budget'] ?? '0')); ?> TND</span></div>
                                    <div class="details-line"><span class="details-label">Duree</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['duree'] ?? '')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Localisation</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['location'] ?? 'N/A')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Contact</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['contact_email'] ?? 'N/A')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Publie le</span><span class="details-value"><?php echo admin_h((string)($selectedOffer['created_at'] ?? '')); ?></span></div>
                                    <div class="details-line"><span class="details-label">Cycle</span><span class="details-value"><span class="badge <?php echo admin_h(admin_offer_status_badge_class((string)($selectedOffer['status'] ?? 'draft'))); ?>"><?php echo admin_h(admin_offer_status_label((string)($selectedOffer['status'] ?? 'draft'))); ?></span></span></div>
                                    <div class="details-line"><span class="details-label">Verification</span><span class="details-value"><span class="badge <?php echo admin_h(admin_verification_badge_class((string)$selectedOffer['verification_status'])); ?>"><?php echo (string)$selectedOffer['verification_status'] === 'verified' ? 'Verifiee' : 'Non verifiee'; ?></span></span></div>

                                    <?php if (!empty($selectedOffer['verified_at'])): ?>
                                        <div class="details-line"><span class="details-label">Verifiee le</span><span class="details-value"><?php echo admin_h((string)$selectedOffer['verified_at']); ?></span></div>
                                        <div class="details-line"><span class="details-label">Par</span><span class="details-value"><?php echo admin_h(trim(((string)$selectedOffer['verifier_prenom']) . ' ' . ((string)$selectedOffer['verifier_nom']))); ?></span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($selectedOffer['moderation_note'])): ?>
                                        <div class="details-line"><span class="details-label">Motif moderation</span><span class="details-value"><?php echo nl2br(admin_h((string)$selectedOffer['moderation_note'])); ?></span></div>
                                    <?php endif; ?>

                                    <div class="details-description">
                                        <strong>Description</strong><br>
                                        <?php echo nl2br(admin_h((string)($selectedOffer['description'] ?? ''))); ?>
                                        <?php if (!empty($selectedOffer['skills_needed'])): ?><br><br><strong>Competences:</strong> <?php echo admin_h((string)$selectedOffer['skills_needed']); ?><?php endif; ?>
                                    </div>
                                    <hr>
                                    <form method="post" class="mt-3">
                                        <input type="hidden" name="csrf" value="<?php echo admin_h($csrf); ?>">
                                        <input type="hidden" name="action" value="delete_offer">
                                        <input type="hidden" name="offer_id" value="<?php echo (int)$selectedOffer['id_offer']; ?>">
                                        <label class="small text-muted font-weight-bold">Raison de suppression (envoyee au recruteur)</label>
                                        <textarea class="form-control form-control-sm mb-2" name="delete_reason" rows="3" placeholder="Exemple: Offre non conforme aux regles de publication." required></textarea>
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Confirmer la suppression definitive de cette offre ?');">
                                            <i class="fas fa-trash-alt mr-1"></i>Supprimer cette offre
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>&copy; <?php echo date('Y'); ?> Herfa Tunisie - Admin Panel</span></div></div></footer>
    </div>
</div>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery/jquery.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('js/sb-admin-2.min.js')); ?>"></script>
</body>
</html>
