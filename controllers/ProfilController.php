<?php
// controllers/ProfilController.php

require_once 'config/config.php';
require_once 'models/ProfilModel.php';

class ProfilController
{
    private ProfilModel $model;

    public function __construct()
    {
        $this->model = new ProfilModel();
    }

    private function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
        exit;
    }

    private function requireAuth(): int
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }

        return (int)$_SESSION['user_id'];
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $message];
    }

    private function isAjaxRequest(): bool
    {
        $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        return $requestedWith === 'xmlhttprequest';
    }

    private function textResponse(bool $success, string $message, int $statusCode = 200, array $data = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }

    private function jsonResponse(bool $success, string $message, int $statusCode = 200, array $data = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'provider' => 'ollama'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? (int)mb_strlen($value) : strlen($value);
    }

    private function normalizeText(?string $value, int $maxLength = 255): string
    {
        $text = trim((string)$value);
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        if ($maxLength > 0 && $this->textLength($text) > $maxLength) {
            $text = function_exists('mb_substr') ? (string)mb_substr($text, 0, $maxLength) : substr($text, 0, $maxLength);
        }

        return $text;
    }

    private function isWithinLength(string $value, int $min, int $max): bool
    {
        $len = $this->textLength($value);
        return $len >= $min && $len <= $max;
    }

    private function hasControlChars(string $value): bool
    {
        return preg_match('/[\x00-\x1F\x7F]/', $value) === 1;
    }

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidName(string $value): bool
    {
        if (!$this->isWithinLength($value, 2, 60)) {
            return false;
        }

        return preg_match("/^[\\p{L}\\s\\-'’]+$/u", $value) === 1;
    }

    private function hasLetter(string $value): bool
    {
        return preg_match('/\\p{L}/u', $value) === 1;
    }

    private function isValidSkillName(string $value, int $minLength = 2, int $maxLength = 100): bool
    {
        if (!$this->isWithinLength($value, $minLength, $maxLength)) {
            return false;
        }

        if ($this->hasControlChars($value) || !$this->hasLetter($value)) {
            return false;
        }

        return preg_match("/^[\\p{L}\\p{N}\\s\\-'’().,+&\\/]+$/u", $value) === 1;
    }

    private function isValidSkillDescription(string $value, int $maxLength = 500): bool
    {
        if ($value === '') {
            return true;
        }

        if (!$this->isWithinLength($value, 2, $maxLength) || $this->hasControlChars($value)) {
            return false;
        }

        return preg_match("/^[\\p{L}\\p{N}\\s\\-'’().,!?+&\\/:-]+$/u", $value) === 1;
    }

    private function isValidPhone(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (preg_match('/^\+?[0-9][0-9\s().-]{7,19}$/', $value) !== 1) {
            return false;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        $count = strlen($digits);
        return $count >= 8 && $count <= 15;
    }

    private function isValidDate(string $value): bool
    {
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return false;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $value);
        return $dt instanceof DateTime && $dt->format('Y-m-d') === $value;
    }

    private function getPivotTableName(bool $createIfMissing = false): ?string
    {
        $pdo = getPDO();
        foreach (['profil_competence', 'profil_competences'] as $candidate) {
            $check = $pdo->query("SHOW TABLES LIKE '$candidate'");
            if ($check && $check->rowCount() > 0) {
                return $candidate;
            }
        }

        if (!$createIfMissing) {
            return null;
        }

        try {
            $pdo->exec("\n                CREATE TABLE IF NOT EXISTS profil_competence (\n                    id_profil BIGINT NOT NULL,\n                    id_competence BIGINT NOT NULL,\n                    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n                    PRIMARY KEY (id_profil, id_competence),\n                    KEY idx_pc_comp (id_competence)\n                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4\n            ");
            return 'profil_competence';
        } catch (Exception) {
            return null;
        }
    }

    private function getPivotCompetenceColumn(string $pivotTable): ?string
    {
        $pdo = getPDO();
        foreach (['id_competence', 'id_competences'] as $column) {
            $stmt = $pdo->prepare("\n                SELECT COUNT(*)\n                FROM INFORMATION_SCHEMA.COLUMNS\n                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?\n            ");
            $stmt->execute([$pivotTable, $column]);
            if ((int)$stmt->fetchColumn() > 0) {
                return $column;
            }
        }

        return null;
    }

    private function getOrCreateProfilId(int $userId): ?int
    {
        $pdo = getPDO();
        $check = $pdo->query("SHOW TABLES LIKE 'profil_professionnel'");
        if (!$check || $check->rowCount() === 0) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT id_profil FROM profil_professionnel WHERE id_user = ? ORDER BY id_profil ASC LIMIT 1");
        $stmt->execute([$userId]);
        $idProfil = $stmt->fetchColumn();
        if ($idProfil !== false) {
            return (int)$idProfil;
        }

        $insert = $pdo->prepare("INSERT INTO profil_professionnel (id_user, date_creation) VALUES (?, CURDATE())");
        $insert->execute([$userId]);

        $stmt->execute([$userId]);
        $idProfil = $stmt->fetchColumn();
        return $idProfil !== false ? (int)$idProfil : null;
    }

    private function getCompetenceCatalogRows(): array
    {
        try {
            $pdo = getPDO();
            $check = $pdo->query("SHOW TABLES LIKE 'competences'");
            if (!$check || $check->rowCount() === 0) {
                return [];
            }

            $stmt = $pdo->query("\n                SELECT id_competence, nom_competence, description\n                FROM competences\n                ORDER BY nom_competence ASC, id_competence ASC\n            ");
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Exception) {
            return [];
        }
    }

    private function getSelectedCompetenceIds(int $userId): array
    {
        $pivotTable = $this->getPivotTableName();
        $profilId = $this->getOrCreateProfilId($userId);
        if ($pivotTable === null || $profilId === null) {
            return [];
        }

        $pivotCompetenceColumn = $this->getPivotCompetenceColumn($pivotTable);
        if ($pivotCompetenceColumn === null) {
            return [];
        }

        $stmt = getPDO()->prepare("SELECT `$pivotCompetenceColumn` FROM `$pivotTable` WHERE id_profil = ?");
        $stmt->execute([$profilId]);

        $ids = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) ?: [] as $raw) {
            $id = (int)$raw;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function getUserCompetencesManyToMany(int $userId): array
    {
        $pivotTable = $this->getPivotTableName();
        $profilId = $this->getOrCreateProfilId($userId);
        if ($pivotTable === null || $profilId === null) {
            return $this->model->getCompetences($userId);
        }

        $pivotCompetenceColumn = $this->getPivotCompetenceColumn($pivotTable);
        if ($pivotCompetenceColumn === null) {
            return $this->model->getCompetences($userId);
        }

        $stmt = getPDO()->prepare("\n            SELECT c.id_competence, c.nom_competence, c.description, 100 AS niveau, 0 AS ordre\n            FROM `$pivotTable` pc\n            INNER JOIN competences c ON c.id_competence = pc.`$pivotCompetenceColumn`\n            WHERE pc.id_profil = ?\n            ORDER BY c.nom_competence ASC, c.id_competence ASC\n        ");
        $stmt->execute([$profilId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function saveSelectedCompetencesForUser(int $userId, array $competenceIds): bool
    {
        $pivotTable = $this->getPivotTableName(true);
        $profilId = $this->getOrCreateProfilId($userId);
        if ($pivotTable === null || $profilId === null) {
            return false;
        }

        $pivotCompetenceColumn = $this->getPivotCompetenceColumn($pivotTable);
        if ($pivotCompetenceColumn === null) {
            return false;
        }

        $cleanIds = [];
        foreach ($competenceIds as $rawId) {
            $id = (int)$rawId;
            if ($id > 0) {
                $cleanIds[] = $id;
            }
        }
        $cleanIds = array_values(array_unique($cleanIds));

        $pdo = getPDO();
        $existingIds = [];
        if (!empty($cleanIds)) {
            $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $stmtValid = $pdo->prepare("SELECT id_competence FROM competences WHERE id_competence IN ($placeholders)");
            $stmtValid->execute($cleanIds);
            foreach ($stmtValid->fetchAll(PDO::FETCH_COLUMN) ?: [] as $rawValid) {
                $existingIds[] = (int)$rawValid;
            }
        }

        $pdo->beginTransaction();
        try {
            $stmtDelete = $pdo->prepare("DELETE FROM `$pivotTable` WHERE id_profil = ?");
            $stmtDelete->execute([$profilId]);

            if (!empty($existingIds)) {
                $stmtInsert = $pdo->prepare("INSERT INTO `$pivotTable` (id_profil, `$pivotCompetenceColumn`) VALUES (?, ?)");
                foreach ($existingIds as $competenceId) {
                    $stmtInsert->execute([$profilId, $competenceId]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    private function resolveCompetenceIdFromRequest(string $catalogChoice, string $nom): ?int
    {
        $pdo = getPDO();

        $catalogChoice = trim($catalogChoice);
        if ($catalogChoice !== '') {
            if (strncmp($catalogChoice, 'id:', 3) === 0) {
                $id = (int)substr($catalogChoice, 3);
                if ($id > 0) {
                    $stmt = $pdo->prepare('SELECT id_competence FROM competences WHERE id_competence = ? LIMIT 1');
                    $stmt->execute([$id]);
                    $found = $stmt->fetchColumn();
                    return $found !== false ? (int)$found : null;
                }
            }

            if (strncmp($catalogChoice, 'name:', 5) === 0) {
                $nom = trim(substr($catalogChoice, 5));
            }
        }

        $nom = $this->normalizeText($nom, 100);
        if ($nom === '') {
            return null;
        }

        $stmt = $pdo->prepare('SELECT id_competence FROM competences WHERE LOWER(nom_competence) = LOWER(?) LIMIT 1');
        $stmt->execute([$nom]);
        $found = $stmt->fetchColumn();

        return $found !== false ? (int)$found : null;
    }

    private function addUserCompetenceLink(int $userId, int $competenceId): bool
    {
        $pivotTable = $this->getPivotTableName(true);
        $profilId = $this->getOrCreateProfilId($userId);
        if ($pivotTable === null || $profilId === null || $competenceId <= 0) {
            return false;
        }

        $pivotCompetenceColumn = $this->getPivotCompetenceColumn($pivotTable);
        if ($pivotCompetenceColumn === null) {
            return false;
        }

        $stmt = getPDO()->prepare("INSERT IGNORE INTO `$pivotTable` (id_profil, `$pivotCompetenceColumn`) VALUES (?, ?)");
        $stmt->execute([$profilId, $competenceId]);

        return true;
    }

    private function removeUserCompetenceLink(int $userId, int $competenceId): bool
    {
        $pivotTable = $this->getPivotTableName();
        $profilId = $this->getOrCreateProfilId($userId);
        if ($pivotTable === null || $profilId === null || $competenceId <= 0) {
            return false;
        }

        $pivotCompetenceColumn = $this->getPivotCompetenceColumn($pivotTable);
        if ($pivotCompetenceColumn === null) {
            return false;
        }

        $stmt = getPDO()->prepare("DELETE FROM `$pivotTable` WHERE id_profil = ? AND `$pivotCompetenceColumn` = ?");
        $stmt->execute([$profilId, $competenceId]);

        return $stmt->rowCount() > 0;
    }

    private function getDefaultPortfolioRealisations(): array
    {
        return [
            'Vase Amazigh',
            'Service a Tajine',
            'Carreaux Zellige',
            'Fontaine en ceramique',
            'Collection Printemps',
            'Motifs Islamiques'
        ];
    }

    private function lowerText(string $value): string
    {
        return function_exists('mb_strtolower') ? (string)mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function extractTopNames(array $items, string $nameKey, string $levelKey, int $limit): array
    {
        usort($items, static function (array $left, array $right) use ($levelKey): int {
            return (int)($right[$levelKey] ?? 0) <=> (int)($left[$levelKey] ?? 0);
        });

        $names = [];
        foreach ($items as $item) {
            $name = trim((string)($item[$nameKey] ?? ''));
            if ($name === '') {
                continue;
            }
            $names[] = $name;
            if (count($names) >= $limit) {
                break;
            }
        }

        return $names;
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function buildSuggestedJobs(string $specialite, array $competences, array $certifications): array
    {
        $haystackParts = [$specialite];
        foreach ($competences as $competence) {
            $haystackParts[] = (string)($competence['nom_competence'] ?? '');
        }
        foreach ($certifications as $certification) {
            $haystackParts[] = (string)($certification['nom_certification'] ?? '');
        }

        $haystack = $this->lowerText(implode(' ', $haystackParts));
        $catalog = [
            'php' => ['Développeur PHP', 'Développeur Laravel', 'Développeur Back-End'],
            'laravel' => ['Développeur Laravel', 'Architecte Back-End', 'Développeur PHP'],
            'symfony' => ['Développeur Symfony', 'Développeur PHP'],
            'javascript' => ['Développeur Front-End', 'Développeur Full Stack', 'Intégrateur Web'],
            'react' => ['Développeur Front-End React', 'Développeur Full Stack'],
            'vue' => ['Développeur Front-End Vue.js', 'Développeur Full Stack'],
            'node' => ['Développeur Node.js', 'Développeur Full Stack'],
            'html' => ['Intégrateur Web', 'Développeur Front-End'],
            'css' => ['Intégrateur Web', 'Développeur Front-End'],
            'python' => ['Développeur Python', 'Data Analyst', 'Automatisation'],
            'data' => ['Data Analyst', 'Consultant Data'],
            'sql' => ['Analyste Données', 'Développeur Back-End'],
            'design' => ['UI/UX Designer', 'Directeur Artistique'],
            'ux' => ['UI/UX Designer'],
            'ui' => ['UI/UX Designer'],
            'marketing' => ['Consultant Marketing Digital', 'Growth Marketer'],
            'seo' => ['Spécialiste SEO', 'Consultant Marketing Digital'],
            'ecommerce' => ['Consultant E-commerce', 'Growth Marketer'],
            'wordpress' => ['Développeur WordPress', 'Intégrateur Web'],
            'mobile' => ['Développeur Mobile'],
            'java' => ['Développeur Java', 'Ingénieur Logiciel'],
            'management' => ['Chef de Projet Digital', 'Product Owner'],
            'project' => ['Chef de Projet Digital'],
            'artisan' => ['Artisan d\'art', 'Créateur de marque artisanale'],
            'ceramique' => ['Artisan céramiste', 'Créateur de collection artisanale'],
            'céramique' => ['Artisan céramiste', 'Créateur de collection artisanale']
        ];

        $jobs = [];
        foreach ($catalog as $needle => $suggestions) {
            if (!$this->containsAny($haystack, [$needle])) {
                continue;
            }

            foreach ($suggestions as $job) {
                if (!in_array($job, $jobs, true)) {
                    $jobs[] = $job;
                }
                if (count($jobs) >= 5) {
                    break 2;
                }
            }
        }

        if (empty($jobs)) {
            $cleanSpecialite = trim($specialite);
            if ($cleanSpecialite !== '' && $this->lowerText($cleanSpecialite) !== 'specialite non renseignee') {
                $jobs[] = $cleanSpecialite;
                $jobs[] = $cleanSpecialite . ' freelance';
            } else {
                $jobs[] = 'Profil freelance polyvalent';
                $jobs[] = 'Consultant métier';
            }
        }

        return array_slice(array_values(array_unique($jobs)), 0, 5);
    }

    private function buildSkillGaps(array $suggestedJobs, array $competences): array
    {
        $skillPool = [];
        foreach ($competences as $competence) {
            $skill = $this->lowerText(trim((string)($competence['nom_competence'] ?? '')));
            if ($skill !== '') {
                $skillPool[] = $skill;
            }
        }

        $gapMap = [
            'Développeur PHP' => ['laravel', 'architecture mvc', 'tests unitaires'],
            'Développeur Laravel' => ['api rest', 'tests', 'eloquent'],
            'Architecte Back-End' => ['architecture logicielle', 'sécurité', 'tests'],
            'Développeur Front-End' => ['react', 'vue.js', 'accessibilité'],
            'Développeur Front-End React' => ['react', 'typescript', 'tests'],
            'Développeur Full Stack' => ['react', 'api rest', 'node.js'],
            'Intégrateur Web' => ['javascript', 'responsive design', 'accessibilité'],
            'Développeur Python' => ['django', 'api rest', 'automatisation'],
            'Data Analyst' => ['sql avancé', 'visualisation de données', 'power bi'],
            'Analyste Données' => ['python', 'sql avancé', 'dashboarding'],
            'UI/UX Designer' => ['figma', 'prototypage', 'design system'],
            'Consultant Marketing Digital' => ['seo', 'analytics', 'cro'],
            'Consultant E-commerce' => ['conversion', 'catalogue produit', 'tracking'],
            'Développeur WordPress' => ['php', 'seo', 'gestion de thèmes'],
            'Développeur Mobile' => ['flutter', 'android', 'ios'],
            'Chef de Projet Digital' => ['gestion de sprint', 'roadmap', 'communication client']
        ];

        $gaps = [];
        foreach ($suggestedJobs as $job) {
            foreach ($gapMap[$job] ?? [] as $gap) {
                $normalizedGap = $this->lowerText($gap);
                $alreadyOwned = false;
                foreach ($skillPool as $skill) {
                    if ($skill !== '' && (strpos($skill, $normalizedGap) !== false || strpos($normalizedGap, $skill) !== false)) {
                        $alreadyOwned = true;
                        break;
                    }
                }

                if (!$alreadyOwned && !in_array($gap, $gaps, true)) {
                    $gaps[] = $gap;
                }
                if (count($gaps) >= 6) {
                    break 2;
                }
            }
        }

        return $gaps;
    }

    private function buildCareerPaths(string $specialite, array $competences): array
    {
        $skillText = $this->lowerText($specialite . ' ' . implode(' ', array_map(static function (array $competence): string {
            return (string)($competence['nom_competence'] ?? '');
        }, $competences)));

        $paths = [];
        if ($this->containsAny($skillText, ['php', 'laravel', 'symfony', 'javascript', 'react', 'vue', 'node'])) {
            $paths = ['Développement Full Stack', 'Développement Back-End', 'Freelance Web'];
        } elseif ($this->containsAny($skillText, ['python', 'data', 'sql'])) {
            $paths = ['Analyse de données', 'Automatisation', 'Conseil Data'];
        } elseif ($this->containsAny($skillText, ['design', 'ui', 'ux'])) {
            $paths = ['UI/UX', 'Direction artistique', 'Branding'];
        } elseif ($this->containsAny($skillText, ['marketing', 'seo', 'ecommerce'])) {
            $paths = ['Marketing digital', 'Growth', 'E-commerce'];
        } elseif ($this->containsAny($skillText, ['artisan', 'ceramique', 'céramique'])) {
            $paths = ['Création artisanale premium', 'Direction de collection', 'Marque personnelle'];
        }

        if (empty($paths)) {
            $paths = ['Spécialisation métier', 'Freelance', 'Conseil'];
        }

        return array_values(array_unique($paths));
    }

    private function buildNarrativeAssets(
        string $specialite,
        array $competences,
        array $certifications,
        array $portfolioFiles,
        array $suggestedJobs,
        array $careerPaths
    ): array {
        $topSkills = $this->extractTopNames($competences, 'nom_competence', 'niveau', 3);
        $topCerts = $this->extractTopNames($certifications, 'nom_certification', 'niveau', 2);
        $skillSentence = empty($topSkills) ? 'des compétences polyvalentes' : implode(', ', $topSkills);
        $portfolioCount = count($portfolioFiles);
        $specialiteLabel = trim($specialite) !== '' ? $specialite : 'professionnel polyvalent';
        $jobLabel = !empty($suggestedJobs) ? $suggestedJobs[0] : 'opportunités adaptées';
        $careerLabel = !empty($careerPaths) ? $careerPaths[0] : 'évolution de carrière structurée';

        $summary = 'Profil de ' . $specialiteLabel . ' avec ' . $skillSentence . '. ';
        $summary .= 'Le parcours montre ' . count($certifications) . ' certification' . (count($certifications) > 1 ? 's' : '') . ' et ' . $portfolioCount . ' réalisation' . ($portfolioCount > 1 ? 's' : '') . '.';

        $headlineParts = array_filter([
            $specialiteLabel,
            $skillSentence,
            !empty($topCerts) ? implode(' · ', $topCerts) : null
        ]);

        $headline = implode(' | ', $headlineParts);
        $cvSummary = 'Professionnel ' . $specialiteLabel . ' orienté résultats, avec une base solide en ' . $skillSentence . '. ';
        $cvSummary .= 'Capable de contribuer à ' . $jobLabel . ' tout en accompagnant une évolution vers ' . $careerLabel . '.';

        $coverLetter = "Madame, Monsieur,\n\nJe vous propose ma candidature pour un poste en lien avec " . $specialiteLabel . '. ';
        $coverLetter .= 'Mon expérience en ' . $skillSentence . ' et mes ' . count($certifications) . " certifications me permettent d'apporter une valeur concrète à votre équipe. ";
        $coverLetter .= 'Je serais ravi de mettre mes compétences au service de vos objectifs.';

        return [
            'summary' => $summary,
            'headline' => $headline,
            'cvSummary' => $cvSummary,
            'coverLetter' => $coverLetter
        ];
    }

    private function buildCompletionData(string $bio, array $competences, array $portfolioFiles): array
    {
        $descriptionCompleted = trim($bio) !== '';
        $competencesCompleted = count($competences) > 0;
        $portfolioCompleted = count($portfolioFiles) > 0;

        $score = 0;
        if ($descriptionCompleted) {
            $score += 20;
        }
        if ($competencesCompleted) {
            $score += 30;
        }
        if ($portfolioCompleted) {
            $score += 50;
        }

        return [
            'score' => $score,
            'description' => [
                'label' => 'Description',
                'weight' => 20,
                'completed' => $descriptionCompleted,
            ],
            'competences' => [
                'label' => 'Competences',
                'weight' => 30,
                'completed' => $competencesCompleted,
            ],
            'portfolio' => [
                'label' => 'Portfolio',
                'weight' => 50,
                'completed' => $portfolioCompleted,
            ],
        ];
    }

    private function buildInsightData(
        string $specialite,
        string $bio,
        string $ville,
        string $email,
        string $telephone,
        array $competences,
        array $certifications,
        array $experiences,
        array $portfolioFiles,
        array $stats
    ): array {
        $competenceScores = array_map(static fn(array $item): int => (int)($item['niveau'] ?? 0), $competences);
        $certificationScores = array_map(static fn(array $item): int => (int)($item['niveau'] ?? 0), $certifications);

        $skillCount = count($competences);
        $certCount = count($certifications);
        $experienceCount = count($experiences);
        $portfolioCount = count($portfolioFiles);

        $completionData = $this->buildCompletionData($bio, $competences, $portfolioFiles);
        $completionScore = (int)($completionData['score'] ?? 0);

        $skillAverage = $skillCount > 0 ? array_sum($competenceScores) / $skillCount : 0;
        $certAverage = $certCount > 0 ? array_sum($certificationScores) / $certCount : 0;

        $skillDepthScore = min(100, (int)round(($skillAverage * 0.6) + min(25, $skillCount * 4)));
        $certQualityScore = min(100, (int)round(($certAverage * 0.7) + min(20, $certCount * 6)));
        $portfolioQualityScore = min(100, (int)round(min(55, $portfolioCount * 18) + min(25, $experienceCount * 6) + min(20, $portfolioCount > 0 ? 10 : 0)));

        $profileScore = (int)round(
            (0.38 * $skillDepthScore) +
            (0.24 * $certQualityScore) +
            (0.18 * $portfolioQualityScore) +
            (0.20 * $completionScore)
        );
        $profileScore = max(0, min(100, $profileScore));

        $freshnessScore = $portfolioCount > 0 ? min(100, 40 + $portfolioCount * 12) : 20;
        $activityScore = min(100, ($skillCount * 8) + ($certCount * 10) + ($experienceCount * 12) + ($portfolioCount * 14));
        $projetCount = (int)($stats['projets'] ?? 0);
        $mentorCount = (int)($stats['mentores'] ?? 0);
        $noteNumeric = (float)($stats['note'] ?? 0);
        $engagementScore = min(100, (int)round(($projetCount * 8) + ($mentorCount * 12) + ($noteNumeric / 5 * 30)));

        $popularityScore = (int)round(
            (0.35 * $activityScore) +
            (0.25 * $engagementScore) +
            (0.20 * $freshnessScore) +
            (0.20 * $completionScore)
        );
        $popularityScore = max(0, min(100, $popularityScore));

        $suggestedJobs = $this->buildSuggestedJobs($specialite, $competences, $certifications);
        $skillGaps = $this->buildSkillGaps($suggestedJobs, $competences);
        $careerPaths = $this->buildCareerPaths($specialite, $competences);
        $narrative = $this->buildNarrativeAssets($specialite, $competences, $certifications, $portfolioFiles, $suggestedJobs, $careerPaths);

        $isTrending = $profileScore >= 70 && $popularityScore >= 55 && (!empty($portfolioFiles) || !empty($suggestedJobs));

        $suggestedOpportunities = max(2, min(6, count($suggestedJobs) + (int)($profileScore >= 75) + (int)($popularityScore >= 65)));

        $activityMetrics = [
            ['label' => 'Portfolio', 'value' => $portfolioCount],
            ['label' => 'Experiences', 'value' => $experienceCount],
            ['label' => 'Competences', 'value' => $skillCount],
            ['label' => 'Certifs', 'value' => $certCount],
            ['label' => 'Projets', 'value' => $projetCount]
        ];

        $maxActivityValue = 1;
        foreach ($activityMetrics as $metric) {
            $maxActivityValue = max($maxActivityValue, (int)$metric['value']);
        }

        $activityBars = [];
        foreach ($activityMetrics as $metric) {
            $value = (int)$metric['value'];
            $height = $value > 0 ? max(18, (int)round(($value / $maxActivityValue) * 100)) : 8;
            $activityBars[] = [
                'label' => $metric['label'],
                'value' => $value,
                'height' => $height
            ];
        }

        return [
            'completionScore' => $completionScore,
            'completionBreakdown' => $completionData,
            'profileScore' => $profileScore,
            'popularityScore' => $popularityScore,
            'isTrending' => $isTrending,
            'suggestedOpportunities' => $suggestedOpportunities,
            'suggestedJobs' => $suggestedJobs,
            'skillGaps' => $skillGaps,
            'careerPaths' => $careerPaths,
            'professionalSummary' => $narrative['summary'],
            'linkedinHeadline' => $narrative['headline'],
            'cvSummary' => $narrative['cvSummary'],
            'coverLetterTemplate' => $narrative['coverLetter'],
            'activityBars' => $activityBars
        ];
    }

    private function persistInsightCache(int $userId, array $insightData): void
    {
        $this->model->saveInsightMetrics($userId, [
            'profile_score' => (int)($insightData['profileScore'] ?? 0),
            'popularity_score' => (int)($insightData['popularityScore'] ?? 0),
            'suggested_jobs' => (int)($insightData['suggestedOpportunities'] ?? 0),
            'is_trending' => !empty($insightData['isTrending'])
        ]);
    }

    private function refreshInsightCache(int $userId): void
    {
        try {
            $user = $this->model->getUserById($userId);
            if (!$user) {
                return;
            }

            $specialite = $user['specialite'] ?? 'Specialite non renseignee';
            $bio = $this->model->getBioByUserId($userId);
            $ville = $user['ville'] ?? 'Tunisie';
            $email = $user['email'] ?? 'Non renseigne';
            $telephone = trim((string)($user['telephone'] ?? ''));
            if ($telephone === '') {
                $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
            }
            if ($telephone === '') {
                $telephone = 'Non renseigne';
            }

            $stats = $this->model->getStats($userId);
            $competences = $this->getUserCompetencesManyToMany($userId);
            $certifications = $this->model->getCertifications($userId);
            $experiences = $this->model->getExperiences($userId);
            $portfolioFiles = $this->model->getPortfolioFiles($userId);

            $insightData = $this->buildInsightData(
                (string)$specialite,
                (string)$bio,
                (string)$ville,
                (string)$email,
                (string)$telephone,
                $competences,
                $certifications,
                $experiences,
                $portfolioFiles,
                $stats
            );

            $this->persistInsightCache($userId, $insightData);
        } catch (Throwable $e) {
            return;
        }
    }

    public function index()
    {
        $user_id = $this->requireAuth();

        $this->refreshInsightCache($user_id);

        $user = $this->model->getUserById($user_id);
        if (!$user) {
            $this->flash('error', 'Profil introuvable.');
            $this->redirect('/dashboard');
        }

        $stats = $this->model->getStats($user_id);
        $competenceCatalog = $this->getCompetenceCatalogRows();
        $competences = $this->getUserCompetencesManyToMany($user_id);
        $certifications = $this->model->getCertifications($user_id);
        $experiences = $this->model->getExperiences($user_id);
        $portfolioFiles = $this->model->getPortfolioFiles($user_id);
        $avis = $this->model->getAvis($user_id);

        // Métiers avancés (max 2 par utilisateur)
        $metiersAvances = $this->model->getMetiersAvances($user_id);

        $initials = strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? 'U', 0, 1));

        $specialite = $user['specialite'] ?? 'Specialite non renseignee';
        $bio = $this->model->getBioByUserId($user_id);
        $disponibiliteRaw = strtolower(trim((string)($user['disponibilite'] ?? 'disponible')));
        if ($disponibiliteRaw === 'absent momentanement' || $disponibiliteRaw === 'absent_momentanement') {
            $disponibilite = 'occupe';
        } elseif ($disponibiliteRaw === 'indisponible') {
            $disponibilite = 'indisponible';
        } elseif ($disponibiliteRaw === 'occupe') {
            $disponibilite = 'occupe';
        } else {
            $disponibilite = 'disponible';
        }
        $disponibiliteLabels = [
            'disponible' => 'Disponible',
            'occupe' => 'Absent momentanement',
            'indisponible' => 'Indisponible'
        ];
        $disponibiliteLabel = $disponibiliteLabels[$disponibilite];
        $disponibilite_horaire = trim((string)($user['disponibilite_horaire'] ?? ''));
        $disponibilite_message = trim((string)($user['disponibilite_message'] ?? ''));
        $disponibilite_slots = trim((string)($user['disponibilite_slots'] ?? ''));
        $disponibilite_exceptions = trim((string)($user['disponibilite_exceptions'] ?? ''));
        $disponibilite_conges = trim((string)($user['disponibilite_conges'] ?? ''));
        if ($disponibilite_horaire === '') {
            $defaultHoraires = [
                'disponible' => 'Lun - Sam · 8h-17h',
                'occupe' => 'Disponible plus tard dans la journee',
                'indisponible' => 'Temporairement indisponible'
            ];
            $disponibilite_horaire = $defaultHoraires[$disponibilite] ?? 'Lun - Sam · 8h-17h';
        }
        $ville = $user['ville'] ?? 'Tunisie';
        $email = $user['email'] ?? 'Non renseigne';
        $telephone = trim((string)($user['telephone'] ?? ''));
        if ($telephone === '') {
            $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
        }
        if ($telephone === '') {
            $telephone = 'Non renseigne';
        }
        $portfolio_url = $user['portfolio'] ?? '';
        $total_projets = $stats['projets'] ?? 0;
        $note_moyenne = $stats['note'] ?? '0.0';
        $total_mentores = $stats['mentores'] ?? 0;

        $insightData = $this->buildInsightData(
            (string)$specialite,
            (string)$bio,
            (string)$ville,
            (string)$email,
            (string)$telephone,
            $competences,
            $certifications,
            $experiences,
            $portfolioFiles,
            $stats
        );

        $completionScore = (int)$insightData['completionScore'];
        $completionBreakdown = $insightData['completionBreakdown'];
        $profileScore = (int)$insightData['profileScore'];
        $popularityScore = (int)$insightData['popularityScore'];
        $isTrending = (bool)$insightData['isTrending'];
        $suggestedOpportunities = (int)$insightData['suggestedOpportunities'];
        $suggestedJobs = $insightData['suggestedJobs'];
        $skillGaps = $insightData['skillGaps'];
        $careerPaths = $insightData['careerPaths'];
        $professionalSummary = $insightData['professionalSummary'];
        $linkedinHeadline = $insightData['linkedinHeadline'];
        $cvSummary = $insightData['cvSummary'];
        $coverLetterTemplate = $insightData['coverLetterTemplate'];
        $activityBars = $insightData['activityBars'];
        $insightLastCalcAt = $user['insight_last_calc_at'] ?? null;

        $flash = null;
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        $portfolio_items = [
            ['titre' => 'Vase Amazigh', 'description' => 'Decoration', 'icon' => 'V'],
            ['titre' => 'Service a Tajine', 'description' => 'Arts de la table', 'icon' => 'S'],
            ['titre' => 'Carreaux Zellige', 'description' => 'Architecture', 'icon' => 'C'],
            ['titre' => 'Fontaine en ceramique', 'description' => 'Jardin & Ext.', 'icon' => 'F'],
            ['titre' => 'Collection Printemps', 'description' => 'Decoration', 'icon' => 'P'],
            ['titre' => 'Motifs Islamiques', 'description' => 'Art sacre', 'icon' => 'M']
        ];
        $defaultRealisations = $this->getDefaultPortfolioRealisations();

        require_once 'views/profil/index.php';
    }

    public function recalculateCompletion()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $user = $this->model->getUserById($user_id);
        if (!$user) {
            if ($isAjax) {
                $this->textResponse(false, 'Profil introuvable.', 404);
            }
            $this->flash('error', 'Profil introuvable.');
            $this->redirect('/profil');
        }

        $specialite = (string)($user['specialite'] ?? 'Specialite non renseignee');
        $bio = (string)$this->model->getBioByUserId($user_id);
        $ville = (string)($user['ville'] ?? 'Tunisie');
        $email = (string)($user['email'] ?? 'Non renseigne');
        $telephone = trim((string)($user['telephone'] ?? ''));
        if ($telephone === '') {
            $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
        }
        if ($telephone === '') {
            $telephone = 'Non renseigne';
        }

        $stats = $this->model->getStats($user_id);
        $competences = $this->getUserCompetencesManyToMany($user_id);
        $certifications = $this->model->getCertifications($user_id);
        $experiences = $this->model->getExperiences($user_id);
        $portfolioFiles = $this->model->getPortfolioFiles($user_id);

        $insightData = $this->buildInsightData(
            $specialite,
            $bio,
            $ville,
            $email,
            $telephone,
            $competences,
            $certifications,
            $experiences,
            $portfolioFiles,
            $stats
        );

        $this->persistInsightCache($user_id, $insightData);

        $completionScore = (int)($insightData['completionScore'] ?? 0);
        $completionBreakdown = (array)($insightData['completionBreakdown'] ?? []);

        if ($isAjax) {
            $this->textResponse(true, 'Progression du profil recalculee.', 200, [
                'completionScore' => $completionScore,
                'completionBreakdown' => $completionBreakdown,
                'profileScore' => (int)($insightData['profileScore'] ?? 0),
                'popularityScore' => (int)($insightData['popularityScore'] ?? 0)
            ]);
        }

        $this->flash('success', 'Progression du profil recalculee : ' . $completionScore . '%.');
        $this->redirect('/profil');
    }

    public function gestion_competences()
    {
        $user_id = $this->requireAuth();
        $competenceCatalog = $this->getCompetenceCatalogRows();
        $competences = $this->getUserCompetencesManyToMany($user_id);
        $selectedCompetenceIds = $this->getSelectedCompetenceIds($user_id);

        require_once 'views/profil/gestion_competences.php';
    }

    public function saveCompetences()
    {
        $user_id = $this->requireAuth();

        $competenceIds = $_POST['competence_ids'] ?? [];
        if (!is_array($competenceIds)) {
            $competenceIds = [];
        }

        if ($this->saveSelectedCompetencesForUser($user_id, $competenceIds)) {
            $this->refreshInsightCache($user_id);
            $this->flash('success', 'Competences du profil mises a jour.');
        } else {
            $this->flash('error', 'Mise a jour des competences impossible.');
        }

        $this->redirect('/profil/gestion_competences');
    }

    public function gestion_certifications()
    {
        $user_id = $this->requireAuth();
        $certifications = $this->model->getCertifications($user_id);

        require_once 'views/profil/gestion_certifications.php';
    }

    public function addCompetence()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $catalogChoice = trim((string)($_POST['competence_catalog_choice'] ?? ''));
        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);

        $competenceId = $this->resolveCompetenceIdFromRequest($catalogChoice, $nom);
        if ($competenceId === null) {
            if ($isAjax) {
                $this->textResponse(false, 'Veuillez selectionner une competence existante.', 422);
            }
            $this->flash('error', 'Veuillez selectionner une competence existante.');
            $this->redirect('/profil/gestion_competences');
        }

        if ($this->addUserCompetenceLink($user_id, $competenceId)) {
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->textResponse(true, 'Competence associee au profil.');
            }
            $this->flash('success', 'Competence associee au profil.');
        } else {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de l\'ajout', 500);
            }
            $this->flash('error', 'Erreur lors de l\'ajout');
        }

        $this->redirect('/profil/gestion_competences');
    }

    public function deleteCompetence()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            if ($isAjax) {
                $this->textResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil/gestion_competences');
        }

        if ($this->removeUserCompetenceLink($user_id, (int)$id)) {
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->textResponse(true, 'Competence retiree du profil.');
            }
            $this->flash('success', 'Competence retiree du profil.');
        } else {
            if ($isAjax) {
                $this->textResponse(false, 'Suppression impossible', 409);
            }
            $this->flash('error', 'Suppression impossible');
        }

        $this->redirect('/profil/gestion_competences');
    }

    public function updateCompetence()
    {
        $this->requireAuth();
        if ($this->isAjaxRequest()) {
            $this->textResponse(false, 'Modification refusee: le catalogue est gere par admin.', 403);
        }
        $this->flash('error', 'Modification refusee: le catalogue est gere par admin.');
        $this->redirect('/profil/gestion_competences');
    }

    public function addExperience()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $poste = $this->normalizeText($_POST['poste'] ?? '', 100);
        $entreprise = $this->normalizeText($_POST['entreprise'] ?? '', 120);
        $date_debut = trim((string)($_POST['date_debut'] ?? ''));
        $date_fin = trim((string)($_POST['date_fin'] ?? ''));
        $description = $this->normalizeText($_POST['description'] ?? '', 1000);

        if (!$this->isWithinLength($poste, 2, 100) || $this->hasControlChars($poste)) {
            if ($isAjax) {
                $this->textResponse(false, 'Le poste est obligatoire', 422);
            }
            $this->flash('error', 'Le poste est obligatoire');
            $this->redirect('/profil');
        }

        if ($entreprise !== '' && ($this->textLength($entreprise) > 120 || $this->hasControlChars($entreprise))) {
            if ($isAjax) {
                $this->textResponse(false, 'Entreprise invalide', 422);
            }
            $this->flash('error', 'Entreprise invalide');
            $this->redirect('/profil');
        }

        if ($description !== '' && ($this->textLength($description) > 1000 || $this->hasControlChars($description))) {
            if ($isAjax) {
                $this->textResponse(false, 'Description invalide', 422);
            }
            $this->flash('error', 'Description invalide');
            $this->redirect('/profil');
        }

        if (!$this->isValidDate($date_debut)) {
            if ($isAjax) {
                $this->textResponse(false, 'La date de debut est obligatoire', 422);
            }
            $this->flash('error', 'Date de debut invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && !$this->isValidDate($date_fin)) {
            if ($isAjax) {
                $this->textResponse(false, 'Date de fin invalide', 422);
            }
            $this->flash('error', 'Date de fin invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && strtotime($date_fin) < strtotime($date_debut)) {
            if ($isAjax) {
                $this->textResponse(false, 'La date de fin doit etre apres la date de debut', 422);
            }
            $this->flash('error', 'La date de fin doit etre apres la date de debut');
            $this->redirect('/profil');
        }

        try {
            $newId = $this->model->addExperience($user_id, $poste, $entreprise, $date_debut, $date_fin, $description);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->textResponse(true, 'Experience ajoutee', 200, [
                    'experience' => [
                        'id_experience' => $newId,
                        'poste' => $poste,
                        'entreprise' => $entreprise,
                        'date_debut' => $date_debut,
                        'date_fin' => $date_fin,
                        'description' => $description
                    ]
                ]);
            }
            $this->flash('success', 'Experience ajoutee');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de l\'ajout', 500);
            }
            $this->flash('error', 'Erreur lors de l\'ajout');
        }

        $this->redirect('/profil');
    }

    public function deleteExperience()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            if ($isAjax) {
                $this->textResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteExperience($id, $user_id)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->textResponse(true, 'Experience supprimee');
                }
                $this->flash('success', 'Experience supprimee');
            } else {
                if ($isAjax) {
                    $this->textResponse(false, 'Suppression impossible', 404);
                }
                $this->flash('error', 'Suppression impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur suppression', 500);
            }
            $this->flash('error', 'Erreur suppression');
        }

        $this->redirect('/profil');
    }

    public function updateExperience()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $poste = $this->normalizeText($_POST['poste'] ?? '', 100);
        $entreprise = $this->normalizeText($_POST['entreprise'] ?? '', 120);
        $date_debut = trim((string)($_POST['date_debut'] ?? ''));
        $date_fin = trim((string)($_POST['date_fin'] ?? ''));
        $description = $this->normalizeText($_POST['description'] ?? '', 1000);

        if (
            !$id ||
            !$this->isWithinLength($poste, 2, 100) ||
            $this->hasControlChars($poste) ||
            !$this->isValidDate($date_debut)
        ) {
            if ($isAjax) {
                $this->textResponse(false, 'Champs invalides', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        if ($entreprise !== '' && ($this->textLength($entreprise) > 120 || $this->hasControlChars($entreprise))) {
            if ($isAjax) {
                $this->textResponse(false, 'Entreprise invalide', 422);
            }
            $this->flash('error', 'Entreprise invalide');
            $this->redirect('/profil');
        }

        if ($description !== '' && ($this->textLength($description) > 1000 || $this->hasControlChars($description))) {
            if ($isAjax) {
                $this->textResponse(false, 'Description invalide', 422);
            }
            $this->flash('error', 'Description invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && !$this->isValidDate($date_fin)) {
            if ($isAjax) {
                $this->textResponse(false, 'Date de fin invalide', 422);
            }
            $this->flash('error', 'Date de fin invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && strtotime($date_fin) < strtotime($date_debut)) {
            if ($isAjax) {
                $this->textResponse(false, 'La date de fin doit etre apres la date de debut', 422);
            }
            $this->flash('error', 'La date de fin doit etre apres la date de debut');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateExperience($id, $user_id, $poste, $entreprise, $date_debut, $date_fin, $description)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->textResponse(true, 'Experience modifiee', 200, [
                        'experience' => [
                            'id_experience' => $id,
                            'poste' => $poste,
                            'entreprise' => $entreprise,
                            'date_debut' => $date_debut,
                            'date_fin' => $date_fin,
                            'description' => $description
                        ]
                    ]);
                }
                $this->flash('success', 'Experience modifiee');
            } else {
                if ($isAjax) {
                    $this->textResponse(false, 'Modification impossible', 409);
                }
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur modification', 500);
            }
            $this->flash('error', 'Erreur modification');
        }

        $this->redirect('/profil');
    }

    public function addCertification()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (!$this->isValidSkillName($nom, 2, 100)) {
            if ($isAjax) {
                $this->textResponse(false, 'Nom de certification invalide.', 422);
            }
            $this->flash('error', 'Nom de certification invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === null || $niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->textResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Niveau invalide.');
            $this->redirect('/profil');
        }

        try {
            $this->model->addCertification($user_id, $nom, $niveau);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->textResponse(true, 'Certification ajoutee');
            }
            $this->flash('success', 'Certification ajoutee');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de l\'ajout', 500);
            }
            $this->flash('error', 'Erreur lors de l\'ajout');
        }

        $this->redirect('/profil');
    }

    public function deleteCertification()
    {
        $user_id = $this->requireAuth();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteCertification($id, $user_id)) {
                $this->refreshInsightCache($user_id);
                $this->flash('success', 'Certification supprimee');
            } else {
                $this->flash('error', 'Suppression impossible');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur lors de la suppression');
        }

        $this->redirect('/profil');
    }

    public function updateCertification()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (!$id) {
            if ($isAjax) {
                $this->textResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        if (!$this->isValidSkillName($nom, 2, 100)) {
            if ($isAjax) {
                $this->textResponse(false, 'Nom de certification invalide.', 422);
            }
            $this->flash('error', 'Nom de certification invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->textResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateCertification($id, $user_id, $nom, $niveau)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->textResponse(true, 'Certification modifiee', 200, [
                        'certification' => [
                            'id_certification' => $id,
                            'nom_certification' => $nom,
                            'niveau' => $niveau
                        ]
                    ]);
                }
                $this->flash('success', 'Certification modifiee');
            } else {
                if ($isAjax) {
                    $this->textResponse(false, 'Modification impossible', 409);
                }
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur modification', 500);
            }
            $this->flash('error', 'Erreur modification');
        }

        $this->redirect('/profil');
    }

    public function update()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->textResponse(false, 'Methode non autorisee', 405);
            }
            $this->redirect('/profil');
        }

        $nom = $this->normalizeText($_POST['nom'] ?? '', 60);
        $prenom = $this->normalizeText($_POST['prenom'] ?? '', 60);
        $specialite = $this->normalizeText($_POST['specialite'] ?? '', 120);
        $ville = $this->normalizeText($_POST['ville'] ?? '', 120);
        $telephone = $this->normalizeText($_POST['telephone'] ?? '', 30);
        if (in_array(strtolower($telephone), ['non renseigne', 'non renseigné'], true)) {
            $telephone = '';
        }
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $disponibilite = strtolower(trim((string)($_POST['disponibilite'] ?? 'disponible')));
        $disponibilite_horaire = $this->normalizeText($_POST['disponibilite_horaire'] ?? '', 120);
        $disponibilite_message = $this->normalizeText($_POST['disponibilite_message'] ?? '', 120);
        $disponibilite_slots = trim((string)($_POST['disponibilite_slots'] ?? '[]'));
        $disponibilite_exceptions = trim((string)($_POST['disponibilite_exceptions'] ?? '[]'));
        $disponibilite_conges = trim((string)($_POST['disponibilite_conges'] ?? '[]'));

        if (!$this->isValidName($nom) || !$this->isValidName($prenom) || !$this->isValidEmail($email)) {
            if ($isAjax) {
                $this->textResponse(false, 'Nom, prenom ou email invalide.', 422);
            }
            $this->flash('error', 'Nom, prenom ou email invalide.');
            $this->redirect('/profil');
        }

        if (!in_array($disponibilite, ['disponible', 'occupe', 'indisponible'], true)) {
            if ($isAjax) {
                $this->textResponse(false, 'Disponibilite invalide.', 422);
            }
            $this->flash('error', 'Disponibilite invalide.');
            $this->redirect('/profil');
        }

        if (!$this->isValidPhone($telephone)) {
            if ($isAjax) {
                $this->textResponse(false, 'Telephone invalide.', 422);
            }
            $this->flash('error', 'Telephone invalide.');
            $this->redirect('/profil');
        }

        if (
            $this->hasControlChars($specialite) ||
            $this->hasControlChars($ville) ||
            $this->hasControlChars($disponibilite_horaire) ||
            $this->hasControlChars($disponibilite_message)
        ) {
            if ($isAjax) {
                $this->textResponse(false, 'Champs texte invalides.', 422);
            }
            $this->flash('error', 'Champs texte invalides.');
            $this->redirect('/profil');
        }

        if (
            $this->textLength($disponibilite_slots) > 5000 ||
            $this->textLength($disponibilite_exceptions) > 5000 ||
            $this->textLength($disponibilite_conges) > 5000
        ) {
            if ($isAjax) {
                $this->textResponse(false, 'Donnees de disponibilite trop volumineuses.', 422);
            }
            $this->flash('error', 'Donnees de disponibilite trop volumineuses.');
            $this->redirect('/profil');
        }

        // Disponibilite slots/exceptions/conges sont laissés tel quel (pas de validation JSON ici)

        try {
            $data = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'specialite' => $specialite,
                'ville' => $ville,
                'disponibilite' => $disponibilite,
                'disponibilite_horaire' => $disponibilite_horaire,
                'disponibilite_message' => $disponibilite_message,
                'disponibilite_slots' => $disponibilite_slots,
                'disponibilite_exceptions' => $disponibilite_exceptions,
                'disponibilite_conges' => $disponibilite_conges
            ];

            $this->model->updateProfil($user_id, $data);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->textResponse(true, 'Profil mis a jour !', 200, [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'telephone' => $telephone,
                    'specialite' => $specialite,
                    'ville' => $ville,
                    'disponibilite' => $disponibilite,
                    'disponibilite_horaire' => $disponibilite_horaire,
                    'disponibilite_message' => $disponibilite_message,
                    'disponibilite_slots' => $disponibilite_slots,
                    'disponibilite_exceptions' => $disponibilite_exceptions,
                    'disponibilite_conges' => $disponibilite_conges
                ]);
            }
            $this->redirect('/profil?success=1');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de la mise a jour du profil.', 500);
            }
            $this->flash('error', 'Erreur lors de la mise a jour du profil.');
            $this->redirect('/profil');
        }
    }

    public function addBio()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->textResponse(false, 'Methode non autorisee', 405);
            }
            $this->redirect('/profil');
        }

        $bio = $this->normalizeText($_POST['bio'] ?? '', 2000);

        if (!$this->isWithinLength($bio, 2, 2000) || $this->hasControlChars($bio)) {
            if ($isAjax) {
                $this->textResponse(false, 'Bio invalide.', 422);
            }
            $this->flash('error', 'Bio invalide.');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->addBioForUser($user_id, $bio)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->textResponse(true, 'Bio ajoutee.', 200, ['bio' => $bio]);
                }
                $this->flash('success', 'Bio ajoutee.');
            } else {
                if ($isAjax) {
                    $this->textResponse(false, 'Ajout impossible. Verifiez qu\'une competence existe deja.', 409);
                }
                $this->flash('error', 'Ajout impossible. Verifiez qu\'une competence existe deja.');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de l\'ajout de la bio.', 500);
            }
            $this->flash('error', 'Erreur lors de l\'ajout de la bio.');
        }

        $this->redirect('/profil');
    }

    public function updateBio()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->textResponse(false, 'Methode non autorisee', 405);
            }
            $this->redirect('/profil');
        }

        $bio = $this->normalizeText($_POST['bio'] ?? '', 2000);

        if (!$this->isWithinLength($bio, 2, 2000) || $this->hasControlChars($bio)) {
            if ($isAjax) {
                $this->textResponse(false, 'Bio invalide.', 422);
            }
            $this->flash('error', 'Bio invalide.');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateBioForUser($user_id, $bio)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->textResponse(true, 'Bio modifiee.', 200, ['bio' => $bio]);
                }
                $this->flash('success', 'Bio modifiee.');
            } else {
                if ($isAjax) {
                    $this->textResponse(false, 'Modification impossible. Ajoutez d\'abord une bio.', 409);
                }
                $this->flash('error', 'Modification impossible. Ajoutez d\'abord une bio.');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->textResponse(false, 'Erreur lors de la modification de la bio.', 500);
            }
            $this->flash('error', 'Erreur lors de la modification de la bio.');
        }

        $this->redirect('/profil');
    }

    public function deleteBio()
    {
        $user_id = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteBioForUser($user_id)) {
                $this->refreshInsightCache($user_id);
                $this->flash('success', 'Bio supprimee.');
            } else {
                $this->flash('error', 'Suppression impossible.');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur lors de la suppression de la bio.');
        }

        $this->redirect('/profil');
    }

    // =========================================================
    // MÉTIERS AVANCÉS AVEC IA
    // =========================================================

    /**
     * Génère une analyse IA locale pour un métier avancé.
     * Retourne un tableau avec recommandations, projection salariale,
     * tendance marché et score d'adéquation.
     */
    private function buildMetierIaAnalysis(
        string $titre,
        string $description,
        int    $niveauMaitrise,
        array  $technologies,
        array  $competences,
        array  $certifications
    ): array {
        $titleLower = $this->lowerText($titre . ' ' . $description . ' ' . implode(' ', $technologies));

        // Catalogue de projections salariales (Tunisie)
        $salaires = [
            'web'         => '1 800 – 3 500 TND/mois',
            'dev'         => '2 000 – 4 000 TND/mois',
            'data'        => '2 500 – 5 000 TND/mois',
            'ia'          => '3 000 – 6 000 TND/mois',
            'mobile'      => '2 000 – 4 500 TND/mois',
            'design'      => '1 500 – 3 000 TND/mois',
            'marketing'   => '1 200 – 2 800 TND/mois',
            'artisan'     => '1 000 – 2 500 TND/mois',
            'consultant'  => '2 500 – 5 500 TND/mois',
        ];

        $projection = '1 500 – 3 000 TND/mois';
        foreach ($salaires as $keyword => $range) {
            if ($this->containsAny($titleLower, [$keyword])) {
                $projection = $range;
                break;
            }
        }

        // Tendances marché
        $tendances = [
            ['keywords' => ['ia', 'intelligence artificielle', 'machine learning', 'llm'], 'label' => '🚀 En forte croissance'],
            ['keywords' => ['data', 'données', 'analytique', 'bi'], 'label' => '📈 Très demandé'],
            ['keywords' => ['cloud', 'devops', 'infrastructure', 'securite'], 'label' => '📈 En croissance'],
            ['keywords' => ['mobile', 'flutter', 'android', 'ios'], 'label' => '📊 Stable et porteur'],
            ['keywords' => ['web', 'react', 'vue', 'javascript', 'php'], 'label' => '📊 Marché mature & actif'],
            ['keywords' => ['design', 'ux', 'ui', 'figma'], 'label' => '🎨 Secteur créatif actif'],
            ['keywords' => ['artisan', 'céramique', 'poterie', 'textile'], 'label' => '🏺 Niche premium croissante'],
        ];

        $tendance = '📊 Marché stable';
        foreach ($tendances as $t) {
            if ($this->containsAny($titleLower, $t['keywords'])) {
                $tendance = $t['label'];
                break;
            }
        }

        // Score d'adéquation : basé sur niveau maîtrise + compétences liées
        $matchCount = 0;
        foreach ($technologies as $tech) {
            $techLower = $this->lowerText((string)$tech);
            foreach ($competences as $comp) {
                $compLower = $this->lowerText((string)($comp['nom_competence'] ?? ''));
                if ($compLower !== '' && strpos($compLower, $techLower) !== false) {
                    $matchCount++;
                }
            }
        }

        $techCount = max(1, count($technologies));
        $matchRatio = min(1.0, $matchCount / $techCount);
        $scoreAdequation = (int)round(($niveauMaitrise * 0.5) + ($matchRatio * 100 * 0.3) + (count($certifications) > 0 ? 20 : 0));
        $scoreAdequation = max(10, min(100, $scoreAdequation));

        // Recommandations IA
        $recommandations = [];
        if ($niveauMaitrise < 50) {
            $recommandations[] = 'Renforcez votre maîtrise avec des projets pratiques concrets.';
        } elseif ($niveauMaitrise < 75) {
            $recommandations[] = 'Visez une certification reconnue pour valoriser ce métier.';
        } else {
            $recommandations[] = 'Niveau avancé : pensez à partager votre expertise (mentorat, blog).';
        }

        if (count($technologies) < 2) {
            $recommandations[] = 'Élargissez votre stack technologique pour ce métier.';
        }

        if ($matchCount === 0) {
            $recommandations[] = 'Ajoutez des compétences liées aux technologies de ce métier dans votre profil.';
        }

        if (empty($certifications)) {
            $recommandations[] = 'Une certification dans ce domaine augmenterait significativement votre score.';
        }

        if ($this->containsAny($titleLower, ['ia', 'machine learning', 'data'])) {
            $recommandations[] = 'Les projets open-source sur GitHub renforcent fortement la visibilité IA.';
        }

        return [
            'recommandations'   => array_slice($recommandations, 0, 4),
            'projectionSalaire' => $projection,
            'tendanceMarche'    => $tendance,
            'scoreAdequation'   => $scoreAdequation,
        ];
    }

    /**
     * Sauvegarde (ajout ou mise à jour) d'un métier avancé (slot 1 ou 2).
     */
    public function upsertMetierAvance(): void
    {
        $userId = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) { $this->textResponse(false, 'Méthode non autorisée', 405); }
            $this->redirect('/profil');
        }

        $slot           = max(1, min(2, (int)($_POST['slot'] ?? 1)));
        $titre          = $this->normalizeText($_POST['titre'] ?? '', 150);
        $description    = $this->normalizeText($_POST['description'] ?? '', 1000);
        $niveauMaitrise = max(0, min(100, (int)($_POST['niveau_maitrise'] ?? 50)));
        $techRaw        = trim((string)($_POST['technologies'] ?? ''));
        $technologies   = [];

        if ($techRaw !== '') {
            foreach (explode(',', $techRaw) as $t) {
                $clean = $this->normalizeText($t, 60);
                if ($clean !== '') {
                    $technologies[] = $clean;
                }
            }
        }
        $technologies = array_slice(array_values(array_unique($technologies)), 0, 10);

        if (!$this->isValidSkillName($titre, 2, 150)) {
            if ($isAjax) { $this->textResponse(false, 'Titre du métier invalide.', 422); }
            $this->flash('error', 'Titre du métier invalide.');
            $this->redirect('/profil');
        }

        try {
            $idMetier = $this->model->upsertMetierAvance(
                $userId, $slot, $titre, $description, $niveauMaitrise, $technologies
            );

            // Lancer l'analyse IA immédiatement
            $competences    = $this->getUserCompetencesManyToMany($userId);
            $certifications = $this->model->getCertifications($userId);
            $ia = $this->buildMetierIaAnalysis(
                $titre, $description, $niveauMaitrise, $technologies, $competences, $certifications
            );

            $this->model->saveMetierIaData(
                $idMetier, $userId,
                $ia['recommandations'],
                $ia['projectionSalaire'],
                $ia['tendanceMarche'],
                $ia['scoreAdequation']
            );

            if ($isAjax) {
                $this->textResponse(true, 'Métier avancé sauvegardé.', 200, [
                    'id_metier'           => $idMetier,
                    'slot'                => $slot,
                    'titre'               => $titre,
                    'niveauMaitrise'      => $niveauMaitrise,
                    'technologies'        => $technologies,
                    'ia'                  => $ia,
                ]);
            }
            $this->flash('success', 'Métier avancé "' . $titre . '" sauvegardé avec analyse IA.');
        } catch (Exception $e) {
            if ($isAjax) { $this->textResponse(false, 'Erreur lors de la sauvegarde du métier.', 500); }
            $this->flash('error', 'Erreur lors de la sauvegarde du métier.');
        }

        $this->redirect('/profil#panel-bio');
    }

    /**
     * Supprime un métier avancé.
     */
    public function deleteMetierAvance(): void
    {
        $userId = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();
        $idMetier = filter_input(INPUT_POST, 'id_metier', FILTER_VALIDATE_INT);

        if (!$idMetier) {
            if ($isAjax) { $this->textResponse(false, 'ID invalide', 422); }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteMetierAvance($idMetier, $userId)) {
                if ($isAjax) { $this->textResponse(true, 'Métier supprimé.'); }
                $this->flash('success', 'Métier avancé supprimé.');
            } else {
                if ($isAjax) { $this->textResponse(false, 'Suppression impossible.', 404); }
                $this->flash('error', 'Suppression impossible.');
            }
        } catch (Exception $e) {
            if ($isAjax) { $this->textResponse(false, 'Erreur suppression.', 500); }
            $this->flash('error', 'Erreur suppression.');
        }

        $this->redirect('/profil');
    }

    /**
     * Relance l'analyse IA pour un métier existant.
     */
    public function analyseMetierAvance(): void
    {
        $userId = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();
        $idMetier = filter_input(INPUT_POST, 'id_metier', FILTER_VALIDATE_INT);

        if (!$idMetier) {
            if ($isAjax) { $this->textResponse(false, 'ID invalide', 422); }
            $this->redirect('/profil');
        }

        $metier = $this->model->getMetierById($idMetier, $userId);
        if (!$metier) {
            if ($isAjax) { $this->textResponse(false, 'Métier introuvable.', 404); }
            $this->redirect('/profil');
        }

        $competences    = $this->getUserCompetencesManyToMany($userId);
        $certifications = $this->model->getCertifications($userId);
        $ia = $this->buildMetierIaAnalysis(
            (string)$metier['titre'],
            (string)($metier['description'] ?? ''),
            (int)$metier['niveau_maitrise'],
            (array)$metier['technologies'],
            $competences,
            $certifications
        );

        $this->model->saveMetierIaData(
            $idMetier, $userId,
            $ia['recommandations'],
            $ia['projectionSalaire'],
            $ia['tendanceMarche'],
            $ia['scoreAdequation']
        );

        if ($isAjax) {
            $this->textResponse(true, 'Analyse IA complétée.', 200, ['ia' => $ia]);
        }
        $this->flash('success', 'Analyse IA relancée.');
        $this->redirect('/profil');
    }

    private function decodeRequestPayload(): array
    {
        return $_POST;
    }


    private function buildCvSeedData(int $userId): array
    {
        $user = $this->model->getUserById($userId) ?: [];
        $bio = $this->model->getBioByUserId($userId);
        $stats = $this->model->getStats($userId);
        $competences = $this->getUserCompetencesManyToMany($userId);
        $certifications = $this->model->getCertifications($userId);
        $experiences = $this->model->getExperiences($userId);

        $specialite = (string)($user['specialite'] ?? 'Professionnel polyvalent');
        $ville = (string)($user['ville'] ?? 'Tunisie');
        $email = (string)($user['email'] ?? '');
        $telephone = trim((string)($user['telephone'] ?? ''));
        if ($telephone === '') {
            $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
        }

        $insightData = $this->buildInsightData(
            (string)$specialite,
            (string)$bio,
            (string)$ville,
            (string)$email,
            (string)$telephone,
            $competences,
            $certifications,
            $experiences,
            [],
            $stats
        );

        return [
            'personal' => [
                'fullName' => trim(((string)($user['prenom'] ?? '')) . ' ' . ((string)($user['nom'] ?? ''))),
                'title' => $specialite,
                'email' => $email,
                'phone' => $telephone,
                'city' => $ville,
                'summary' => trim($bio) !== '' ? (string)$bio : (string)($insightData['cvSummary'] ?? '')
            ],
            'skills' => array_map(static function (array $item): array {
                return [
                    'name' => (string)($item['nom_competence'] ?? ''),
                    'level' => (int)($item['niveau'] ?? 50)
                ];
            }, $competences),
            'experiences' => array_map(static function (array $item): array {
                return [
                    'role' => (string)($item['poste'] ?? ''),
                    'company' => (string)($item['entreprise'] ?? ''),
                    'start' => (string)($item['date_debut'] ?? ''),
                    'end' => (string)($item['date_fin'] ?? ''),
                    'description' => (string)($item['description'] ?? '')
                ];
            }, $experiences),
            'education' => array_map(static function (array $item): array {
                return [
                    'degree' => (string)($item['nom_certification'] ?? 'Certification'),
                    'school' => 'Certification professionnelle',
                    'start' => '',
                    'end' => '',
                    'description' => 'Niveau estime: ' . (int)($item['niveau'] ?? 0) . '%'
                ];
            }, $certifications),
            'scores' => [
                'ats' => (int)($insightData['profileScore'] ?? 60),
                'impact' => (int)($insightData['popularityScore'] ?? 55),
                'readability' => 78
            ],
            'tips' => [
                'Ajoutez des verbes d action et des resultats mesurables dans chaque experience.',
                'Gardez des competences ciblees sur le poste vise pour renforcer le score ATS.',
                'Conservez un resume clair en 4 a 6 lignes max pour une meilleure lisibilite.'
            ],
            'language' => 'fr',
            'template' => 'moderne',
            'primaryColor' => '#2E6B3E'
        ];
    }

    private function getOllamaBaseUrl(): string
    {
        // Use explicit IPv4 loopback to avoid IPv6/localhost resolution issues on Windows
        return 'http://127.0.0.1:11434';
    }

    private function buildOllamaPrompt(array $profileData, string $language, string $userPrompt): string
    {
        $langs = [
            'fr' => 'Tu es Assistant Professionnel Intelligent HERFA.\n\nTu aides les artisans et professionnels tunisiens à créer un CV professionnel moderne et impactant.\n\nAnalyse les informations suivantes et génère UNIQUEMENT EN JSON structuré avec ces clés exactes:\n- resume_professionnel\n- competences_reformulees\n- experiences_reformulees\n- qualites_professionnelles\n- slogan_professionnel\n- recommandations\n- conseils_cv\n\nLes conseils_cv doivent contenir 3 à 5 conseils courts, concrets et actionnables pour améliorer le CV.\n',
            'en' => 'You are an Intelligent Professional Assistant for HERFA.\n\nYou help Tunisian craftspeople and professionals create a modern and impactful CV.\n\nAnalyze the following information and generate ONLY in structured JSON with these exact keys:\n- professional_summary\n- reformulated_skills\n- reformulated_experiences\n- professional_qualities\n- professional_slogan\n- recommendations\n- cv_tips\n\nThe cv_tips field must contain 3 to 5 short, concrete, actionable tips to improve the CV.\n',
            'ar' => 'أنت مساعد احترافي ذكي لـ HERFA.\n\nتساعد الحرفيين والمهنيين التونسيين على إنشاء سيرة ذاتية حديثة وفعالة.\n\nحلل المعلومات التالية وأنتج JSON منظم فقط بهذه المفاتيح الدقيقة:\n- resume_professionnel\n- competences_reformulees\n- experiences_reformulees\n- qualites_professionnelles\n- slogan_professionnel\n- recommandations\n- conseils_cv\n\nيجب أن تحتوي conseils_cv على 3 إلى 5 نصائح قصيرة وعملية لتحسين السيرة الذاتية.\n'
        ];

        $header = $langs[$language] ?? $langs['fr'];

        $prompt = $header;
        $prompt .= "=== INFORMATIONS PROFIL ===\n";
        $prompt .= "Nom: " . $this->normalizeText($profileData['personal']['fullName'] ?? '', 100) . "\n";
        $prompt .= "Métier/Spécialité: " . $this->normalizeText($profileData['personal']['title'] ?? '', 100) . "\n";
        $prompt .= "Email: " . $this->normalizeText($profileData['personal']['email'] ?? '', 100) . "\n";
        $prompt .= "Téléphone: " . $this->normalizeText($profileData['personal']['phone'] ?? '', 20) . "\n";
        $prompt .= "Ville: " . $this->normalizeText($profileData['personal']['city'] ?? '', 50) . "\n";
        $prompt .= "Résumé actuel: " . $this->normalizeText($profileData['personal']['summary'] ?? '', 300) . "\n\n";

        if (!empty($profileData['skills'])) {
            $prompt .= "=== COMPÉTENCES ===\n";
            foreach ($profileData['skills'] as $skill) {
                $skillName = $this->normalizeText($skill['name'] ?? '', 100);
                $skillLevel = (int)($skill['level'] ?? 50);
                $prompt .= "- " . $skillName . " (Niveau: " . $skillLevel . "%)\n";
            }
            $prompt .= "\n";
        }

        if (!empty($profileData['experiences'])) {
            $prompt .= "=== EXPÉRIENCES ===\n";
            foreach ($profileData['experiences'] as $exp) {
                $prompt .= "Poste: " . $this->normalizeText($exp['role'] ?? '', 100) . "\n";
                $prompt .= "Entreprise: " . $this->normalizeText($exp['company'] ?? '', 100) . "\n";
                $prompt .= "Période: " . $this->normalizeText($exp['start'] ?? '', 50) . " - " . $this->normalizeText($exp['end'] ?? '', 50) . "\n";
                $prompt .= "Description: " . $this->normalizeText($exp['description'] ?? '', 500) . "\n\n";
            }
        }

        if (!empty($profileData['education'])) {
            $prompt .= "=== CERTIFICATIONS ===\n";
            foreach ($profileData['education'] as $edu) {
                $prompt .= "- " . $this->normalizeText($edu['degree'] ?? '', 100) . " (" . $this->normalizeText($edu['description'] ?? '', 100) . ")\n";
            }
            $prompt .= "\n";
        }

        if (!empty($profileData['scores'])) {
            $prompt .= "=== SCORES DU PROFIL ===\n";
            $prompt .= "Score ATS: " . (int)($profileData['scores']['ats'] ?? 0) . "%\n";
            $prompt .= "Score Impact: " . (int)($profileData['scores']['impact'] ?? 0) . "%\n";
            $prompt .= "Lisibilité: " . (int)($profileData['scores']['readability'] ?? 0) . "%\n\n";
        }

        if ($userPrompt !== '') {
            $prompt .= "=== OBJECTIF SUPPLÉMENTAIRE ===\n";
            $prompt .= $this->normalizeText($userPrompt, 500) . "\n\n";
        }

        $prompt .= "Génère un CV professionnel structuré et impactant au format JSON.\n";
        $prompt .= "Assure-toi que chaque section est pertinente et optimisée pour les recruteurs et les ATS.\n";

        return $prompt;
    }

    private function callOllamaCvGenerate(array $profileData, string $language, string $userPrompt, string $model = 'mistral'): ?array
    {
        $prompt = $this->buildOllamaPrompt($profileData, $language, $userPrompt);

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
            'temperature' => 0.7
        ];

        $url = $this->getOllamaBaseUrl() . '/api/generate';

        // Preferred: cURL with IPv4 resolution and sensible timeouts
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch !== false) {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
                // Force IPv4 to avoid Windows resolving 'localhost' to IPv6 (::1)
                if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
                    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                }
                // Use HTTP/1.1 for compatibility
                if (defined('CURLOPT_HTTP_VERSION') && defined('CURL_HTTP_VERSION_1_1')) {
                    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                }

                $response = curl_exec($ch);
                $curlErr = null;
                if ($response === false) {
                    $curlErr = curl_error($ch);
                }
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($response !== false && $httpCode === 200) {
                    $data = json_decode($response, true);
                } else {
                    error_log('[ProfilController] Ollama cURL error: ' . ($curlErr ?: 'http_code=' . $httpCode));
                    $data = null;
                }
            } else {
                $data = null;
            }
        } else {
            $data = null;
        }

        // Fallback: try file_get_contents with stream context if cURL failed
        if ((!is_array($data) || !isset($data['response'])) && function_exists('stream_context_create')) {
            $ctxOpts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'content' => json_encode($payload),
                    'timeout' => 120
                ]
            ];
            $ctx = stream_context_create($ctxOpts);
            $response2 = @file_get_contents($url, false, $ctx);
            if ($response2 !== false) {
                $data = json_decode($response2, true);
            } else {
                error_log('[ProfilController] Ollama fallback file_get_contents failed');
            }
        }

        if (!is_array($data) || !isset($data['response'])) {
            return null;
        }

        $responseText = (string)($data['response'] ?? '');
        $jsonMatch = null;
        if (preg_match('/\{[\s\S]*\}/', $responseText, $jsonMatch)) {
            $parsed = json_decode($jsonMatch[0], true);
            if (is_array($parsed)) {
                return $parsed;
            }
        }

        return null;
    }

    private function parseOllamaResponse(?array $ollamaData, array $seed): array
    {
        if ($ollamaData === null) {
            return $this->buildLocalCvFromSeed($seed, 'fr');
        }

        $result = $seed;

        if (isset($ollamaData['resume_professionnel'])) {
            $result['personal']['summary'] = (string)$ollamaData['resume_professionnel'];
        }

        if (isset($ollamaData['professional_summary'])) {
            $result['personal']['summary'] = (string)$ollamaData['professional_summary'];
        }

        if (isset($ollamaData['competences_reformulees'])) {
            $competences = $ollamaData['competences_reformulees'];
            if (is_array($competences)) {
                $result['skills'] = [];
                foreach ($competences as $comp) {
                    if (is_string($comp)) {
                        $result['skills'][] = ['name' => $comp, 'level' => 75];
                    } elseif (is_array($comp) && isset($comp['name'])) {
                        $result['skills'][] = [
                            'name' => (string)$comp['name'],
                            'level' => (int)($comp['level'] ?? 75)
                        ];
                    }
                }
            }
        }

        if (isset($ollamaData['reformulated_skills'])) {
            $competences = $ollamaData['reformulated_skills'];
            if (is_array($competences)) {
                $result['skills'] = [];
                foreach ($competences as $comp) {
                    if (is_string($comp)) {
                        $result['skills'][] = ['name' => $comp, 'level' => 75];
                    } elseif (is_array($comp) && isset($comp['name'])) {
                        $result['skills'][] = [
                            'name' => (string)$comp['name'],
                            'level' => (int)($comp['level'] ?? 75)
                        ];
                    }
                }
            }
        }

        if (isset($ollamaData['qualites_professionnelles'])) {
            $qualities = $ollamaData['qualites_professionnelles'];
            if (is_array($qualities) && !isset($result['qualities'])) {
                $result['qualities'] = $qualities;
            }
        }

        if (isset($ollamaData['professional_qualities'])) {
            $qualities = $ollamaData['professional_qualities'];
            if (is_array($qualities) && !isset($result['qualities'])) {
                $result['qualities'] = $qualities;
            }
        }

        if (isset($ollamaData['slogan_professionnel'])) {
            $result['slogan'] = (string)$ollamaData['slogan_professionnel'];
        }

        if (isset($ollamaData['professional_slogan'])) {
            $result['slogan'] = (string)$ollamaData['professional_slogan'];
        }

        if (isset($ollamaData['recommandations'])) {
            $recs = $ollamaData['recommandations'];
            $result['recommendations'] = is_array($recs) ? $recs : [(string)$recs];
        }

        if (isset($ollamaData['recommendations'])) {
            $recs = $ollamaData['recommendations'];
            $result['recommendations'] = is_array($recs) ? $recs : [(string)$recs];
        }

        if (isset($ollamaData['conseils_cv'])) {
            $advice = $ollamaData['conseils_cv'];
            $result['aiAdvice'] = is_array($advice) ? $advice : [(string)$advice];
        }

        if (isset($ollamaData['cv_tips'])) {
            $advice = $ollamaData['cv_tips'];
            $result['aiAdvice'] = is_array($advice) ? $advice : [(string)$advice];
        }

        if (isset($ollamaData['advice'])) {
            $advice = $ollamaData['advice'];
            $result['aiAdvice'] = is_array($advice) ? $advice : [(string)$advice];
        }

        return $result;
    }

    private function buildLocalCvFromSeed(array $seed, string $language, string $prompt = ''): array
    {
        $result = $seed;
        $result['language'] = $language;

        $summary = trim((string)($result['personal']['summary'] ?? ''));
        if ($summary === '') {
            $title = (string)($result['personal']['title'] ?? 'Professionnel');
            $summary = 'Professionnel ' . $title . ' oriente resultats, capable de contribuer rapidement a des projets concrets.';
        }
        if ($prompt !== '') {
            $summary .= ' Objectif cible: ' . $this->normalizeText($prompt, 260) . '.';
        }
        $result['personal']['summary'] = $summary;

        $result['tips'] = [
            'Ajoutez 3 a 5 mots-cles metier exacts dans le titre et le resume.',
            'Transformez chaque experience en impact: action + contexte + resultat.',
            'Conservez une hierarchie visuelle simple pour une lecture rapide par recruteurs et ATS.'
        ];

        $result['aiAdvice'] = [
            'Gardez le titre de poste très précis et orienté métier.',
            'Commencez le résumé par votre valeur ajoutée la plus forte.',
            'Limitez les compétences à celles qui sont utiles pour le poste visé.'
        ];

        return $result;
    }

    public function generateCvAi(): void
    {
        $userId = $this->requireAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(false, 'Méthode non autorisée', 405);
            }
            $this->textResponse(false, 'Methode non autorisee', 405);
        }

        $prompt = $this->normalizeText($_POST['prompt'] ?? '', 1200);
        $language = strtolower(trim((string)($_POST['language'] ?? 'fr')));
        $model = strtolower(trim((string)($_POST['model'] ?? 'mistral')));

        if (!in_array($language, ['fr', 'en', 'ar'], true)) {
            $language = 'fr';
        }

        if (!in_array($model, ['mistral', 'llama3', 'llama2', 'neural-chat', 'starling-lm'], true)) {
            $model = 'mistral';
        }

        // Build seed data from database
        $seed = $this->buildCvSeedData($userId);

        // Call Ollama API
        $ollamaResponse = $this->callOllamaCvGenerate($seed, $language, $prompt, $model);

        // Parse response (fallback to local generation if Ollama fails)
        $cvData = $this->parseOllamaResponse($ollamaResponse, $seed);

        // If AJAX request, return JSON
        if ($this->isAjaxRequest()) {
            $this->jsonResponse(
                true,
                $ollamaResponse !== null ? 'CV généré avec Ollama' : 'CV généré localement',
                200,
                $cvData
            );
        }

        // For regular POST, save and redirect
        $this->flash('success', $ollamaResponse !== null ? 'CV généré avec Ollama' : 'CV généré localement');
        $this->redirect('/profil');
    }

    public function optimizeCvAi(): void
    {
        $userId = $this->requireAuth();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            if ($this->isAjaxRequest()) {
                $this->jsonResponse(false, 'Méthode non autorisée', 405);
            }
            $this->textResponse(false, 'Methode non autorisee', 405);
        }

        $cvData = $_POST['cv'] ?? '{}';
        $language = strtolower(trim((string)($_POST['language'] ?? 'fr')));
        $model = strtolower(trim((string)($_POST['model'] ?? 'mistral')));

        if (!in_array($language, ['fr', 'en', 'ar'], true)) {
            $language = 'fr';
        }

        if (!in_array($model, ['mistral', 'llama3', 'llama2', 'neural-chat', 'starling-lm'], true)) {
            $model = 'mistral';
        }

        $parsed = is_string($cvData) ? json_decode($cvData, true) : $cvData;
        if (!is_array($parsed)) {
            $parsed = $this->buildCvSeedData($userId);
        }

        // Build optimization prompt
        $optimizationPrompt = "Optimise ce CV pour qu'il soit plus impactant et attire davantage les recruteurs. ";
        $optimizationPrompt .= "Fournisse des recommandations précises pour améliorer chaque section.";

        $ollamaResponse = $this->callOllamaCvGenerate($parsed, $language, $optimizationPrompt, $model);

        if ($ollamaResponse !== null && isset($ollamaResponse['recommandations'])) {
            $parsed['recommendations'] = is_array($ollamaResponse['recommandations']) 
                ? $ollamaResponse['recommandations'] 
                : [$ollamaResponse['recommandations']];
        } elseif ($ollamaResponse !== null && isset($ollamaResponse['recommendations'])) {
            $parsed['recommendations'] = is_array($ollamaResponse['recommendations']) 
                ? $ollamaResponse['recommendations'] 
                : [$ollamaResponse['recommendations']];
        }

        if ($this->isAjaxRequest()) {
            $this->jsonResponse(
                true,
                $ollamaResponse !== null ? 'CV optimisé avec Ollama' : 'CV optimisé localement',
                200,
                $parsed
            );
        }

        $this->flash('success', $ollamaResponse !== null ? 'CV optimisé avec Ollama' : 'CV optimisé localement');
        $this->redirect('/profil');
    }

    public function addPortfolioFile()
    {
        $user_id = $this->requireAuth();

        if (!isset($_FILES['portfolio_file']) || $_FILES['portfolio_file']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Fichier invalide.');
            $this->redirect('/profil');
        }

        $titre = $this->normalizeText($_POST['titre'] ?? '', 120);
        if ($titre !== '' && $this->hasControlChars($titre)) {
            $this->flash('error', 'Titre invalide.');
            $this->redirect('/profil');
        }

        $defaultRealisations = $this->getDefaultPortfolioRealisations();
        $realisationDefault = trim((string)($_POST['realisation_default'] ?? ''));
        $realisationCustom = $this->normalizeText($_POST['realisation_custom'] ?? '', 120);

        if ($realisationDefault === '') {
            $this->flash('error', 'Veuillez choisir une realisation.');
            $this->redirect('/profil');
        }

        if ($realisationDefault !== '__custom__' && !in_array($realisationDefault, $defaultRealisations, true)) {
            $this->flash('error', 'Realisation invalide.');
            $this->redirect('/profil');
        }

        $realisation = $realisationDefault === '__custom__'
            ? $realisationCustom
            : $this->normalizeText($realisationDefault, 120);

        if (!$this->isWithinLength($realisation, 2, 120) || $this->hasControlChars($realisation)) {
            $this->flash('error', 'La realisation est invalide.');
            $this->redirect('/profil');
        }

        try {
            $pdo = getPDO();
            $colStmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'portfolio_files' AND COLUMN_NAME = 'realisation'");
            $colStmt->execute();
            $hasRealisationColumn = (int)$colStmt->fetchColumn() > 0;

            if (!$hasRealisationColumn) {
                $this->flash('error', 'Colonne realisation manquante dans portfolio_files.');
                $this->redirect('/profil');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur de verification de la colonne realisation.');
            $this->redirect('/profil');
        }

        $originalName = $_FILES['portfolio_file']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ((int)($_FILES['portfolio_file']['size'] ?? 0) > 8 * 1024 * 1024) {
            $this->flash('error', 'Le fichier depasse 8 Mo.');
            $this->redirect('/profil');
        }

        if ($ext !== 'pdf') {
            $this->flash('error', 'Seuls les fichiers PDF sont autorises.');
            $this->redirect('/profil');
        }

        $uploadDir = __DIR__ . '/../public/uploads/portfolio';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        if ($baseName === '') {
            $baseName = 'document';
        }

        $storedName = $baseName . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($_FILES['portfolio_file']['tmp_name'], $targetPath)) {
            $this->flash('error', 'Echec du televersement.');
            $this->redirect('/profil');
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO portfolio_files (id_user, titre, realisation, file_name, file_path, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $user_id,
                $titre === '' ? null : $titre,
                $realisation,
                $originalName,
                app_url('/public/uploads/portfolio/' . $storedName)
            ]);

            $this->refreshInsightCache($user_id);
            $this->flash('success', 'Document ajoute.');
        } catch (Exception $e) {
            $this->flash('error', 'Erreur lors de l\'enregistrement.');
        }

        $this->redirect('/profil');
    }
}