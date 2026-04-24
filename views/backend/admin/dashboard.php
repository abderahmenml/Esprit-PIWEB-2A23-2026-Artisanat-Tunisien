<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Dashboard | Herfa Tunisie</title>
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
            font-size: 1.4rem;
            font-weight: 800;
            margin: 0 0 0.2rem;
        }
        .page-subtitle {
            color: var(--ui-muted);
            font-size: 0.92rem;
            margin: 0;
        }
        .metric-card {
            border: 1px solid var(--ui-border);
            border-radius: 14px;
            box-shadow: 0 8px 18px rgba(20, 33, 61, 0.04);
            height: 100%;
        }
        .metric-card .card-body { padding: 1rem 1.1rem; }
        .metric-label {
            color: var(--ui-muted);
            font-size: 0.75rem;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 0.35rem;
            font-weight: 700;
        }
        .metric-value {
            color: #202637;
            font-size: 1.6rem;
            line-height: 1.15;
            font-weight: 800;
        }
        .metric-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eef3f0;
            color: var(--herfa-green);
            font-size: 1.1rem;
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
        .admin-table td {
            border-color: #edf0f5;
            vertical-align: top;
        }
        .offer-thumb {
            width: 74px;
            height: 74px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .data-note {
            color: var(--ui-muted);
            font-size: 0.8rem;
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
        <li class="nav-item active"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('offers.php')); ?>"><i class="fas fa-fw fa-briefcase"></i><span>Offres a verifier</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(app_base_url() . 'controllers/home.php'); ?>"><i class="fas fa-fw fa-home"></i><span>Retour application</span></a></li>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow-sm">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
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
                <div class="page-header d-sm-flex align-items-start justify-content-between">
                    <div>
                        <h1>Dashboard Administration</h1>
                        <p class="page-subtitle">Pilotage des offres, des candidatures et de la verification.</p>
                    </div>
                    <a href="<?php echo admin_h(admin_controller_url('offers.php')); ?>" class="btn btn-success btn-sm shadow-sm mt-2 mt-sm-0">
                        <i class="fas fa-check-circle mr-1"></i>Gerer les verifications
                    </a>
                </div>

                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?php echo admin_h($noticeType); ?> alert-dismissible fade show" role="alert">
                        <?php echo admin_h($notice); ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Offres totales</div>
                                    <div class="metric-value"><?php echo (int)$stats['total_offers']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-briefcase"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Offres verifiees</div>
                                    <div class="metric-value"><?php echo (int)$stats['verified_offers']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-check-circle"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">A verifier</div>
                                    <div class="metric-value"><?php echo (int)$stats['not_verified_offers']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-hourglass-half"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Recruteurs actifs</div>
                                    <div class="metric-value"><?php echo (int)$stats['active_recruiters']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-users"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card panel-card">
                    <div class="card-header">
                        <h6 class="panel-title"><i class="fas fa-file-alt"></i>Dernieres candidatures</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($latestApplications)): ?>
                            <div class="text-center text-muted py-4">Aucune candidature recue.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover admin-table" width="100%" cellspacing="0">
                                    <thead><tr><th>Candidat</th><th>Offre</th><th>Profil extrait</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($latestApplications as $application): ?>
                                        <?php $cvData = !empty($application['parsed_cv_data']) ? (json_decode((string)$application['parsed_cv_data'], true) ?: []) : []; ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?php echo admin_h(trim((string)$application['candidate_prenom'] . ' ' . (string)$application['candidate_nom'])); ?></div>
                                                <small class="data-note"><?php echo admin_h((string)$application['candidate_email']); ?></small>
                                            </td>
                                            <td><?php echo admin_h((string)$application['offer_title']); ?></td>
                                            <td>
                                                <div><?php echo admin_h((string)($cvData['professional_title'] ?? $cvData['full_name'] ?? '')); ?></div>
                                                <small class="data-note"><?php echo admin_h(is_array($cvData['skills'] ?? null) ? implode(', ', array_slice($cvData['skills'], 0, 5)) : (string)($cvData['skills'] ?? '')); ?></small>
                                            </td>
                                            <td><span class="badge badge-info"><?php echo admin_h((string)$application['status']); ?></span><br><small class="data-note"><?php echo admin_h((string)($application['cv_parsing_status'] ?? 'pending')); ?></small></td>
                                            <td><?php echo admin_h((string)$application['date_creation']); ?></td>
                                            <td>
                                                <?php if (!empty($application['id_offer'])): ?>
                                                    <a class="btn btn-sm btn-outline-info" href="<?php echo admin_h(app_base_url() . 'controllers/offer_emploi/applications.php?id_offer=' . (int)$application['id_offer']); ?>"><i class="fas fa-eye mr-1"></i>Voir</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card panel-card">
                    <div class="card-header">
                        <h6 class="panel-title"><i class="fas fa-briefcase"></i>Dernieres offres publiees</h6>
                    </div>
                    <div class="card-body">
                        <form method="get" class="filter-toolbar">
                            <div class="row">
                                <div class="col-md-5 mb-2"><input type="text" class="form-control" name="q" placeholder="Recherche offre, recruteur, email, projet..." value="<?php echo admin_h($search); ?>"></div>
                                <div class="col-md-3 mb-2"><select class="form-control" name="verification"><option value="">Tous les statuts</option><option value="verified" <?php echo $verificationFilter === 'verified' ? 'selected' : ''; ?>>Verifiee</option><option value="not_verified" <?php echo $verificationFilter === 'not_verified' ? 'selected' : ''; ?>>Non verifiee</option></select></div>
                                <div class="col-md-2 mb-2"><select class="form-control" name="sort"><option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Plus recentes</option><option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Plus anciennes</option><option value="budget_high" <?php echo $sort === 'budget_high' ? 'selected' : ''; ?>>Budget eleve</option><option value="budget_low" <?php echo $sort === 'budget_low' ? 'selected' : ''; ?>>Budget bas</option></select></div>
                                <div class="col-md-2 mb-2"><button class="btn btn-success btn-block" type="submit"><i class="fas fa-filter mr-1"></i>Filtrer</button></div>
                            </div>
                        </form>

                        <?php if (count($offers) === 0): ?>
                            <div class="text-center text-muted py-4">Aucune offre trouvee.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover admin-table" width="100%" cellspacing="0">
                                    <thead><tr><th>Offre</th><th>Recruteur</th><th>Budget</th><th>Statut verif.</th><th>Date</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($offers as $offer): ?>
                                        <?php $img = admin_offer_image_url((string)($offer['image_path'] ?? '')); ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-start">
                                                    <?php if ($img !== ''): ?><img src="<?php echo admin_h($img); ?>" class="offer-thumb mr-2" alt="offer image"><?php endif; ?>
                                                    <div>
                                                        <div class="font-weight-bold"><?php echo admin_h((string)$offer['titre']); ?></div>
                                                        <small class="data-note"><?php echo admin_h((string)mb_strimwidth((string)$offer['description'], 0, 90, '...')); ?></small><br>
                                                        <small class="data-note">Projet: <?php echo admin_h((string)($offer['projet_titre'] ?? 'N/A')); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div><?php echo admin_h(trim(((string)$offer['prenom']) . ' ' . ((string)$offer['nom']))); ?></div>
                                                <small class="data-note"><?php echo admin_h((string)$offer['email']); ?></small>
                                            </td>
                                            <td><?php echo admin_h((string)$offer['budget']); ?> TND</td>
                                            <td><span class="badge <?php echo admin_h(admin_verification_badge_class((string)$offer['verification_status'])); ?>"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Verifiee' : 'Non verifiee'; ?></span></td>
                                            <td><?php echo admin_h((string)($offer['created_at'] ?? '')); ?></td>
                                            <td>
                                                <a href="<?php echo admin_h(admin_controller_url('offers.php?id_offer=' . (int)$offer['id_offer'])); ?>" class="btn btn-sm btn-outline-info mb-1"><i class="fas fa-eye mr-1"></i>Detail</a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf" value="<?php echo admin_h($csrf); ?>">
                                                    <input type="hidden" name="action" value="set_verification">
                                                    <input type="hidden" name="offer_id" value="<?php echo (int)$offer['id_offer']; ?>">
                                                    <input type="hidden" name="target" value="<?php echo (string)$offer['verification_status'] === 'verified' ? 'not_verified' : 'verified'; ?>">
                                                    <button class="btn btn-sm <?php echo (string)$offer['verification_status'] === 'verified' ? 'btn-outline-warning' : 'btn-success'; ?>" type="submit" <?php echo !$schemaReady ? 'disabled' : ''; ?>><?php echo (string)$offer['verification_status'] === 'verified' ? 'Retirer' : 'Verifier'; ?></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
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
