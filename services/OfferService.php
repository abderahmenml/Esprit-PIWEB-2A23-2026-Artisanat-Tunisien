<?php
declare(strict_types=1);

final class OfferService
{
    public function __construct(private PDO $pdo)
    {
    }

    private function hasColumn(string $column): bool
    {
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM offre_emploi LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    }

    public function getOfferById(int $offerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre
             FROM offre_emploi o
             JOIN `user` u ON o.id_recruteur = u.id_user
             LEFT JOIN projet p ON p.id = o.id_projet
             WHERE o.id_offer = ?
             LIMIT 1'
        );
        $stmt->execute([$offerId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getOffers(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre
                FROM offre_emploi o
                JOIN `user` u ON o.id_recruteur = u.id_user
                LEFT JOIN projet p ON p.id = o.id_projet
                WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
            $needle = '%' . $filters['search'] . '%';
            $params[] = $needle;
            $params[] = $needle;
        }

        if (!empty($filters['competence'])) {
            $sql .= ' AND (o.skills_needed LIKE ? OR o.titre LIKE ? OR o.description LIKE ?)';
            $needle = '%' . $filters['competence'] . '%';
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
        }

        if (!empty($filters['verification']) && in_array($filters['verification'], ['verified', 'not_verified'], true) && $this->hasColumn('verification_status')) {
            $sql .= ' AND o.verification_status = ?';
            $params[] = $filters['verification'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'published', 'paused', 'closed', 'active', 'open', 'completed', 'actif'], true)) {
            $sql .= ' AND o.status = ?';
            $params[] = $filters['status'];
        }

        $sort = (string)($filters['sort'] ?? 'latest');
        $sortMap = [
            'latest' => $this->hasColumn('created_at') ? 'o.created_at DESC, o.id_offer DESC' : 'o.id_offer DESC',
            'oldest' => $this->hasColumn('created_at') ? 'o.created_at ASC, o.id_offer ASC' : 'o.id_offer ASC',
            'budget_high' => 'o.budget DESC, o.id_offer DESC',
            'budget_low' => 'o.budget ASC, o.id_offer DESC',
            'title_asc' => 'o.titre ASC, o.id_offer DESC',
            'title_desc' => 'o.titre DESC, o.id_offer DESC',
        ];
        $orderBy = $sortMap[$sort] ?? $sortMap['latest'];

        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $sql .= ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countOffers(array $filters = []): int
    {
        $sql = 'SELECT COUNT(DISTINCT o.id_offer) AS total FROM offre_emploi o WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
            $needle = '%' . $filters['search'] . '%';
            $params[] = $needle;
            $params[] = $needle;
        }

        if (!empty($filters['competence'])) {
            $sql .= ' AND (o.skills_needed LIKE ? OR o.titre LIKE ? OR o.description LIKE ?)';
            $needle = '%' . $filters['competence'] . '%';
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
        }

        if (!empty($filters['verification']) && in_array($filters['verification'], ['verified', 'not_verified'], true) && $this->hasColumn('verification_status')) {
            $sql .= ' AND o.verification_status = ?';
            $params[] = $filters['verification'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'published', 'paused', 'closed', 'active', 'open', 'completed', 'actif'], true)) {
            $sql .= ' AND o.status = ?';
            $params[] = $filters['status'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    public function getRecruiterOffers(int $recruiterId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT o.id_offer, o.titre, o.description, o.budget, o.duree, o.status, o.verification_status,
                    o.created_at, o.location, o.contact_email, o.views_count, o.applications_count,
                    p.titre AS projet_titre
             FROM offre_emploi o
             LEFT JOIN projet p ON p.id = o.id_projet
             WHERE o.id_recruteur = ?
             ORDER BY o.id_offer DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$recruiterId]);
        return $stmt->fetchAll();
    }

    public function getRecruiterOfferStats(int $recruiterId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total_offers,
                SUM(CASE WHEN status IN ("open","active","actif","published") THEN 1 ELSE 0 END) AS open_offers,
                SUM(CASE WHEN status IN ("closed","completed") THEN 1 ELSE 0 END) AS closed_offers,
                SUM(applications_count) AS total_applications,
                SUM(views_count) AS total_views
             FROM offre_emploi
             WHERE id_recruteur = ?'
        );
        $stmt->execute([$recruiterId]);
        $row = $stmt->fetch() ?: [];

        return [
            'total_offers' => (int)($row['total_offers'] ?? 0),
            'open_offers' => (int)($row['open_offers'] ?? 0),
            'closed_offers' => (int)($row['closed_offers'] ?? 0),
            'total_applications' => (int)($row['total_applications'] ?? 0),
            'total_views' => (int)($row['total_views'] ?? 0),
        ];
    }

    public function createOffer(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO offre_emploi
                (titre, description, budget, duree, id_projet, id_recruteur, status, skills_needed, image_path, location, contact_email)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['titre'] ?? '',
            $data['description'] ?? '',
            $data['budget'] ?? 0,
            $data['duree'] ?? '',
            $data['id_projet'] ?? 0,
            $data['id_recruteur'] ?? 0,
            $data['status'] ?? 'draft',
            $data['skills_needed'] ?? null,
            $data['image_path'] ?? null,
            $data['location'] ?? null,
            $data['contact_email'] ?? null,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateOffer(int $offerId, int $recruiterId, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = $key . ' = ?';
            $values[] = $value;
        }

        $values[] = $offerId;
        $values[] = $recruiterId;
        $stmt = $this->pdo->prepare('UPDATE offre_emploi SET ' . implode(', ', $fields) . ' WHERE id_offer = ? AND id_recruteur = ?');
        return $stmt->execute($values);
    }

    public function deleteOffer(int $offerId, int $recruiterId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ?');
        return $stmt->execute([$offerId, $recruiterId]);
    }

    public function getApplicationsCountByOffer(int $offerId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM application_offre WHERE id_offre = ?');
        $stmt->execute([$offerId]);
        return (int)$stmt->fetchColumn();
    }

    public function getOfferStatusOptions(): array
    {
        return ['draft', 'published', 'paused', 'closed'];
    }
}
