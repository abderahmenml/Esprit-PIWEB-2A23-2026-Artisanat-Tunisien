<?php

declare(strict_types=1);

function admin_ensure_offer_verification_schema(PDO $pdo): void
{
    $hasVerificationColumn = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verification_status'")->fetch();
    if (!$hasVerificationColumn) {
        $pdo->exec("ALTER TABLE offre_emploi ADD COLUMN verification_status ENUM('not_verified','verified') NOT NULL DEFAULT 'not_verified' AFTER status");
    }

    $hasVerifiedAt = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verified_at'")->fetch();
    if (!$hasVerifiedAt) {
        $pdo->exec("ALTER TABLE offre_emploi ADD COLUMN verified_at DATETIME NULL AFTER verification_status");
    }

    $hasVerifiedBy = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'verified_by'")->fetch();
    if (!$hasVerifiedBy) {
        $pdo->exec("ALTER TABLE offre_emploi ADD COLUMN verified_by INT NULL AFTER verified_at");
    }

    $hasModerationNote = (bool)$pdo->query("SHOW COLUMNS FROM offre_emploi LIKE 'moderation_note'")->fetch();
    if (!$hasModerationNote) {
        $pdo->exec("ALTER TABLE offre_emploi ADD COLUMN moderation_note TEXT NULL AFTER verified_by");
    }

    $pdo->exec("UPDATE offre_emploi SET verification_status = 'not_verified' WHERE verification_status IS NULL OR verification_status = ''");
}

function admin_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function admin_column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function admin_get_profile_dashboard_stats(PDO $pdo): array
{
    $stats = [
        'total_users' => 0,
        'completed_profiles' => 0,
        'completion_rate' => 0,
        'with_competences' => 0,
        'with_certifications' => 0,
        'with_experiences' => 0,
        'with_portfolio' => 0,
        'onboarding_pending' => 0,
        'avg_profile_score' => null,
    ];

    if (!admin_table_exists($pdo, 'user')) {
        return $stats;
    }

    $stats['total_users'] = (int)$pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();

    if (admin_table_exists($pdo, 'competences') && admin_column_exists($pdo, 'competences', 'id_user')) {
        $stats['with_competences'] = (int)$pdo->query('SELECT COUNT(DISTINCT id_user) FROM competences WHERE id_user > 0')->fetchColumn();
    }

    if (admin_table_exists($pdo, 'certification') && admin_column_exists($pdo, 'certification', 'id_user')) {
        $stats['with_certifications'] = (int)$pdo->query('SELECT COUNT(DISTINCT id_user) FROM certification WHERE id_user > 0')->fetchColumn();
    }

    if (admin_table_exists($pdo, 'experience') && admin_column_exists($pdo, 'experience', 'id_user')) {
        $stats['with_experiences'] = (int)$pdo->query('SELECT COUNT(DISTINCT id_user) FROM experience WHERE id_user > 0')->fetchColumn();
    }

    if (admin_table_exists($pdo, 'portfolio_files') && admin_column_exists($pdo, 'portfolio_files', 'id_user')) {
        $stats['with_portfolio'] = (int)$pdo->query('SELECT COUNT(DISTINCT id_user) FROM portfolio_files WHERE id_user > 0')->fetchColumn();
    }

    if (admin_table_exists($pdo, 'artisan_onboarding')) {
        $stats['onboarding_pending'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM artisan_onboarding WHERE completed_at IS NULL OR completed_at = ''"
        )->fetchColumn();
    }

    if (admin_table_exists($pdo, 'profil_metrics') && admin_column_exists($pdo, 'profil_metrics', 'profile_score')) {
        $avgScore = $pdo->query('SELECT AVG(profile_score) FROM profil_metrics')->fetchColumn();
        $stats['avg_profile_score'] = $avgScore !== false ? (int)round((float)$avgScore) : null;
    }

    if (!admin_table_exists($pdo, 'profil_professionnel')) {
        return $stats;
    }

    $conditions = [];
    foreach (['specialite', 'bio', 'ville'] as $column) {
        if (admin_column_exists($pdo, 'profil_professionnel', $column)) {
            $conditions[] = "COALESCE(p.$column, '') <> ''";
        }
    }

    if (admin_table_exists($pdo, 'competences') && admin_column_exists($pdo, 'competences', 'id_user')) {
        $conditions[] = '(SELECT COUNT(*) FROM competences c WHERE c.id_user = u.id_user) > 0';
    }

    if (!empty($conditions)) {
        $sql = 'SELECT COUNT(*) FROM user u LEFT JOIN profil_professionnel p ON p.id_user = u.id_user WHERE ' . implode(' AND ', $conditions);
        $stats['completed_profiles'] = (int)$pdo->query($sql)->fetchColumn();
        if ($stats['total_users'] > 0) {
            $stats['completion_rate'] = (int)round(($stats['completed_profiles'] / $stats['total_users']) * 100);
        }
    }

    return $stats;
}

