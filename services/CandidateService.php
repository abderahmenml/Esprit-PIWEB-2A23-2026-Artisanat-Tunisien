<?php
declare(strict_types=1);

final class CandidateService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getCandidateProfileByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cp.*, a.status AS application_status, a.cv, a.parsed_cv_data, a.cv_parsing_status,
                    o.id_offer, o.titre AS offer_title
             FROM candidate_profile cp
             LEFT JOIN application a ON a.id = cp.id_application
             LEFT JOIN application_offre ao ON ao.id_application = a.id
             LEFT JOIN offre_emploi o ON o.id_offer = ao.id_offre
             WHERE cp.id_user = ?
             ORDER BY cp.updated_at DESC
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function getProfileByApplication(int $applicationId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM candidate_profile WHERE id_application = ? LIMIT 1');
        $stmt->execute([$applicationId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function upsertFromApplication(int $applicationId, int $userId, array $structuredCv): int
    {
        $existing = $this->getProfileByApplication($applicationId);
        $skills = json_encode($structuredCv['skills'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $education = json_encode($structuredCv['education'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $workExperience = json_encode($structuredCv['work_experience'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $summary = (string)($structuredCv['professional_summary'] ?? '');
        $fullName = (string)($structuredCv['full_name'] ?? '');
        $email = (string)($structuredCv['email'] ?? '');
        $phone = (string)($structuredCv['phone'] ?? '');
        $location = (string)($structuredCv['location'] ?? '');
        $title = (string)($structuredCv['professional_title'] ?? '');
        $completeness = (int)($structuredCv['profile_completeness'] ?? 0);

        if ($existing) {
            $stmt = $this->pdo->prepare(
                'UPDATE candidate_profile
                 SET id_user = ?, full_name = ?, email = ?, phone = ?, location = ?, professional_title = ?,
                     professional_summary = ?, skills = ?, education = ?, work_experience = ?, profile_completeness = ?
                 WHERE id_application = ?'
            );
            $stmt->execute([$userId, $fullName, $email, $phone, $location, $title, $summary, $skills, $education, $workExperience, $completeness, $applicationId]);
            return (int)$existing['id'];
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO candidate_profile
                (id_application, id_user, full_name, email, phone, location, professional_title, professional_summary, skills, education, work_experience, profile_completeness)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$applicationId, $userId, $fullName, $email, $phone, $location, $title, $summary, $skills, $education, $workExperience, $completeness]);

        return (int)$this->pdo->lastInsertId();
    }

    public function saveSkills(int $candidateProfileId, array $skills): void
    {
        $this->pdo->prepare('DELETE FROM candidate_skills WHERE id_candidate_profile = ?')->execute([$candidateProfileId]);
        $stmt = $this->pdo->prepare('INSERT INTO candidate_skills (id_candidate_profile, skill_name, proficiency_level, years_of_experience) VALUES (?, ?, ?, ?)');

        foreach ($skills as $skill) {
            $name = trim((string)($skill['skill_name'] ?? $skill));
            if ($name === '') {
                continue;
            }
            $stmt->execute([
                $candidateProfileId,
                $name,
                (string)($skill['proficiency_level'] ?? 'intermediate'),
                $skill['years_of_experience'] ?? null,
            ]);
        }
    }

    public function candidateSummaryFromStructuredCv(array $structuredCv): string
    {
        $name = trim((string)($structuredCv['full_name'] ?? ''));
        $skills = $structuredCv['skills'] ?? [];
        $skillsText = is_array($skills) ? implode(', ', $skills) : (string)$skills;
        $experience = trim((string)($structuredCv['experience'] ?? ''));

        return trim(implode(' · ', array_filter([$name, $skillsText, $experience])));
    }
}
