<?php
declare(strict_types=1);

if (!function_exists('app_header_escape')) {
    function app_header_escape(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('app_header_url')) {
    function app_header_url(string $path = ''): string
    {
        $base = function_exists('app_base_url') ? app_base_url() : '/';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_header_asset')) {
    function app_header_asset(string $path): string
    {
        return app_header_url('public/' . ltrim(str_replace('\\', '/', $path), '/'));
    }
}

if (!function_exists('app_header_styles')) {
    function app_header_styles(): string
    {
        return <<<'CSS'
        :root {
            --primary-green: #2E6B3E;
            --primary-brown: #8B5A3A;
            --cream: #F5ECD7;
            --transition-fast: 0.2s ease;
            --transition-normal: 0.3s ease;
        }
        .dashboard-navbar {
            background: rgba(59, 35, 20, 0.96) !important;
            backdrop-filter: blur(10px);
            padding: 0.8rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all var(--transition-normal);
        }
        .dashboard-navbar.scrolled {
            padding: 0.5rem 0;
            background: rgba(59, 35, 20, 0.98) !important;
        }
        .dashboard-navbar .nav-link {
            color: var(--cream) !important;
            font-weight: 500;
            margin: 0 0.3rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all var(--transition-fast);
            position: relative;
        }
        .dashboard-navbar .nav-link:hover {
            background: rgba(197, 154, 108, 0.2);
            color: #C49A6C !important;
            transform: translateY(-2px);
        }
        .dashboard-navbar .nav-link.active {
            background: var(--primary-green);
            color: white !important;
        }
        .user-greeting {
            background: rgba(197, 154, 108, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 40px;
            color: var(--cream);
            font-weight: 500;
            margin-right: 0.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-green);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .btn-logout {
            background: #DC3545 !important;
            color: white !important;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            white-space: nowrap;
        }
        .btn-logout:hover {
            background: #c82333 !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,53,69,0.3);
        }
        .app-main-navbar.dashboard-navbar {
            background: rgba(59, 35, 20, 0.96) !important;
            backdrop-filter: blur(10px);
            padding: 0.8rem 0 !important;
        }
        .app-main-navbar.dashboard-navbar .nav-link {
            color: var(--cream) !important;
            font-weight: 500 !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px !important;
        }
        .app-main-navbar.dashboard-navbar .nav-link.active {
            background: var(--primary-green) !important;
            color: #fff !important;
        }
        .app-main-navbar .btn-logout {
            background: #DC3545 !important;
            color: #fff !important;
            border-radius: 8px !important;
        }
CSS;
    }
}

if (!function_exists('app_header_nav_items')) {
    function app_header_nav_items(string $role): array
    {
        $items = [
            ['key' => 'home', 'label' => 'Accueil', 'icon' => 'fas fa-home', 'url' => app_header_url('controllers/home.php')],
            ['key' => 'projects', 'label' => 'Projets', 'icon' => 'fas fa-project-diagram', 'url' => app_header_url('controllers/projects/projets.php')],
            ['key' => 'formations', 'label' => 'Formations', 'icon' => 'fas fa-graduation-cap', 'url' => app_header_url('controllers/formation/formations.php')],
            ['key' => 'invest', 'label' => 'Investir', 'icon' => 'fas fa-chart-line', 'url' => app_header_url('controllers/invester/invest.php')],
            ['key' => 'jobs', 'label' => 'Emplois', 'icon' => 'fas fa-briefcase', 'url' => app_header_url('controllers/offer_emploi/offres.php')],
        ];

        if ($role === 'artisan') {
            $items[] = ['key' => 'my_applications', 'label' => 'Mes candidatures', 'icon' => 'fas fa-list-check', 'url' => app_header_url('controllers/offer_emploi/my_applications.php')];
        }

        if (in_array($role, ['recruteur', 'entrepreneur'], true)) {
            $items[] = ['key' => 'create_offer', 'label' => 'Créer offre', 'icon' => 'fas fa-plus-circle', 'url' => app_header_url('controllers/offer_emploi/create_offre.php')];
            $items[] = ['key' => 'recruiter_dashboard', 'label' => 'Dashboard', 'icon' => 'fas fa-table-columns', 'url' => app_header_url('controllers/offer_emploi/recruiter_dashboard.php')];
        }

        if ($role === 'admin') {
            $items[] = ['key' => 'admin', 'label' => 'Admin', 'icon' => 'fas fa-shield-alt', 'url' => app_header_url('controllers/admin/dashboard.php')];
        }

        $items[] = ['key' => 'profile', 'label' => 'Profil', 'icon' => 'fas fa-user', 'url' => app_header_url('controllers/user/profile.php')];

        return $items;
    }
}

if (!function_exists('render_app_header')) {
    function render_app_header(string $active = ''): void
    {
        $role = (string)($_SESSION['role'] ?? 'artisan');
        $nom = trim((string)($_SESSION['nom'] ?? 'Utilisateur'));
        $prenom = trim((string)($_SESSION['prenom'] ?? ''));
        $fullName = trim($prenom . ' ' . $nom);
        $initials = mb_strtoupper(mb_substr($prenom !== '' ? $prenom : $nom, 0, 1) . mb_substr($nom, 0, 1));
        $initials = trim($initials) !== '' ? $initials : 'U';
        ?>
        <nav class="navbar navbar-expand-lg dashboard-navbar app-main-navbar sticky-top" id="mainNavbar">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo app_header_escape(app_header_url('controllers/home.php')); ?>">
                    <img src="<?php echo app_header_escape(app_header_asset('assets/img/logo_herfa.png')); ?>" alt="Logo حرفة" height="40" style="margin-right: 0.8rem;">
                    <span style="color: var(--cream); font-weight: 700; font-size: 1.3rem;">حرفة Tunisie</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Menu de navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center gap-2">
                        <?php foreach (app_header_nav_items($role) as $item): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $active === $item['key'] ? 'active' : ''; ?>" href="<?php echo app_header_escape($item['url']); ?>">
                                    <i class="<?php echo app_header_escape($item['icon']); ?> me-1"></i><?php echo app_header_escape($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li class="nav-item">
                            <span class="user-greeting">
                                <span class="user-avatar-small"><?php echo app_header_escape($initials); ?></span>
                                <span><?php echo app_header_escape($fullName !== '' ? $fullName : 'Utilisateur'); ?></span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="btn-logout" href="<?php echo app_header_escape(app_header_url('controllers/session_status.php?action=logout')); ?>">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php
    }
}
