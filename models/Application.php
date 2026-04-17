<?php
/**
 * Application.php
 * Application model for job applications
 * Handles user applications to job offers
 */

declare(strict_types=1);

class Application
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if user has applied to an offer
     */
    public function hasApplied(int $userId, int $offerId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as count FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             WHERE a.id_user = ? AND ao.id_offre = ?'
        );
        $stmt->execute([$userId, $offerId]);
        $result = $stmt->fetch();
        return $result && (int)$result['count'] > 0;
    }

    /**
     * Get applied offer IDs for a user
     */
    public function getAppliedOfferIds(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT DISTINCT ao.id_offre FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             WHERE a.id_user = ?'
        );
        $stmt->execute([$userId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'id_offre'));
    }

    /**
     * Get user's applications
     */
    public function getUserApplications(int $userId, int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, ao.id_offre, o.titre as offre_titre, o.budget, u.nom, u.prenom
             FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             JOIN user u ON u.id_user = o.id_recruteur
             WHERE a.id_user = ?
             ORDER BY a.id DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get applications for an offer (for recruiter)
     */
    public function getOfferApplications(int $offerId, int $recruiterId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.nom, u.prenom, u.email, u.phone
             FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             JOIN user u ON u.id_user = a.id_user
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             WHERE ao.id_offre = ? AND o.id_recruteur = ?
             ORDER BY a.id DESC'
        );
        $stmt->execute([$offerId, $recruiterId]);
        return $stmt->fetchAll();
    }

    /**
     * Create application
     */
    public function create(int $userId, int $offerId): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO application (id_user, date_application) VALUES (?, NOW())');
        $stmt->execute([$userId]);
        $applicationId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare('INSERT INTO application_offre (id_application, id_offre) VALUES (?, ?)');
        $stmt->execute([$applicationId, $offerId]);

        return $applicationId;
    }

    /**
     * Update application status
     */
    public function updateStatus(int $applicationId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $applicationId]);
    }

    /**
     * Delete application
     */
    public function delete(int $applicationId): bool
    {
        $this->pdo->prepare('DELETE FROM application_offre WHERE id_application = ?')->execute([$applicationId]);
        $stmt = $this->pdo->prepare('DELETE FROM application WHERE id = ?');
        return $stmt->execute([$applicationId]);
    }
}
