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
