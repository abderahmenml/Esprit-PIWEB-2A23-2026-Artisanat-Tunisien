<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Dashboard | Herfa Tunisie</title>
    <link href="<?php echo admin_h(admin_asset_url('vendor/fontawesome-free/css/all.min.css')); ?>" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="<?php echo admin_h(admin_asset_url('css/sb-admin-2.min.css')); ?>" rel="stylesheet">
    <style>
        :root {
            --ink: #121725;
            --slate: #1f2937;
            --mist: #b3bac7;
            --glow: #2dd4bf;
            --sky: #60a5fa;
            --ember: #f97316;
            --sun: #facc15;
            --bg: #0b0f1e;
            --panel: rgba(16, 23, 42, 0.9);
            --panel-strong: rgba(18, 28, 52, 0.95);
            --border: rgba(148, 163, 184, 0.2);
        }
        body {
            background: radial-gradient(circle at top, rgba(45, 212, 191, 0.15), transparent 45%),
                radial-gradient(circle at 80% 10%, rgba(96, 165, 250, 0.12), transparent 40%),
                var(--bg);
            color: #e2e8f0;
            font-family: "DM Sans", sans-serif;
        }
        h1, h2, h3, h4, h5, h6, .sidebar-brand-text { font-family: "Space Grotesk", sans-serif; }
        #wrapper { min-height: 100vh; }
        .bg-gradient-primary {
            background-image: linear-gradient(160deg, #0f172a 10%, #1f2937 100%) !important;
        }
        .sidebar {
            background: linear-gradient(180deg, rgba(8, 12, 26, 0.98), rgba(17, 24, 39, 0.98));
            box-shadow: 0 18px 45px rgba(5, 8, 18, 0.6);
        }
        .sidebar .nav-link { color: #cbd5f5; transition: all 0.3s ease; }
        .sidebar .nav-link:hover {
            color: #fff;
            transform: translateX(6px);
        }
        .topbar {
            border-bottom: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(12px);
        }
        .topbar .text-gray-700 { color: #e2e8f0 !important; }
        .page-header {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95));
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.4rem 1.6rem;
            margin-bottom: 1.6rem;
            position: relative;
            overflow: hidden;
        }
        .page-header::after {
            content: "";
            position: absolute;
            inset: -40% -10% auto auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(45, 212, 191, 0.35), transparent 70%);
            animation: floatPulse 12s ease-in-out infinite;
        }
        .page-header h1 {
            color: #f8fafc;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.35rem;
        }
        .page-subtitle {
            color: #cbd5f5;
            font-size: 0.95rem;
            margin: 0;
        }
        .metric-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 18px 30px rgba(10, 16, 30, 0.4);
            height: 100%;
            animation: fadeUp 0.7s ease forwards;
        }
        .row > div:nth-child(1) .metric-card { animation-delay: 0.05s; }
        .row > div:nth-child(2) .metric-card { animation-delay: 0.12s; }
        .row > div:nth-child(3) .metric-card { animation-delay: 0.18s; }
        .row > div:nth-child(4) .metric-card { animation-delay: 0.24s; }
        .metric-card .card-body { padding: 1.2rem 1.3rem; }
        .metric-label {
            color: #94a3b8;
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 0.4rem;
            font-weight: 700;
        }
        .metric-value {
            color: #f8fafc;
            font-size: 1.7rem;
            line-height: 1.15;
            font-weight: 700;
        }
        .metric-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(96, 165, 250, 0.15);
            color: #7dd3fc;
            font-size: 1.1rem;
        }
        .panel-card {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel);
            box-shadow: 0 18px 30px rgba(10, 16, 30, 0.4);
            margin-bottom: 1.6rem;
        }
        .panel-card .card-header {
            border-bottom: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.8);
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            padding: 1rem 1.2rem;
        }
        .panel-title {
            display: inline-flex;
            align-items: center;
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: #f8fafc;
        }
        .panel-title i { color: var(--glow); margin-right: 0.5rem; }
        .filter-toolbar {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .filter-toolbar .form-control {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(148, 163, 184, 0.3);
            color: #e2e8f0;
        }
        .filter-toolbar .form-control::placeholder {
            color: rgba(226, 232, 240, 0.6);
        }
        .admin-table th {
            background: rgba(15, 23, 42, 0.9);
            color: #cbd5f5;
            border-color: var(--border);
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .admin-table td {
            border-color: rgba(148, 163, 184, 0.15);
            vertical-align: top;
            color: #e2e8f0;
        }
        .table {
            color: #e2e8f0;
        }
        .table-hover tbody tr:hover {
            background: rgba(59, 130, 246, 0.08);
        }
        .offer-thumb {
            width: 74px;
            height: 74px;
            object-fit: cover;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }
        .data-note { color: #94a3b8; font-size: 0.82rem; }
        .pill-chip {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            color: #0f172a;
            background: linear-gradient(135deg, #38bdf8, #22d3ee);
            margin-right: 0.4rem;
            margin-bottom: 0.4rem;
        }
        .progress-track {
            width: 100%;
            height: 8px;
            background: rgba(148, 163, 184, 0.2);
            border-radius: 999px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #22d3ee, #facc15);
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.35rem;
        }
        .status-dot.ok { background: #22d3ee; }
        .status-dot.warn { background: #f97316; }
        .status-dot.bad { background: #ef4444; }
        .profile-table td { vertical-align: middle; }
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
            margin-bottom: 1.8rem;
        }
        .chart-card {
            position: relative;
            overflow: hidden;
            background: var(--panel-strong);
        }
        .chart-card::after {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.35), transparent 70%);
            opacity: 0.7;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .chart-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #f8fafc;
        }
        .chart-badge {
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.7rem;
            background: rgba(45, 212, 191, 0.18);
            color: #67e8f9;
            font-weight: 700;
        }
        .ring-chart {
            width: 120px;
            height: 120px;
            margin-right: 1rem;
            position: relative;
        }
        .ring-chart svg { transform: rotate(-90deg); }
        .ring-chart .ring-bg { stroke: rgba(148, 163, 184, 0.2); }
        .ring-chart .ring-progress {
            stroke: url(#ringGradient);
            stroke-linecap: round;
            transition: stroke-dashoffset 1.2s ease;
        }
        .ring-value {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: #f8fafc;
        }
        .mini-bars {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            align-items: end;
            height: 90px;
        }
        .mini-bar {
            background: linear-gradient(180deg, #38bdf8, #f97316);
            border-radius: 999px;
            height: calc(var(--value, 0) * 1%);
            animation: barGrow 1.2s ease forwards;
        }
        .chart-meta {
            color: #94a3b8;
            font-size: 0.82rem;
        }
        .alert {
            border-radius: 12px;
        }
        .btn-success {
            background: linear-gradient(135deg, #22d3ee, #38bdf8);
            border: none;
            color: #0b1120;
            font-weight: 700;
        }
        .btn-outline-info {
            color: #7dd3fc;
            border-color: rgba(125, 211, 252, 0.6);
        }
        .table-responsive { animation: fadeUp 0.7s ease; }
        .sticky-footer { background: transparent !important; color: #94a3b8; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes barGrow {
            from { height: 0; }
            to { height: calc(var(--value, 0) * 1%); }
        }
        @keyframes floatPulse {
            0%, 100% { transform: translateY(0); opacity: 0.6; }
            50% { transform: translateY(-18px); opacity: 0.9; }
        }
        @media (max-width: 992px) {
            .metric-value { font-size: 1.4rem; }
            .page-header { padding: 1.1rem; }
        }

        :root {
            --primary-brown: #8B5A3A;
            --primary-green: #2E6B3E;
            --cream: #F5ECD7;
            --dark-brown: #3B2314;
            --light-cream: #FAF7F0;
            --sand: #C49A6C;
            --shadow-sm: 0 2px 8px rgba(59, 35, 20, 0.08);
            --shadow-md: 0 8px 24px rgba(59, 35, 20, 0.12);
            --shadow-lg: 0 16px 34px rgba(59, 35, 20, 0.16);
            --border-soft: rgba(139, 90, 58, 0.18);
        }
        body {
            background: linear-gradient(180deg, var(--light-cream), #fff8ec);
            color: var(--dark-brown);
        }
        .bg-gradient-primary {
            background-image: linear-gradient(180deg, var(--dark-brown), var(--primary-brown)) !important;
        }
        .sidebar {
            background: linear-gradient(180deg, rgba(59, 35, 20, 0.98), rgba(46, 107, 62, 0.95));
            box-shadow: var(--shadow-lg);
        }
        .sidebar .nav-link { color: var(--cream); }
        .sidebar .nav-link:hover {
            background: rgba(197, 154, 108, 0.25);
            color: #fff;
        }
        .topbar {
            background: rgba(245, 236, 215, 0.95) !important;
            border-bottom: 1px solid var(--border-soft);
            backdrop-filter: blur(12px);
        }
        .topbar .text-gray-700 { color: var(--dark-brown) !important; }
        .page-header {
            background: linear-gradient(135deg, #fff7ea, var(--cream));
            border: 1px solid rgba(197, 154, 108, 0.25);
            border-radius: 26px;
            box-shadow: var(--shadow-sm);
        }
        .page-header::after {
            background: radial-gradient(circle, rgba(197, 154, 108, 0.35), transparent 70%);
        }
        .page-header h1 { color: var(--dark-brown); }
        .page-subtitle { color: #5a4a3b; }
        .metric-card {
            background: #fffdf7;
            border-radius: 26px;
            border: 1px solid rgba(197, 154, 108, 0.2);
            box-shadow: var(--shadow-md);
        }
        .metric-label { color: #7b6a58; }
        .metric-value { color: var(--dark-brown); }
        .metric-icon {
            background: rgba(46, 107, 62, 0.15);
            color: var(--primary-green);
        }
        .panel-card {
            background: #fffdf7;
            border-radius: 26px;
            border: 1px solid rgba(197, 154, 108, 0.18);
            box-shadow: var(--shadow-md);
        }
        .panel-card .card-header {
            background: linear-gradient(90deg, #fff7ea, var(--cream));
            border-bottom: 1px solid rgba(197, 154, 108, 0.25);
        }
        .panel-title { color: var(--dark-brown); }
        .panel-title i { color: var(--primary-green); }
        .filter-toolbar {
            background: #fff7ea;
            border: 1px solid rgba(197, 154, 108, 0.25);
            border-radius: 20px;
        }
        .filter-toolbar .form-control,
        .form-control {
            background: #fff;
            border: 1px solid rgba(139, 90, 58, 0.2);
            color: var(--dark-brown);
        }
        .filter-toolbar .form-control::placeholder { color: #988773; }
        .admin-table th {
            background: var(--cream);
            color: var(--dark-brown);
            border-color: rgba(197, 154, 108, 0.3);
        }
        .admin-table td,
        .table { color: var(--dark-brown); }
        .table-hover tbody tr:hover { background: rgba(139, 90, 58, 0.06); }
        .data-note { color: #7b6a58; }
        .text-muted { color: #7b6a58 !important; }
        .pill-chip {
            background: linear-gradient(135deg, var(--primary-green), var(--sand));
            color: var(--cream);
        }
        .progress-track { background: rgba(139, 90, 58, 0.15); }
        .progress-fill { background: linear-gradient(90deg, var(--primary-green), var(--primary-brown)); }
        .status-dot.ok { background: var(--primary-green); }
        .status-dot.warn { background: #d58b2a; }
        .status-dot.bad { background: #c5544a; }
        .chart-card { background: #fffdf9; }
        .chart-card::after {
            background: radial-gradient(circle, rgba(197, 154, 108, 0.35), transparent 70%);
        }
        .chart-title { color: var(--dark-brown); }
        .chart-badge {
            background: rgba(46, 107, 62, 0.15);
            color: var(--primary-green);
        }
        .chart-meta { color: #7b6a58; }
        .spark-bars {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.45rem;
            align-items: end;
            height: 90px;
        }
        .spark-labels {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.45rem;
            margin-top: 0.45rem;
            font-size: 0.72rem;
            color: #7b6a58;
            text-align: center;
        }
        .mini-bar,
        .spark-bar {
            background: linear-gradient(180deg, var(--primary-green), var(--primary-brown));
        }
        .spark-labels { color: #7b6a58; }
        .btn-success {
            background: linear-gradient(135deg, var(--primary-green), #3f8b55);
            color: var(--cream);
        }
        .btn-outline-info {
            color: var(--primary-brown);
            border-color: rgba(139, 90, 58, 0.45);
        }
        .sticky-footer { color: #7b6a58; }
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
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('profiles.php')); ?>"><i class="fas fa-fw fa-id-badge"></i><span>Profile manager</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(admin_controller_url('offers.php')); ?>"><i class="fas fa-fw fa-briefcase"></i><span>Offres a verifier</span></a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo admin_h(app_base_url() . 'controllers/home.php'); ?>"><i class="fas fa-fw fa-home"></i><span>Retour application</span></a></li>
    </ul>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <nav class="navbar navbar-expand topbar mb-4 static-top">
                <ul class="navbar-nav ml-auto">
<li class="nav-item no-arrow">
                        <div class="nav-link d-flex align-items-center">
                            <span class="mr-2 d-none d-lg-inline text-gray-700 small"><?php echo admin_h($adminName !== '' ? $adminName : 'Admin'); ?></span>
                            <img class="img-profile rounded-circle mr-2" src="<?php echo admin_h(admin_asset_url('img/undraw_profile.svg')); ?>" alt="admin">
                            <a href="<?php echo admin_h(app_base_url() . 'controllers/session_status.php?action=logout'); ?>" class="btn btn-sm btn-outline-info" title="Logout">
                                <i class="fas fa-sign-out-alt"></i>
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
                    <div class="d-flex flex-wrap gap-2 mt-2 mt-sm-0">
                        <a href="<?php echo admin_h(admin_controller_url('offers.php')); ?>" class="btn btn-success btn-sm shadow-sm">
                            <i class="fas fa-check-circle mr-1"></i>Gerer les verifications
                        </a>
                        <a href="<?php echo admin_h(app_base_url() . 'controllers/session_status.php?action=logout'); ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </a>
                    </div>
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

                <?php
                    $offerTotal = max(1, (int)$stats['total_offers']);
                    $verifiedPct = (int)round(((int)$stats['verified_offers'] / $offerTotal) * 100);
                    $notVerifiedPct = (int)round(((int)$stats['not_verified_offers'] / $offerTotal) * 100);
                    $completionRate = (int)($profileStats['completion_rate'] ?? 0);
                    $userTotal = max(1, (int)($profileStats['total_users'] ?? 0));
                    $activeRecruiterPct = (int)round(((int)$stats['active_recruiters'] / $userTotal) * 100);
                ?>

                <div class="analytics-grid">
                    <div class="card panel-card chart-card">
                        <div class="card-body">
                            <div class="chart-header">
                                <div class="chart-title">Verification pulse</div>
                                <span class="chart-badge">Offres</span>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="ring-chart" data-value="<?php echo $verifiedPct; ?>">
                                    <svg width="120" height="120" viewBox="0 0 120 120">
                                        <defs>
                                            <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="var(--primary-green)" />
                                                <stop offset="100%" stop-color="var(--primary-brown)" />
                                            </linearGradient>
                                        </defs>
                                        <circle class="ring-bg" cx="60" cy="60" r="52" stroke-width="12" fill="none" />
                                        <circle class="ring-progress" cx="60" cy="60" r="52" stroke-width="12" fill="none" />
                                    </svg>
                                    <div class="ring-value"><?php echo $verifiedPct; ?>%</div>
                                </div>
                                <div>
                                    <div class="chart-meta">Offres verifiees: <?php echo (int)$stats['verified_offers']; ?> / <?php echo (int)$stats['total_offers']; ?></div>
                                    <div class="chart-meta">A verifier: <?php echo (int)$stats['not_verified_offers']; ?></div>
                                    <div class="chart-meta">Recruteurs actifs: <?php echo (int)$stats['active_recruiters']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card panel-card chart-card">
                        <div class="card-body">
                            <div class="chart-header">
                                <div class="chart-title">Signal completude</div>
                                <span class="chart-badge">Profils</span>
                            </div>
                            <div class="mini-bars">
                                <div class="mini-bar" style="--value: <?php echo min(100, $verifiedPct); ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo min(100, $notVerifiedPct); ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo min(100, $completionRate); ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo min(100, $activeRecruiterPct); ?>;"></div>
                            </div>
                            <div class="chart-meta mt-3">Verif. / Non verif. / Completion / Recruiters actifs</div>
                        </div>
                    </div>
                    <div class="card panel-card chart-card">
                        <div class="card-body">
                            <div class="chart-header">
                                <div class="chart-title">Candidatures (7 jours)</div>
                                <span class="chart-badge">Flux</span>
                            </div>
                            <div class="spark-bars">
                                <?php $appMax = (int)($applicationsTrend['max'] ?? 1); ?>
                                <?php foreach (($applicationsTrend['values'] ?? []) as $value): ?>
                                    <?php $height = (int)round(((int)$value / max(1, $appMax)) * 100); ?>
                                    <div class="spark-bar" style="--value: <?php echo $height; ?>%;"></div>
                                <?php endforeach; ?>
                            </div>
                            <div class="spark-labels">
                                <?php foreach (($applicationsTrend['labels'] ?? []) as $label): ?>
                                    <span><?php echo admin_h((string)$label); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card panel-card chart-card">
                        <div class="card-body">
                            <div class="chart-header">
                                <div class="chart-title">Cycle des offres</div>
                                <span class="chart-badge">Statuts</span>
                            </div>
                            <?php
                                $offerTotalCount = max(1, array_sum($offerStatusCounts));
                                $draftPct = (int)round(((int)$offerStatusCounts['draft'] / $offerTotalCount) * 100);
                                $publishedPct = (int)round(((int)$offerStatusCounts['published'] / $offerTotalCount) * 100);
                                $pausedPct = (int)round(((int)$offerStatusCounts['paused'] / $offerTotalCount) * 100);
                                $closedPct = (int)round(((int)$offerStatusCounts['closed'] / $offerTotalCount) * 100);
                            ?>
                            <div class="mini-bars">
                                <div class="mini-bar" style="--value: <?php echo $draftPct; ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo $publishedPct; ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo $pausedPct; ?>;"></div>
                                <div class="mini-bar" style="--value: <?php echo $closedPct; ?>;"></div>
                            </div>
                            <div class="chart-meta mt-3">Brouillon / Publiee / Pause / Cloturee</div>
                        </div>
                    </div>
                </div>

                <div class="page-header">
                    <div>
                        <h1>Profiles & onboarding</h1>
                        <p class="page-subtitle">Vue rapide sur la qualite des profils et l'avancement onboarding.</p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Utilisateurs</div>
                                    <div class="metric-value"><?php echo (int)$profileStats['total_users']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-user-friends"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Profils complets</div>
                                    <div class="metric-value"><?php echo (int)$profileStats['completed_profiles']; ?></div>
                                    <div class="data-note"><?php echo (int)$profileStats['completion_rate']; ?>% de completion</div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-id-badge"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Onboarding en attente</div>
                                    <div class="metric-value"><?php echo (int)$profileStats['onboarding_pending']; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-user-check"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card metric-card">
                            <div class="card-body d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="metric-label">Score profil moyen</div>
                                    <div class="metric-value"><?php echo $profileStats['avg_profile_score'] !== null ? (int)$profileStats['avg_profile_score'] . '%' : 'N/A'; ?></div>
                                </div>
                                <span class="metric-icon"><i class="fas fa-chart-line"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card panel-card">
                    <div class="card-header">
                        <h6 class="panel-title"><i class="fas fa-user-clock"></i>Profils a completer</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="pill-chip">Competences: <?php echo (int)$profileStats['with_competences']; ?></span>
                            <span class="pill-chip">Certifications: <?php echo (int)$profileStats['with_certifications']; ?></span>
                            <span class="pill-chip">Experiences: <?php echo (int)$profileStats['with_experiences']; ?></span>
                            <span class="pill-chip">Portfolio: <?php echo (int)$profileStats['with_portfolio']; ?></span>
                        </div>

                        <?php if (empty($profileAttention)): ?>
                            <div class="text-center text-muted py-4">Aucun profil incomplet detecte.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover admin-table profile-table" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Membre</th>
                                            <th>Completion</th>
                                            <th>Manques</th>
                                            <th>Onboarding</th>
                                            <th>Creation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($profileAttention as $profile): ?>
                                        <?php
                                            $completion = (int)($profile['completion'] ?? 0);
                                            $statusClass = $completion >= 70 ? 'ok' : ($completion >= 40 ? 'warn' : 'bad');
                                            $missing = $profile['missing'] ?? [];
                                            $missingLabel = empty($missing) ? 'Aucun' : implode(', ', array_slice($missing, 0, 4));
                                            $onboardingDone = !empty($profile['onboarding_completed']);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?php echo admin_h(trim((string)($profile['prenom'] ?? '') . ' ' . (string)($profile['nom'] ?? ''))); ?></div>
                                                <small class="data-note"><?php echo admin_h((string)($profile['email'] ?? '')); ?></small>
                                            </td>
                                            <td style="min-width:160px;">
                                                <div class="d-flex align-items-center">
                                                    <span class="status-dot <?php echo $statusClass; ?>"></span>
                                                    <strong><?php echo $completion; ?>%</strong>
                                                </div>
                                                <div class="progress-track mt-1">
                                                    <div class="progress-fill" style="width: <?php echo $completion; ?>%"></div>
                                                </div>
                                            </td>
                                            <td><?php echo admin_h($missingLabel); ?></td>
                                            <td>
                                                <?php if ($onboardingDone): ?>
                                                    <span class="badge badge-success">OK</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">En attente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo admin_h((string)($profile['date_creation'] ?? '')); ?></td>
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
<script>
    document.querySelectorAll('.ring-chart').forEach(function(chart) {
        var value = parseInt(chart.getAttribute('data-value') || '0', 10);
        var circle = chart.querySelector('.ring-progress');
        if (!circle) {
            return;
        }
        var radius = parseFloat(circle.getAttribute('r')) || 52;
        var circumference = 2 * Math.PI * radius;
        circle.style.strokeDasharray = circumference;
        circle.style.strokeDashoffset = circumference;
        setTimeout(function() {
            var offset = circumference - (Math.max(0, Math.min(100, value)) / 100) * circumference;
            circle.style.strokeDashoffset = offset;
        }, 200);
    });
</script>
</body>
</html>
