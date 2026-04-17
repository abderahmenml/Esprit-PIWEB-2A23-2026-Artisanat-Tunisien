<?php
/**
 * Offer.php
 * Offer model for job/employment opportunities
 * Handles CRUD operations for job offers
 */

declare(strict_types=1);

class Offer
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get offer by ID
     */
    public function getById(int $offerId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre
             FROM offre_emploi o
             JOIN user u ON o.id_recruteur = u.id_user
             LEFT JOIN projet p ON p.id = o.id_projet
             WHERE o.id_offer = ? LIMIT 1'
        );
        $stmt->execute([$offerId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Get all offers with filters and pagination
     */
    public function getAll(array $filters = [], int $limit = 12, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.nom, u.prenom, p.titre AS projet_titre
                FROM offre_emploi o
                JOIN user u ON o.id_recruteur = u.id_user
                LEFT JOIN projet p ON p.id = o.id_projet
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['competence'])) {
            $sql .= ' AND o.skills_needed LIKE ?';
            $params[] = '%' . $filters['competence'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= ' AND o.status = ?';
            $params[] = $filters['status'];
        }

        $sql .= ' ORDER BY o.id_offer DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count total offers with filters
     */
    public function countAll(array $filters = []): int
    {
        $sql = "SELECT COUNT(DISTINCT o.id_offer) as total FROM offre_emploi o WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= ' AND (o.titre LIKE ? OR o.description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['competence'])) {
            $sql .= ' AND o.skills_needed LIKE ?';
            $params[] = '%' . $filters['competence'] . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Get recruiter's offers
     */
    public function getByRecruiter(int $recruiterId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT o.id_offer, o.titre, o.budget, o.duree, o.status, p.titre AS projet_titre
             FROM offre_emploi o
             LEFT JOIN projet p ON p.id = o.id_projet
             WHERE o.id_recruteur = ?
             ORDER BY o.id_offer DESC
             LIMIT ?'
        );
        $stmt->execute([$recruiterId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Create new offer
     */
    public function create(array $data): int
    {
        $columns = ['titre', 'description', 'budget', 'duree', 'id_projet', 'id_recruteur'];
        $placeholders = ['?', '?', '?', '?', '?', '?'];
        $values = [
            $data['titre'],
            $data['description'],
            $data['budget'],
            $data['duree'],
            $data['id_projet'],
            $data['id_recruteur'],
        ];

        if (isset($data['location'])) {
            $columns[] = 'location';
            $placeholders[] = '?';
            $values[] = $data['location'];
        }

        if (isset($data['contact_email'])) {
            $columns[] = 'contact_email';
            $placeholders[] = '?';
            $values[] = $data['contact_email'];
        }

        if (isset($data['image_path'])) {
            $columns[] = 'image_path';
            $placeholders[] = '?';
            $values[] = $data['image_path'];
        }

        if (isset($data['skills_needed'])) {
            $columns[] = 'skills_needed';
            $placeholders[] = '?';
            $values[] = $data['skills_needed'];
        }

        $sql = 'INSERT INTO offre_emploi (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update offer
     */
    public function update(int $offerId, int $recruiterId, array $data): bool
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }

        $values[] = $offerId;
        $values[] = $recruiterId;

        $sql = 'UPDATE offre_emploi SET ' . implode(', ', $fields) . ' WHERE id_offer = ? AND id_recruteur = ?';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Delete offer
     */
    public function delete(int $offerId, int $recruiterId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ?');
        return $stmt->execute([$offerId, $recruiterId]);
    }

    /**
     * Check if offer belongs to recruiter
     */
    public function belongsToRecruiter(int $offerId, int $recruiterId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) as count FROM offre_emploi WHERE id_offer = ? AND id_recruteur = ?');
        $stmt->execute([$offerId, $recruiterId]);
        $result = $stmt->fetch();
        return $result && (int)$result['count'] > 0;
    }
}
