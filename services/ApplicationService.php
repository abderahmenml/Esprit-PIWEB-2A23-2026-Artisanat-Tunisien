<?php
declare(strict_types=1);

final class ApplicationService
{
    public function __construct(private PDO $pdo)
    {
    }

    private function hasColumn(string $table, string $column): bool
    {
        $stmt = $this->pdo->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetch();
    }

    public function hasApplied(int $userId, int $offerId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             WHERE a.id_user = ? AND ao.id_offre = ?'
        );
        $stmt->execute([$userId, $offerId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getUserApplications(int $userId, int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $stmt = $this->pdo->prepare(
            'SELECT a.*, ao.id_offre, o.titre AS offre_titre, o.budget, o.duree, o.status AS offer_status,
                    p.titre AS projet_titre, u.nom, u.prenom
             FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             LEFT JOIN projet p ON p.id = o.id_projet
             JOIN `user` u ON u.id_user = o.id_recruteur
             WHERE a.id_user = ?
             ORDER BY a.date_creation DESC
             LIMIT ' . $limit . ' OFFSET ' . $offset
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getOfferApplications(int $offerId, int $recruiterId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, u.nom, u.prenom, u.email, u.phone, u.ville,
                    cp.full_name, cp.skills, cp.education, cp.work_experience, cp.professional_summary
             FROM application a
             JOIN application_offre ao ON ao.id_application = a.id
             JOIN `user` u ON u.id_user = a.id_user
             LEFT JOIN candidate_profile cp ON cp.id_application = a.id
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             WHERE ao.id_offre = ? AND o.id_recruteur = ?
             ORDER BY a.date_creation DESC'
        );
        $stmt->execute([$offerId, $recruiterId]);
        return $stmt->fetchAll();
    }

    public function getApplicationById(int $applicationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM application WHERE id = ? LIMIT 1');
        $stmt->execute([$applicationId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createApplication(int $userId, int $offerId, array $data): int
    {
        $this->pdo->beginTransaction();

        try {
            $columns = ['id_user', 'id_offer', 'lettre_de_motivation', 'cv', 'status', 'date_creation'];
            $placeholders = ['?', '?', '?', '?', '?', 'NOW()'];
            $values = [
                $userId,
                $offerId,
                $data['lettre_de_motivation'] ?? '',
                $data['cv'] ?? '',
                $data['status'] ?? 'pending',
            ];

            if ($this->hasColumn('application', 'parsed_cv_data')) {
                $columns[] = 'parsed_cv_data';
                $placeholders[] = '?';
                $values[] = isset($data['parsed_cv_data']) ? json_encode($data['parsed_cv_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            }
            if ($this->hasColumn('application', 'cv_parsing_status')) {
                $columns[] = 'cv_parsing_status';
                $placeholders[] = '?';
                $values[] = $data['cv_parsing_status'] ?? 'pending';
            }
            if ($this->hasColumn('application', 'cv_parsed_at')) {
                $columns[] = 'cv_parsed_at';
                $placeholders[] = 'NOW()';
            }
            foreach (['cv_file_name', 'cv_file_size', 'cv_file_type', 'cv_file_hash'] as $column) {
                if ($this->hasColumn('application', $column)) {
                    $columns[] = $column;
                    $placeholders[] = '?';
                    $values[] = $data[$column] ?? null;
                }
            }

            $stmt = $this->pdo->prepare('INSERT INTO application (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
            $stmt->execute($values);
            $applicationId = (int)$this->pdo->lastInsertId();

            $pivot = $this->pdo->prepare('INSERT INTO application_offre (id_application, id_offre) VALUES (?, ?)');
            $pivot->execute([$applicationId, $offerId]);

            $this->pdo->commit();
            return $applicationId;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus(int $applicationId, string $status): bool
    {
        $stmt = $this->pdo->prepare('UPDATE application SET status = ? WHERE id = ?');
        return $stmt->execute([$status, $applicationId]);
    }

    public function deleteApplication(int $applicationId): bool
    {
        $this->pdo->prepare('DELETE FROM application_offre WHERE id_application = ?')->execute([$applicationId]);
        $stmt = $this->pdo->prepare('DELETE FROM application WHERE id = ?');
        return $stmt->execute([$applicationId]);
    }

    public function countApplicationsForOffer(int $offerId, int $recruiterId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM application_offre ao
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             WHERE ao.id_offre = ? AND o.id_recruteur = ?'
        );
        $stmt->execute([$offerId, $recruiterId]);
        return (int)$stmt->fetchColumn();
    }

    public function countApplicationsByStatus(int $offerId, int $recruiterId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.status, COUNT(*) AS total
             FROM application_offre ao
             JOIN application a ON a.id = ao.id_application
             JOIN offre_emploi o ON o.id_offer = ao.id_offre
             WHERE ao.id_offre = ? AND o.id_recruteur = ?
             GROUP BY a.status'
        );
        $stmt->execute([$offerId, $recruiterId]);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[(string)$row['status']] = (int)$row['total'];
        }

        return $counts;
    }

    public function buildStructuredCv(array $payload): array
    {
        $skills = array_values(array_filter(array_map(
            'trim',
            preg_split('/[,;\n]+/', (string)($payload['skills'] ?? '')) ?: []
        )));

        return [
            'full_name' => trim((string)($payload['candidate_name'] ?? '')),
            'email' => trim((string)($payload['candidate_email'] ?? '')),
            'skills' => $skills,
            'experience' => trim((string)($payload['experience'] ?? '')),
            'education' => trim((string)($payload['education'] ?? '')),
            'source' => (string)($payload['cv_source'] ?? 'manual'),
        ];
    }

    public function extractCvTextHints(string $text): array
    {
        $result = [
            'candidate_name' => '',
            'candidate_email' => '',
            'skills' => '',
            'experience' => '',
            'education' => '',
        ];

        if (preg_match('/(?:name|nom)\s*[:\-]\s*(.+)/i', $text, $m)) {
            $result['candidate_name'] = trim($m[1]);
        }
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            $result['candidate_email'] = trim($m[0]);
        }
        if (preg_match('/(?:skills|comp[ée]tences)\s*[:\-]\s*(.+)/i', $text, $m)) {
            $result['skills'] = trim($m[1]);
        }
        if (preg_match('/(?:experience|exp[ée]rience)\s*[:\-]\s*(.+)/i', $text, $m)) {
            $result['experience'] = trim($m[1]);
        }
        if (preg_match('/(?:education|formation|[ée]tudes)\s*[:\-]\s*(.+)/i', $text, $m)) {
            $result['education'] = trim($m[1]);
        }

        return $result;
    }

    public function statusOptions(): array
    {
        return ['pending', 'reviewed', 'shortlisted', 'accepted', 'rejected'];
    }
}
