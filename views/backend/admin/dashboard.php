<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Dashboard | حرفة Tunisie</title>
    <link href="<?php echo admin_h(admin_asset_url('vendor/fontawesome-free/css/all.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="<?php echo admin_h(admin_asset_url('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <style>
        :root { --herfa-brown:#8B5A3A; --herfa-green:#2E6B3E; --herfa-dark:#3B2314; }
        .bg-gradient-primary { background-image: linear-gradient(180deg, var(--herfa-dark) 10%, var(--herfa-brown) 100%) !important; }
        .text-herfa-green { color: var(--herfa-green) !important; }
        .badge-success { background-color: var(--herfa-green); color: #fff; }
        .badge-warning { background-color: #f6c23e; color: #3B2314; }
        .offer-thumb { width: 76px; height: 76px; object-fit: cover; border-radius: 10px; border: 1px solid #e3e6f0; }
        .card-metric { border-left: 5px solid var(--herfa-green); }
    </style>
</head>
<body id="page-top">
<div id="wrapper">
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>">
            <div class="sidebar-brand-icon"><i class="fas fa-shield-alt"></i></div>
            <div class="sidebar-brand-text mx-2">حرفة Admin</div>
        </a>
        <hr class="sidebar-divider my-0">
        <li class="nav-item active"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('dashboard.php')); ?>"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('offers.php')); ?>"><i class="fas fa-fw fa-briefcase"></i><span>Offres à vérifier</span></a></li>
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
                            <a class="dropdown-item" href="<?php echo admin_h(app_base_url() . 'controllers/session_status.php?action=logout'); ?>"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h1 class="h3 mb-0 text-gray-800">Dashboard Admin</h1>
                    <a href="<?php echo admin_h(admin_controller_url('offers.php')); ?>" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                        <i class="fas fa-check-circle fa-sm text-white-50"></i> Gérer les vérifications
                    </a>
                </div>

                <?php if ($notice !== ''): ?>
                    <div class="alert alert-<?php echo admin_h($noticeType); ?> alert-dismissible fade show" role="alert">
                        <?php echo admin_h($notice); ?>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-metric shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-herfa-green text-uppercase mb-1">Offres totales</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$stats['total_offers']; ?></div></div><div class="col-auto"><i class="fas fa-briefcase fa-2x text-gray-300"></i></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-metric shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Offres vérifiées</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$stats['verified_offers']; ?></div></div><div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-metric shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">À vérifier</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$stats['not_verified_offers']; ?></div></div><div class="col-auto"><i class="fas fa-hourglass-half fa-2x text-gray-300"></i></div></div></div></div></div>
                    <div class="col-xl-3 col-md-6 mb-4"><div class="card card-metric shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Recruteurs actifs</div><div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo (int)$stats['active_recruiters']; ?></div></div><div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div></div></div></div></div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-herfa-green">Dernières offres publiées</h6></div>
                    <div class="card-body">
                        <form method="get" class="row g-2 mb-3">
                            <div class="col-md-5 mb-2"><input type="text" class="form-control" name="q" placeholder="Recherche offre, recruteur, email, projet..." value="<?php echo admin_h($search); ?>"></div>
                            <div class="col-md-3 mb-2"><select class="form-control" name="verification"><option value="">Tous les statuts</option><option value="verified" <?php echo $verificationFilter === 'verified' ? 'selected' : ''; ?>>Vérifiée</option><option value="not_verified" <?php echo $verificationFilter === 'not_verified' ? 'selected' : ''; ?>>Non vérifiée</option></select></div>
                            <div class="col-md-2 mb-2"><select class="form-control" name="sort"><option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Plus récentes</option><option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Plus anciennes</option><option value="budget_high" <?php echo $sort === 'budget_high' ? 'selected' : ''; ?>>Budget élevé</option><option value="budget_low" <?php echo $sort === 'budget_low' ? 'selected' : ''; ?>>Budget bas</option></select></div>
                            <div class="col-md-2 mb-2"><button class="btn btn-success btn-block" type="submit">Filtrer</button></div>
                        </form>

                        <?php if (count($offers) === 0): ?>
                            <div class="text-center text-muted py-4">Aucune offre trouvée.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead><tr><th>Offre</th><th>Recruteur</th><th>Budget</th><th>Statut vérif.</th><th>Date</th><th>Action</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($offers as $offer): ?>
                                        <?php $img = admin_offer_image_url((string)($offer['image_path'] ?? '')); ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-start gap-2">
                                                    <?php if ($img !== ''): ?><img src="<?php echo admin_h($img); ?>" class="offer-thumb mr-2" alt="offer image"><?php endif; ?>
                                                    <div><div class="font-weight-bold"><?php echo admin_h((string)$offer['titre']); ?></div><small class="text-muted"><?php echo admin_h((string)mb_strimwidth((string)$offer['description'], 0, 90, '...')); ?></small><br><small class="text-muted">Projet: <?php echo admin_h((string)($offer['projet_titre'] ?? 'N/A')); ?></small></div>
                                                </div>
                                            </td>
                                            <td><div><?php echo admin_h(trim(((string)$offer['prenom']) . ' ' . ((string)$offer['nom']))); ?></div><small class="text-muted"><?php echo admin_h((string)$offer['email']); ?></small></td>
                                            <td><?php echo admin_h((string)$offer['budget']); ?> TND</td>
                                            <td><span class="badge <?php echo admin_h(admin_verification_badge_class((string)$offer['verification_status'])); ?>"><?php echo (string)$offer['verification_status'] === 'verified' ? 'Vérifiée' : 'Non vérifiée'; ?></span></td>
                                            <td><?php echo admin_h((string)($offer['created_at'] ?? '')); ?></td>
                                            <td>
                                                <a href="<?php echo admin_h(admin_controller_url('offers.php?id_offer=' . (int)$offer['id_offer'])); ?>" class="btn btn-sm btn-outline-info mb-1"><i class="fas fa-eye"></i></a>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf" value="<?php echo admin_h($csrf); ?>">
                                                    <input type="hidden" name="action" value="set_verification">
                                                    <input type="hidden" name="offer_id" value="<?php echo (int)$offer['id_offer']; ?>">
                                                    <input type="hidden" name="target" value="<?php echo (string)$offer['verification_status'] === 'verified' ? 'not_verified' : 'verified'; ?>">
                                                    <button class="btn btn-sm <?php echo (string)$offer['verification_status'] === 'verified' ? 'btn-outline-warning' : 'btn-success'; ?>" type="submit" <?php echo !$schemaReady ? 'disabled' : ''; ?>><?php echo (string)$offer['verification_status'] === 'verified' ? 'Retirer vérif.' : 'Vérifier'; ?></button>
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

        <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>© <?php echo date('Y'); ?> حرفة Tunisie - Admin Panel</span></div></div></footer>
    </div>
</div>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery/jquery.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/bootstrap/js/bootstrap.bundle.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('vendor/jquery-easing/jquery.easing.min.js')); ?>"></script>
<script src="<?php echo admin_h(admin_asset_url('js/sb-admin-2.min.js')); ?>"></script>
</body>
</html>