function admin_fetch_profile_attention(PDO $pdo, int $limit = 8): array
{
    if (!admin_table_exists($pdo, 'user')) {
        return [];
    }

    $hasProfile = admin_table_exists($pdo, 'profil_professionnel');
    $profileFields = [];
    foreach (['specialite', 'bio', 'ville'] as $column) {
        if ($hasProfile && admin_column_exists($pdo, 'profil_professionnel', $column)) {
            $profileFields[] = $column;
        }
    }

    $hasCompetences = admin_table_exists($pdo, 'competences') && admin_column_exists($pdo, 'competences', 'id_user');
    $hasCertifications = admin_table_exists($pdo, 'certification') && admin_column_exists($pdo, 'certification', 'id_user');
    $hasExperiences = admin_table_exists($pdo, 'experience') && admin_column_exists($pdo, 'experience', 'id_user');
    $hasPortfolio = admin_table_exists($pdo, 'portfolio_files') && admin_column_exists($pdo, 'portfolio_files', 'id_user');
    $hasOnboarding = admin_table_exists($pdo, 'artisan_onboarding');

    $select = [
        'u.id_user',
        'u.nom',
        'u.prenom',
        'u.email',
        'u.date_creation'
    ];

    if ($hasProfile) {
        foreach ($profileFields as $column) {
            $select[] = 'p.' . $column;
        }
    }

    if ($hasCompetences) {
        $select[] = '(SELECT COUNT(*) FROM competences c WHERE c.id_user = u.id_user) AS competence_count';
    } else {
        $select[] = '0 AS competence_count';
    }

    if ($hasCertifications) {
        $select[] = '(SELECT COUNT(*) FROM certification c WHERE c.id_user = u.id_user) AS certification_count';
    } else {
        $select[] = '0 AS certification_count';
    }

    if ($hasExperiences) {
        $select[] = '(SELECT COUNT(*) FROM experience e WHERE e.id_user = u.id_user) AS experience_count';
    } else {
        $select[] = '0 AS experience_count';
    }

    if ($hasPortfolio) {
        $select[] = '(SELECT COUNT(*) FROM portfolio_files f WHERE f.id_user = u.id_user) AS portfolio_count';
    } else {
        $select[] = '0 AS portfolio_count';
    }

    if ($hasOnboarding) {
        $select[] = 'ao.completed_at AS onboarding_completed_at';
    } else {
        $select[] = 'NULL AS onboarding_completed_at';
    }

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM user u';
    if ($hasProfile) {
        $sql .= ' LEFT JOIN profil_professionnel p ON p.id_user = u.id_user';
    }
    if ($hasOnboarding) {
        $sql .= ' LEFT JOIN artisan_onboarding ao ON ao.user_id = u.id_user';
    }
    $sql .= ' ORDER BY u.date_creation DESC LIMIT 60';

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $scorableBase = count($profileFields)
        + ($hasCompetences ? 1 : 0)
        + ($hasCertifications ? 1 : 0)
        + ($hasExperiences ? 1 : 0)
        + ($hasPortfolio ? 1 : 0)
        + ($hasOnboarding ? 1 : 0);

    $items = [];
    foreach ($rows as $row) {
        $filled = 0;
        $missing = [];

        foreach ($profileFields as $column) {
            $value = trim((string)($row[$column] ?? ''));
            if ($value !== '') {
                $filled++;
            } else {
                $missing[] = ucfirst($column);
            }
        }

        if ($hasCompetences) {
            if ((int)$row['competence_count'] > 0) {
                $filled++;
            } else {
                $missing[] = 'Competences';
            }
        }

        if ($hasCertifications) {
            if ((int)$row['certification_count'] > 0) {
                $filled++;
            } else {
                $missing[] = 'Certifications';
            }
        }

        if ($hasExperiences) {
            if ((int)$row['experience_count'] > 0) {
                $filled++;
            } else {
                $missing[] = 'Experiences';
            }
        }

        if ($hasPortfolio) {
            if ((int)$row['portfolio_count'] > 0) {
                $filled++;
            } else {
                $missing[] = 'Portfolio';
            }
        }

        $onboardingCompleted = true;
        if ($hasOnboarding) {
            $onboardingCompleted = !empty($row['onboarding_completed_at']);
            if ($onboardingCompleted) {
                $filled++;
            } else {
                $missing[] = 'Onboarding';
            }
        }

        $completion = $scorableBase > 0 ? (int)round(($filled / $scorableBase) * 100) : 0;
        $needsAttention = $completion < 70 || !$onboardingCompleted;

        if (!$needsAttention) {
            continue;
        }

        $row['completion'] = $completion;
        $row['missing'] = $missing;
        $row['onboarding_completed'] = $onboardingCompleted;
        $items[] = $row;
    }

    usort($items, static function (array $a, array $b): int {
        if ($a['completion'] === $b['completion']) {
            return strcmp((string)($b['date_creation'] ?? ''), (string)($a['date_creation'] ?? ''));
        }
        return $a['completion'] <=> $b['completion'];
    });

    $limit = max(1, min(20, $limit));
    return array_slice($items, 0, $limit);
}

