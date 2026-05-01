<?php
// controllers/ProfilController.php

require_once dirname(__DIR__, 3) . '/config/Config.php';
require_once __DIR__ . '/ProfilModel.php';

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
        $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');

        return $requestedWith === 'xmlhttprequest' || strpos($accept, 'application/json') !== false;
    }

    private function jsonResponse(bool $success, string $message, int $statusCode = 200, array $data = []): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        $payload = [
            'success' => $success,
            'message' => $message
        ];

        if (!empty($data)) {
            $payload = array_merge($payload, $data);
        }

        echo json_encode($payload);
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

        $skillAverage = $skillCount > 0 ? array_sum($competenceScores) / $skillCount : 0;
        $certAverage = $certCount > 0 ? array_sum($certificationScores) / $certCount : 0;

        $profileSignals = [
            trim((string)$specialite) !== '' && $this->lowerText(trim((string)$specialite)) !== 'specialite non renseignee',
            trim((string)$bio) !== '',
            trim((string)$ville) !== '' && $this->lowerText(trim((string)$ville)) !== 'non renseigne',
            trim((string)$email) !== '' && $this->lowerText(trim((string)$email)) !== 'non renseigne',
            trim((string)$telephone) !== '' && $this->lowerText(trim((string)$telephone)) !== 'non renseigne',
            $skillCount > 0,
            $certCount > 0,
            $experienceCount > 0,
            $portfolioCount > 0
        ];

        $completedSignals = count(array_filter($profileSignals));
        $completenessScore = (int)round(($completedSignals / max(1, count($profileSignals))) * 100);

        $skillDepthScore = min(100, (int)round(($skillAverage * 0.6) + min(25, $skillCount * 4)));
        $certQualityScore = min(100, (int)round(($certAverage * 0.7) + min(20, $certCount * 6)));
        $portfolioQualityScore = min(100, (int)round(min(55, $portfolioCount * 18) + min(25, $experienceCount * 6) + min(20, $portfolioCount > 0 ? 10 : 0)));

        $profileScore = (int)round(
            (0.38 * $skillDepthScore) +
            (0.24 * $certQualityScore) +
            (0.18 * $portfolioQualityScore) +
            (0.20 * $completenessScore)
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
            (0.20 * $completenessScore)
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

            $specialite = trim((string)($user['specialite'] ?? ''));
            $bio = $this->model->getBioByUserId($userId);
            $ville = trim((string)($user['ville'] ?? ''));
            $email = trim((string)($user['email'] ?? ''));
            $telephone = trim((string)($user['telephone'] ?? ''));
            if ($telephone === '') {
                $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
            }
            $telephone = trim($telephone);

            $stats = $this->model->getStats($userId);
            $competences = $this->model->getCompetences($userId);
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
        $viewer_id = $this->requireAuth();
        $requestedUserId = (int)($_GET['user_id'] ?? $viewer_id);
        if ($requestedUserId <= 0) {
            $requestedUserId = $viewer_id;
        }
        $isOwnProfile = $requestedUserId === $viewer_id;

        if ($isOwnProfile) {
            $this->refreshInsightCache($requestedUserId);
        }

        $user = $this->model->getUserById($requestedUserId);
        if (!$user) {
            $this->flash('error', 'Profil introuvable.');
            $this->redirect('/dashboard');
        }

        $stats = $this->model->getStats($requestedUserId);
        $competenceCatalog = $this->model->getCompetenceCatalog();
        $competences = $this->model->getCompetences($requestedUserId);
        $certifications = $this->model->getCertifications($requestedUserId);
        $experiences = $this->model->getExperiences($requestedUserId);
        $portfolioFiles = $this->model->getPortfolioFiles($requestedUserId);
        $onboarding = $this->model->getOnboardingByUserId($requestedUserId);
        if ($onboarding && is_array($onboarding)) {
            // Expose onboarding fields as simple variables for the view
            $onboard_skills = trim((string)($onboarding['skills'] ?? ''));
            $onboard_craft_focus = trim((string)($onboarding['craft_focus'] ?? ''));
            $onboard_interests = trim((string)($onboarding['interests'] ?? ''));
        } else {
            $onboard_skills = '';
            $onboard_craft_focus = '';
            $onboard_interests = '';
        }
        $avis = $this->model->getAvis($requestedUserId);

        // Métiers avancés (max 2 par utilisateur)
        $metiersAvances = $isOwnProfile ? $this->model->getMetiersAvances($requestedUserId) : [];

        $initials = strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? 'U', 0, 1));

        $specialite = trim((string)($user['specialite'] ?? ''));
        $bio = $this->model->getBioByUserId($requestedUserId);
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
            $disponibilite_horaire = 'Horaires non renseignes';
        }
        $ville = trim((string)($user['ville'] ?? ''));
        $email = trim((string)($user['email'] ?? ''));
        $telephone = trim((string)($user['telephone'] ?? ''));
        if ($telephone === '') {
            $telephone = trim((string)($user['profil_telephone'] ?? ($user['num_tel'] ?? '')));
        }
        $telephone = trim($telephone);
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
        if ($isOwnProfile && isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
        }

        $defaultRealisations = $this->model->getPortfolioRealisationsCatalog();

        require_once dirname(__DIR__, 3) . '/views/profil/profile.php';
    }

    public function gestion_competences()
    {
        $user_id = $this->requireAuth();
        $competenceCatalog = $this->model->getCompetenceCatalog();
        $competences = $this->model->getCompetences($user_id);

        require_once dirname(__DIR__, 3) . '/views/profil/gestion_competences.php';
    }

    public function gestion_certifications()
    {
        $user_id = $this->requireAuth();
        $certifications = $this->model->getCertifications($user_id);

        require_once dirname(__DIR__, 3) . '/views/profil/gestion_certifications.php';
    }

    public function addCompetence()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $catalogChoice = trim((string)($_POST['competence_catalog_choice'] ?? ''));
        $catalogId = null;
        $catalogName = '';
        if ($catalogChoice !== '') {
            if (strncmp($catalogChoice, 'id:', 3) === 0) {
                $catalogId = (int)substr($catalogChoice, 3);
            } elseif (strncmp($catalogChoice, 'name:', 5) === 0) {
                $catalogName = trim(substr($catalogChoice, 5));
            }
        }

        $nom = $this->normalizeText($_POST['nom'] ?? '', 80);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if ($catalogId) {
            $catalog = $this->model->getCompetenceCatalogById($catalogId);
            if (!$catalog) {
                if ($isAjax) {
                    $this->jsonResponse(false, 'Competence cataloguee introuvable.', 404);
                }
                $this->flash('error', 'Competence cataloguee introuvable.');
                $this->redirect('/profil');
            }

            $nom = trim((string)($catalog['nom_competence'] ?? $nom));
            if ($description === '') {
                $description = trim((string)($catalog['description'] ?? ''));
            }
        } elseif ($catalogName !== '' && $nom === '') {
            $nom = $catalogName;
        }

        if ($catalogId === null && $catalogName === '' && !$this->isValidSkillName($nom, 2, 80)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Nom de competence invalide.', 422);
            }
            $this->flash('error', 'Nom de competence invalide.');
            $this->redirect('/profil');
        }

        if (!$this->isValidSkillDescription($description, 500)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Description de competence invalide.', 422);
            }
            $this->flash('error', 'Description de competence invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Niveau invalide.');
            $this->redirect('/profil');
        }

        try {
            $this->model->addCompetence($user_id, $nom, $description, $niveau, $catalogId);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->jsonResponse(true, 'Competence ajoutee');
            }
            $this->flash('success', 'Competence ajoutee');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur lors de l\'ajout', 500);
            }
            $this->flash('error', 'Erreur lors de l\'ajout');
        }

        $this->redirect('/profil');
    }

    public function deleteCompetence()
    {
        $user_id = $this->requireAuth();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteCompetence($id, $user_id)) {
                $this->refreshInsightCache($user_id);
                $this->flash('success', 'Competence supprimee');
            } else {
                $this->flash('error', 'Suppression impossible');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur suppression');
        }

        $this->redirect('/profil');
    }

    public function updateCompetence()
    {
        $user_id = $this->requireAuth();
        $isAjax = $this->isAjaxRequest();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 80);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (!$id) {
            if ($isAjax) {
                $this->jsonResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        if (!$this->isValidSkillName($nom, 2, 80)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Nom de competence invalide.', 422);
            }
            $this->flash('error', 'Nom de competence invalide.');
            $this->redirect('/profil');
        }

        if (!$this->isValidSkillDescription($description, 500)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Description de competence invalide.', 422);
            }
            $this->flash('error', 'Description de competence invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateCompetence($id, $user_id, $nom, $description, $niveau)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Competence modifiee', 200, [
                        'competence' => [
                            'id_competence' => $id,
                            'nom_competence' => $nom,
                            'description' => $description,
                            'niveau' => $niveau
                        ]
                    ]);
                }
                $this->flash('success', 'Competence modifiee');
            } else {
                if ($isAjax) {
                    $this->jsonResponse(false, 'Modification impossible', 409);
                }
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur modification', 500);
            }
            $this->flash('error', 'Erreur modification');
        }

        $this->redirect('/profil');
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
                $this->jsonResponse(false, 'Le poste est obligatoire', 422);
            }
            $this->flash('error', 'Le poste est obligatoire');
            $this->redirect('/profil');
        }

        if ($entreprise !== '' && ($this->textLength($entreprise) > 120 || $this->hasControlChars($entreprise))) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Entreprise invalide', 422);
            }
            $this->flash('error', 'Entreprise invalide');
            $this->redirect('/profil');
        }

        if ($description !== '' && ($this->textLength($description) > 1000 || $this->hasControlChars($description))) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Description invalide', 422);
            }
            $this->flash('error', 'Description invalide');
            $this->redirect('/profil');
        }

        if (!$this->isValidDate($date_debut)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'La date de debut est obligatoire', 422);
            }
            $this->flash('error', 'Date de debut invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && !$this->isValidDate($date_fin)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Date de fin invalide', 422);
            }
            $this->flash('error', 'Date de fin invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && strtotime($date_fin) < strtotime($date_debut)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'La date de fin doit etre apres la date de debut', 422);
            }
            $this->flash('error', 'La date de fin doit etre apres la date de debut');
            $this->redirect('/profil');
        }

        try {
            $newId = $this->model->addExperience($user_id, $poste, $entreprise, $date_debut, $date_fin, $description);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->jsonResponse(true, 'Experience ajoutee', 200, [
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
                $this->jsonResponse(false, 'Erreur lors de l\'ajout', 500);
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
                $this->jsonResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteExperience($id, $user_id)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Experience supprimee');
                }
                $this->flash('success', 'Experience supprimee');
            } else {
                if ($isAjax) {
                    $this->jsonResponse(false, 'Suppression impossible', 404);
                }
                $this->flash('error', 'Suppression impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur suppression', 500);
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
                $this->jsonResponse(false, 'Champs invalides', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        if ($entreprise !== '' && ($this->textLength($entreprise) > 120 || $this->hasControlChars($entreprise))) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Entreprise invalide', 422);
            }
            $this->flash('error', 'Entreprise invalide');
            $this->redirect('/profil');
        }

        if ($description !== '' && ($this->textLength($description) > 1000 || $this->hasControlChars($description))) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Description invalide', 422);
            }
            $this->flash('error', 'Description invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && !$this->isValidDate($date_fin)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Date de fin invalide', 422);
            }
            $this->flash('error', 'Date de fin invalide');
            $this->redirect('/profil');
        }

        if ($date_fin !== '' && strtotime($date_fin) < strtotime($date_debut)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'La date de fin doit etre apres la date de debut', 422);
            }
            $this->flash('error', 'La date de fin doit etre apres la date de debut');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateExperience($id, $user_id, $poste, $entreprise, $date_debut, $date_fin, $description)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Experience modifiee', 200, [
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
                    $this->jsonResponse(false, 'Modification impossible', 409);
                }
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur modification', 500);
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
                $this->jsonResponse(false, 'Nom de certification invalide.', 422);
            }
            $this->flash('error', 'Nom de certification invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === null || $niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Niveau invalide.');
            $this->redirect('/profil');
        }

        try {
            $this->model->addCertification($user_id, $nom, $niveau);
            $this->refreshInsightCache($user_id);
            if ($isAjax) {
                $this->jsonResponse(true, 'Certification ajoutee');
            }
            $this->flash('success', 'Certification ajoutee');
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur lors de l\'ajout', 500);
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
                $this->jsonResponse(false, 'ID invalide', 422);
            }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        if (!$this->isValidSkillName($nom, 2, 100)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Nom de certification invalide.', 422);
            }
            $this->flash('error', 'Nom de certification invalide.');
            $this->redirect('/profil');
        }

        if ($niveau === false || $niveau < 0 || $niveau > 100) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Niveau invalide.', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateCertification($id, $user_id, $nom, $niveau)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Certification modifiee', 200, [
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
                    $this->jsonResponse(false, 'Modification impossible', 409);
                }
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur modification', 500);
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
                $this->jsonResponse(false, 'Methode non autorisee', 405);
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
                $this->jsonResponse(false, 'Nom, prenom ou email invalide.', 422);
            }
            $this->flash('error', 'Nom, prenom ou email invalide.');
            $this->redirect('/profil');
        }

        if (!in_array($disponibilite, ['disponible', 'occupe', 'indisponible'], true)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Disponibilite invalide.', 422);
            }
            $this->flash('error', 'Disponibilite invalide.');
            $this->redirect('/profil');
        }

        if (!$this->isValidPhone($telephone)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Telephone invalide.', 422);
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
                $this->jsonResponse(false, 'Champs texte invalides.', 422);
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
                $this->jsonResponse(false, 'Donnees de disponibilite trop volumineuses.', 422);
            }
            $this->flash('error', 'Donnees de disponibilite trop volumineuses.');
            $this->redirect('/profil');
        }

        $invalidSlotsJson = json_decode($disponibilite_slots, true) === null && $disponibilite_slots !== 'null' && $disponibilite_slots !== '[]';
        $invalidExceptionsJson = json_decode($disponibilite_exceptions, true) === null && $disponibilite_exceptions !== 'null' && $disponibilite_exceptions !== '[]';
        $invalidCongesJson = json_decode($disponibilite_conges, true) === null && $disponibilite_conges !== 'null' && $disponibilite_conges !== '[]';

        if ($invalidSlotsJson || $invalidExceptionsJson || $invalidCongesJson) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Format des donnees de disponibilite invalide.', 422);
            }
            $this->flash('error', 'Format des donnees de disponibilite invalide.');
            $this->redirect('/profil');
        }

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
                $this->jsonResponse(true, 'Profil mis a jour !', 200, [
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
                $this->jsonResponse(false, 'Erreur lors de la mise a jour du profil.', 500);
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
                $this->jsonResponse(false, 'Methode non autorisee', 405);
            }
            $this->redirect('/profil');
        }

        $bio = $this->normalizeText($_POST['bio'] ?? '', 2000);

        if (!$this->isWithinLength($bio, 2, 2000) || $this->hasControlChars($bio)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Bio invalide.', 422);
            }
            $this->flash('error', 'Bio invalide.');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->addBioForUser($user_id, $bio)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Bio ajoutee.', 200, ['bio' => $bio]);
                }
                $this->flash('success', 'Bio ajoutee.');
            } else {
                if ($isAjax) {
                    $this->jsonResponse(false, 'Ajout impossible. Verifiez qu\'une competence existe deja.', 409);
                }
                $this->flash('error', 'Ajout impossible. Verifiez qu\'une competence existe deja.');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur lors de l\'ajout de la bio.', 500);
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
                $this->jsonResponse(false, 'Methode non autorisee', 405);
            }
            $this->redirect('/profil');
        }

        $bio = $this->normalizeText($_POST['bio'] ?? '', 2000);

        if (!$this->isWithinLength($bio, 2, 2000) || $this->hasControlChars($bio)) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Bio invalide.', 422);
            }
            $this->flash('error', 'Bio invalide.');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateBioForUser($user_id, $bio)) {
                $this->refreshInsightCache($user_id);
                if ($isAjax) {
                    $this->jsonResponse(true, 'Bio modifiee.', 200, ['bio' => $bio]);
                }
                $this->flash('success', 'Bio modifiee.');
            } else {
                if ($isAjax) {
                    $this->jsonResponse(false, 'Modification impossible. Ajoutez d\'abord une bio.', 409);
                }
                $this->flash('error', 'Modification impossible. Ajoutez d\'abord une bio.');
            }
        } catch (Exception $e) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Erreur lors de la modification de la bio.', 500);
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
            if ($isAjax) { $this->jsonResponse(false, 'Méthode non autorisée', 405); }
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
            if ($isAjax) { $this->jsonResponse(false, 'Titre du métier invalide.', 422); }
            $this->flash('error', 'Titre du métier invalide.');
            $this->redirect('/profil');
        }

        try {
            $idMetier = $this->model->upsertMetierAvance(
                $userId, $slot, $titre, $description, $niveauMaitrise, $technologies
            );

            // Lancer l'analyse IA immédiatement
            $competences    = $this->model->getCompetences($userId);
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
                $this->jsonResponse(true, 'Métier avancé sauvegardé.', 200, [
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
            if ($isAjax) { $this->jsonResponse(false, 'Erreur lors de la sauvegarde du métier.', 500); }
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
            if ($isAjax) { $this->jsonResponse(false, 'ID invalide', 422); }
            $this->flash('error', 'ID invalide');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->deleteMetierAvance($idMetier, $userId)) {
                if ($isAjax) { $this->jsonResponse(true, 'Métier supprimé.'); }
                $this->flash('success', 'Métier avancé supprimé.');
            } else {
                if ($isAjax) { $this->jsonResponse(false, 'Suppression impossible.', 404); }
                $this->flash('error', 'Suppression impossible.');
            }
        } catch (Exception $e) {
            if ($isAjax) { $this->jsonResponse(false, 'Erreur suppression.', 500); }
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
            if ($isAjax) { $this->jsonResponse(false, 'ID invalide', 422); }
            $this->redirect('/profil');
        }

        $metier = $this->model->getMetierById($idMetier, $userId);
        if (!$metier) {
            if ($isAjax) { $this->jsonResponse(false, 'Métier introuvable.', 404); }
            $this->redirect('/profil');
        }

        $competences    = $this->model->getCompetences($userId);
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
            $this->jsonResponse(true, 'Analyse IA complétée.', 200, ['ia' => $ia]);
        }
        $this->flash('success', 'Analyse IA relancée.');
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

        $realisationDefault = trim((string)($_POST['realisation_default'] ?? ''));
        $realisationCustom = $this->normalizeText($_POST['realisation_custom'] ?? '', 120);

        if ($realisationDefault === '') {
            $this->flash('error', 'Veuillez choisir une realisation.');
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

        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/portfolio';
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
