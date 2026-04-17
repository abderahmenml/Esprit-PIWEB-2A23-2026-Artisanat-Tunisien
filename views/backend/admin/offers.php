<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Vérification des offres | Admin</title>
    <link href="<?php echo admin_h(admin_asset_url('vendor/fontawesome-free/css/all.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="<?php echo admin_h(admin_asset_url('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <style>
        :root { --herfa-brown:#8B5A3A; --herfa-green:#2E6B3E; --herfa-dark:#3B2314; }
        .bg-gradient-primary { background-image: linear-gradient(180deg, var(--herfa-dark) 10%, var(--herfa-brown) 100%) !important; }
        .badge-success { background-color: var(--herfa-green); color:#fff; }
        .badge-warning { background-color: #f6c23e; color:#3B2314; }
        .offer-thumb { width:64px; height:64px; border-radius:8px; object-fit:cover; border:1px solid #ddd; }
        .details-card { border-left: 4px solid var(--herfa-green); }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>"><div class="sidebar-brand-icon"><i class="fas fa-shield-alt"></i></div><div class="sidebar-brand-text mx-2">حرفة Admin</div></a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item active"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('offers.php')); ?>"><i class="fas fa-fw fa-briefcase"></i><span>Offres à vérifier</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(app_base_url() . 'controllers/home.php'); ?>"><i class="fas fa-fw fa-home"></i><span>Retour application</span></a></li>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown"><span class="mr-2 d-none d-lg-inline text-gray-700 small"><?php echo admin_h($adminName !== '' ? $adminName : 'Admin'); ?></span><img class="img-profile rounded-circle" src="<?php echo admin_h(admin_asset_url('img/undraw_profile.svg')); ?>" alt="admin"></a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"><a class="dropdown-item" href="<?php echo admin_h(app_base_url() . 'controllers/session_status.php?action=logout'); ?>"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a></div>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4"><h1 class="h3 mb-0 text-gray-800">Gestion & Vérification des offres</h1></div>

                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?php echo admin_h($noticeType); ?> alert-dismissible fade show" role="alert"><?php echo admin_h($notice); ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Liste complète des offres</h6></div>
                            <div class="card-body">
                                <form method="get" class="row mb-3">
                                    <div class="col-md-5 mb-2"><input type="text" class="form-control" name="q" value="<?php echo admin_h($search); ?>" placeholder="Recherche... titre, recruteur, email, projet"></div>
                                    <div class="col-md-3 mb-2"><select class="form-control" name="verification"><option value="">Tous les statuts</option><option value="verified" <?php echo $verificationFilter === 'verified' ? 'selected' : ''; ?>>Vérifiées</option><option value="not_verified" <?php echo $verificationFilter === 'not_verified' ? 'selected' : ''; ?>>Non vérifiées</option></select></div>
                                    <div class="col-md-2 mb-2"><select class="form-control" name="status"><option value="">Tous cycles</option><option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Brouillon</option><option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Publiée</option><option value="paused" <?php echo $statusFilter === 'paused' ? 'selected' : ''; ?>>En pause</option><option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Clôturée</option></select></div>
                                    <div class="col-md-1 mb-2"><select class="form-control" name="sort"><option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>↓</option><option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>↑</option><option value="budget_high" <?php echo $sort === 'budget_high' ? 'selected' : ''; ?>>B+</option><option value="budget_low" <?php echo $sort === 'budget_low' ? 'selected' : ''; ?>>B-</option></select></div>
                                    <div class="col-md-1 mb-2"><button class="btn btn-success btn-block" type="submit">OK</button></div>
                                </form>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                        <thead><tr><th>#</th><th>Offre</th><th>Auteur</th><th>Cycle</th><th>Vérification</th><th>Actions</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($offers as $offer): ?>
                                            <?php $img = admin_offer_image_url((string)($offer['image_path'] ?? '')); ?>
                                            <tr>
                                                <td><?php echo (int)$offer['id_offer']; ?></td>
                                                <td><div class="d-flex align-items-start"><?php if ($img !== ''): ?><img src="<?php echo admin_h($img); ?>" class="offer-thumb mr-2" alt="offer"><?php endif; ?><div><div class="font-weight-bold"><?php echo admin_h((string)$offer['titre']); ?></div><small class="text-muted">Budget: <?php echo admin_h((string)$offer['budget']); ?> TND • Durée: <?php echo admin_h((string)$offer['duree']); ?></small></div></div></td>
                                                <td><div><?php echo admin_h(trim(((string)$offer['prenom']) . ' ' . ((string)$offer['nom']))); ?></div><small class="text-muted"><?php echo admin_h((string)$offer['email']); ?></small></td>
                                                <td><span class="badge <?php echo admin_h(admin_offer_status_badge_class((string)($offer['status'] ?? 'draft'))); ?>"><?php echo admin_h(admin_offer_status_label((string)($offer['status'] ?? 'draft'))); ?></span></td>
                                                <td><span class="badge <?php echo admin_h(admin_verification_badge_class((string)$offer['verification_status'])); ?>"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Vérifiée' : 'Non vérifiée'; ?></span></td>
                                                <td>
                                                    <a href="<?php echo admin_h(admin_controller_url('offers.php?id_offer=' . (int)$offer['id_offer'] . '&q=' . urlencode($search) . '&verification=' . urlencode($verificationFilter) . '&status=' . urlencode($statusFilter) . '&sort=' . urlencode($sort))); ?>" class="btn btn-sm btn-outline-info mb-1"><i class="fas fa-eye"></i> Détails</a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="csrf" value="<?php echo admin_h($csrf); ?>">
                                                        <input type="hidden" name="action" value="set_verification">
                                                        <input type="hidden" name="offer_id" value="<?php echo (int)$offer['id_offer']; ?>">
                                                        <input type="hidden" name="target" value="<?php echo (string)$offer['verification_status'] === 'verified' ? 'not_verified' : 'verified'; ?>">
                                                        <input type="text" name="moderation_note" class="form-control form-control-sm my-1" placeholder="Motif (optionnel)">
                                                        <button class="btn btn-sm <?php echo (string)$offer['verification_status'] === 'verified' ? 'btn-outline-warning' : 'btn-success'; ?>" type="submit"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Annuler' : 'Vérifier'; ?></button>
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
                        <div class="card shadow mb-4 details-card">
                            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Détails complets de l'offre</h6></div>
                            <div class="card-body">
                                <?php if (!$selectedOffer): ?>
                                    <p class="text-muted mb-0">Sélectionnez une offre depuis la liste pour afficher ses détails complets.</p>
                                <?php else: ?>
                                    <?php $selectedImg = admin_offer_image_url((string)($selectedOffer['image_path'] ?? '')); ?>
                                    <?php if ($selectedImg !== ''): ?><img src="<?php echo admin_h($selectedImg); ?>" alt="offer image" class="img-fluid rounded mb-3"><?php endif; ?>
                                    <h5 class="font-weight-bold"><?php echo admin_h((string)$selectedOffer['titre']); ?></h5>
                                    <p class="mb-1"><strong>Projet:</strong> <?php echo admin_h((string)($selectedOffer['projet_titre'] ?? 'N/A')); ?></p>
                                    <p class="mb-1"><strong>Recruteur:</strong> <?php echo admin_h(trim(((string)$selectedOffer['prenom']) . ' ' . ((string)$selectedOffer['nom']))); ?></p>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo admin_h((string)($selectedOffer['email'] ?? '')); ?></p>
                                    <p class="mb-1"><strong>Rôle:</strong> <?php echo admin_h((string)($selectedOffer['role'] ?? '')); ?></p>
                                    <p class="mb-1"><strong>Budget:</strong> <?php echo admin_h((string)($selectedOffer['budget'] ?? '0')); ?> TND</p>
                                    <p class="mb-1"><strong>Durée:</strong> <?php echo admin_h((string)($selectedOffer['duree'] ?? '')); ?></p>
                                    <p class="mb-1"><strong>Localisation:</strong> <?php echo admin_h((string)($selectedOffer['location'] ?? 'N/A')); ?></p>
                                    <p class="mb-1"><strong>Contact:</strong> <?php echo admin_h((string)($selectedOffer['contact_email'] ?? 'N/A')); ?></p>
                                    <p class="mb-1"><strong>Publié le:</strong> <?php echo admin_h((string)($selectedOffer['created_at'] ?? '')); ?></p>
                                    <p class="mb-1"><strong>Cycle:</strong> <span class="badge <?php echo admin_h(admin_offer_status_badge_class((string)($selectedOffer['status'] ?? 'draft'))); ?>"><?php echo admin_h(admin_offer_status_label((string)($selectedOffer['status'] ?? 'draft'))); ?></span></p>
                                    <p class="mb-1"><strong>Vérification:</strong> <span class="badge <?php echo admin_h(admin_verification_badge_class((string)$selectedOffer['verification_status'])); ?>"><?php echo (string)$selectedOffer['verification_status'] === 'verified' ? 'Vérifiée' : 'Non vérifiée'; ?></span></p>
                                    <?php if (!empty($selectedOffer['verified_at'])): ?>
                                        <p class="mb-1"><strong>Vérifiée le:</strong> <?php echo admin_h((string)$selectedOffer['verified_at']); ?></p>
                                        <p class="mb-1"><strong>Par:</strong> <?php echo admin_h(trim(((string)$selectedOffer['verifier_prenom']) . ' ' . ((string)$selectedOffer['verifier_nom']))); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($selectedOffer['moderation_note'])): ?>
                                        <p class="mb-1"><strong>Motif modération:</strong> <?php echo nl2br(admin_h((string)$selectedOffer['moderation_note'])); ?></p>
                                    <?php endif; ?>
                                    <hr>
                                    <p class="mb-1"><strong>Description:</strong></p>
                                    <p class="text-muted"><?php echo nl2br(admin_h((string)($selectedOffer['description'] ?? ''))); ?></p>
                                    <?php if (!empty($selectedOffer['skills_needed'])): ?><p class="mb-1"><strong>Compétences:</strong> <?php echo admin_h((string)$selectedOffer['skills_needed']); ?></p><?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>© <?php echo date('Y'); ?> حرفة Tunisie - Admin Panel</span></div></div></footer>
    </div>
</div>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery/jquery.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('js/sb-admin-2.min.js')); ?>"></script>
</body>
</html>