function admin_get_offer_dashboard_stats(PDO $pdo): array
{
    return [
        'total_offers' => (int)$pdo->query("SELECT COUNT(*) FROM offre_emploi")->fetchColumn(),
        'verified_offers' => (int)$pdo->query("SELECT COUNT(*) FROM offre_emploi WHERE verification_status = 'verified'")->fetchColumn(),
        'not_verified_offers' => (int)$pdo->query("SELECT COUNT(*) FROM offre_emploi WHERE verification_status = 'not_verified'")->fetchColumn(),
        'active_recruiters' => (int)$pdo->query("SELECT COUNT(DISTINCT id_recruteur) FROM offre_emploi")->fetchColumn(),
        'total_applications' => (int)$pdo->query("SELECT COUNT(*) FROM application")->fetchColumn(),
    ];
}

function admin_fetch_latest_applications(PDO $pdo, int $limit = 8): array
{
    $limit = max(1, min(50, $limit));
    $stmt = $pdo->prepare(
        "SELECT
            a.id AS id_application,
            a.status,
            a.date_creation,
            a.parsed_cv_data,
            a.cv_parsing_status,
            a.cv_file_name,
            candidate.nom AS candidate_nom,
            candidate.prenom AS candidate_prenom,
            candidate.email AS candidate_email,
            o.id_offer,
            o.titre AS offer_title,
            recruiter.nom AS recruiter_nom,
            recruiter.prenom AS recruiter_prenom
         FROM application a
         LEFT JOIN application_offre ao ON ao.id_application = a.id
         LEFT JOIN offre_emploi o ON o.id_offer = ao.id_offre
         LEFT JOIN `user` candidate ON candidate.id_user = a.id_user
         LEFT JOIN `user` recruiter ON recruiter.id_user = o.id_recruteur
         ORDER BY a.date_creation DESC
         LIMIT {$limit}"
    );
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function admin_fetch_offers(PDO $pdo, string $search = '', string $verification = '', string $status = '', string $sort = 'latest', int $limit = 50): array
{
    $sortMap = [
        'latest' => 'o.created_at DESC, o.id_offer DESC',
        'oldest' => 'o.created_at ASC, o.id_offer ASC',
        'budget_high' => 'o.budget DESC, o.id_offer DESC',
        'budget_low' => 'o.budget ASC, o.id_offer DESC',
    ];
    $orderBy = $sortMap[$sort] ?? $sortMap['latest'];

    $sql = "SELECT
                o.id_offer,
                o.titre,
                o.description,
                o.budget,
                o.duree,
                o.image_path,
                o.location,
                o.contact_email,
                o.skills_needed,
                o.status,
                o.verification_status,
                o.verified_at,
                o.created_at,
                u.id_user,
                u.nom,
                u.prenom,
                u.email,
                u.role,
                p.titre AS projet_titre,
                verifier.nom AS verifier_nom,
                verifier.prenom AS verifier_prenom
            FROM offre_emploi o
            LEFT JOIN `user` u ON u.id_user = o.id_recruteur
            LEFT JOIN projet p ON p.id = o.id_projet
            LEFT JOIN `user` verifier ON verifier.id_user = o.verified_by
            WHERE 1=1";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (o.titre LIKE ? OR o.description LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ? OR u.email LIKE ? OR p.titre LIKE ?)";
        $needle = '%' . $search . '%';
        $params = array_merge($params, [$needle, $needle, $needle, $needle, $needle, $needle]);
    }

    if (in_array($verification, ['verified', 'not_verified'], true)) {
        $sql .= " AND o.verification_status = ?";
        $params[] = $verification;
    }

    if (in_array($status, ['draft', 'published', 'paused', 'closed'], true)) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }

    $limit = max(1, min(200, $limit));
    $sql .= " ORDER BY {$orderBy} LIMIT {$limit}";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function admin_fetch_offer_by_id(PDO $pdo, int $offerId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT
            o.*,
            u.nom,
            u.prenom,
            u.email,
            u.role,
            p.titre AS projet_titre,
            verifier.nom AS verifier_nom,
            verifier.prenom AS verifier_prenom
         FROM offre_emploi o
         LEFT JOIN `user` u ON u.id_user = o.id_recruteur
         LEFT JOIN projet p ON p.id = o.id_projet
         LEFT JOIN `user` verifier ON verifier.id_user = o.verified_by
         WHERE o.id_offer = ?
         LIMIT 1"
    );
    $stmt->execute([$offerId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function admin_get_offer_status_counts(PDO $pdo): array
{
    $defaults = [
        'draft' => 0,
        'published' => 0,
        'paused' => 0,
        'closed' => 0,
    ];

    try {
        $rows = $pdo->query("SELECT status, COUNT(*) AS total FROM offre_emploi GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $status = (string)($row['status'] ?? '');
            if (array_key_exists($status, $defaults)) {
                $defaults[$status] = (int)$row['total'];
            }
        }
    } catch (Throwable $e) {
        return $defaults;
    }

    return $defaults;
}

function admin_update_offer_verification(PDO $pdo, int $offerId, bool $verified, int $adminId, ?string $moderationNote = null): bool
{
    $status = $verified ? 'verified' : 'not_verified';
    $verifiedAt = $verified ? date('Y-m-d H:i:s') : null;
    $verifiedBy = $verified ? $adminId : null;
    $note = trim((string)$moderationNote);
    $note = $note !== '' ? $note : null;

    $stmt = $pdo->prepare(
        "UPDATE offre_emploi
         SET verification_status = ?, verified_at = ?, verified_by = ?, moderation_note = ?
         WHERE id_offer = ?"
    );

    return $stmt->execute([$status, $verifiedAt, $verifiedBy, $note, $offerId]);
}

function admin_delete_offer_with_reason(PDO $pdo, int $offerId, int $adminId, string $reason): bool
{
    $reason = trim($reason);
    if ($offerId <= 0 || $adminId <= 0 || $reason === '') {
        return false;
    }

    try {
        $offerStmt = $pdo->prepare(
            "SELECT o.id_offer, o.titre, o.id_recruteur
             FROM offre_emploi o
             WHERE o.id_offer = ?
             LIMIT 1"
        );
        $offerStmt->execute([$offerId]);
        $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            return false;
        }

        $recruiterId = (int)($offer['id_recruteur'] ?? 0);
        if ($recruiterId <= 0) {
            return false;
        }

        $title = trim((string)($offer['titre'] ?? 'Offre'));
        if ($title === '') {
            $title = 'Offre';
        }

        $pdo->beginTransaction();

        $deleteStmt = $pdo->prepare("DELETE FROM offre_emploi WHERE id_offer = ?");
        $deleteStmt->execute([$offerId]);

        if ($deleteStmt->rowCount() < 1) {
            $pdo->rollBack();
            return false;
        }

        $hasNotificationsTable = (bool)$pdo->query("SHOW TABLES LIKE 'notifications'")->fetch();
        if ($hasNotificationsTable) {
            $message = "Votre offre \"" . $title . "\" a ete supprimee par un administrateur.\nRaison: " . $reason;
            $notifStmt = $pdo->prepare(
                "INSERT INTO notifications (user_id, title, message, type, is_read)
                 VALUES (?, ?, ?, 'warning', 0)"
            );
            $notifStmt->execute([$recruiterId, 'Offre supprimee par moderation', $message]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('admin_delete_offer_with_reason failed: ' . $e->getMessage());
        return false;
    }
}

function admin_h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_verification_badge_class(string $verificationStatus): string
{
    return $verificationStatus === 'verified' ? 'badge-success' : 'badge-warning';
}

function admin_offer_status_badge_class(string $status): string
{
    return match ($status) {
        'published' => 'badge-success',
        'paused' => 'badge-warning',
        'closed' => 'badge-secondary',
        default => 'badge-info',
    };
}

function admin_offer_status_label(string $status): string
{
    return match ($status) {
        'draft' => 'Brouillon',
        'published' => 'Publiée',
        'paused' => 'En pause',
        'closed' => 'Clôturée',
        default => ucfirst($status),
    };
}

function admin_offer_image_url(?string $path): string
{
    $value = trim((string)$path);
    if ($value === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $value) === 1 || str_starts_with($value, 'data:')) {
        return $value;
    }
    $value = str_replace('\\', '/', $value);
    if (str_starts_with($value, '/')) {
        return $value;
    }
    if (str_starts_with($value, 'uploads/')) {
        return app_base_url() . 'controllers/offer_emploi/' . $value;
    }
    return app_base_url() . ltrim($value, '/');
}

function admin_build_daily_trend(array $rows, string $dateKey, int $days = 7): array
{
    $days = max(1, min(30, $days));
    $today = new DateTimeImmutable('today');
    $start = $today->sub(new DateInterval('P' . ($days - 1) . 'D'));

    $labels = [];
    $values = array_fill(0, $days, 0);
    $index = [];

    for ($i = 0; $i < $days; $i++) {
        $day = $start->add(new DateInterval('P' . $i . 'D'));
        $key = $day->format('Y-m-d');
        $labels[] = $day->format('D');
        $index[$key] = $i;
    }

    foreach ($rows as $row) {
        $value = trim((string)($row[$dateKey] ?? ''));
        if ($value === '') {
            continue;
        }
        $key = substr($value, 0, 10);
        if (isset($index[$key])) {
            $values[$index[$key]]++;
        }
    }

    $max = max(1, max($values));

    return [
        'labels' => $labels,
        'values' => $values,
        'max' => $max,
    ];
}
