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

    public function index()
    {
        $user_id = $this->requireAuth();

        $user = $this->model->getUserById($user_id);
        if (!$user) {
            $this->flash('error', 'Profil introuvable.');
            $this->redirect('/dashboard');
        }

        $stats = $this->model->getStats($user_id);
        $competences = $this->model->getCompetences($user_id);
        $certifications = $this->model->getCertifications($user_id);
        $experiences = $this->model->getExperiences($user_id);
        $portfolioFiles = $this->model->getPortfolioFiles($user_id);
        $avis = $this->model->getAvis($user_id);

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

    public function gestion_competences()
    {
        $user_id = $this->requireAuth();
        $competences = $this->model->getCompetences($user_id);

        require_once 'views/profil/gestion_competences.php';
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

        $nom = $this->normalizeText($_POST['nom'] ?? '', 80);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (
            !$this->isWithinLength($nom, 2, 80) ||
            $this->hasControlChars($nom) ||
            $this->hasControlChars($description) ||
            $niveau === false || $niveau < 0 || $niveau > 100
        ) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Champs invalides', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            $this->model->addCompetence($user_id, $nom, $description, $niveau);
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

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 80);
        $description = $this->normalizeText($_POST['description'] ?? '', 500);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (
            !$id ||
            !$this->isWithinLength($nom, 2, 80) ||
            $this->hasControlChars($nom) ||
            $this->hasControlChars($description) ||
            $niveau === false || $niveau < 0 || $niveau > 100
        ) {
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateCompetence($id, $user_id, $nom, $description, $niveau)) {
                $this->flash('success', 'Competence modifiee');
            } else {
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
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

        if (
            !$this->isWithinLength($nom, 2, 100) ||
            $this->hasControlChars($nom) ||
            $niveau === null || $niveau === false || $niveau < 0 || $niveau > 100
        ) {
            if ($isAjax) {
                $this->jsonResponse(false, 'Champs invalides', 422);
            }
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            $this->model->addCertification($user_id, $nom, $niveau);
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

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nom = $this->normalizeText($_POST['nom'] ?? '', 100);
        $niveau = filter_input(INPUT_POST, 'niveau', FILTER_VALIDATE_INT);

        if (
            !$id ||
            !$this->isWithinLength($nom, 2, 100) ||
            $this->hasControlChars($nom) ||
            $niveau === false || $niveau < 0 || $niveau > 100
        ) {
            $this->flash('error', 'Champs invalides');
            $this->redirect('/profil');
        }

        try {
            if ($this->model->updateCertification($id, $user_id, $nom, $niveau)) {
                $this->flash('success', 'Certification modifiee');
            } else {
                $this->flash('error', 'Modification impossible');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur modification');
        }

        $this->redirect('/profil');
    }

    public function update()
    {
        $user_id = $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
            $this->flash('error', 'Nom, prenom ou email invalide.');
            $this->redirect('/profil');
        }

        if (!in_array($disponibilite, ['disponible', 'occupe', 'indisponible'], true)) {
            $this->flash('error', 'Disponibilite invalide.');
            $this->redirect('/profil');
        }

        if (!$this->isValidPhone($telephone)) {
            $this->flash('error', 'Telephone invalide.');
            $this->redirect('/profil');
        }

        if (
            $this->hasControlChars($specialite) ||
            $this->hasControlChars($ville) ||
            $this->hasControlChars($disponibilite_horaire) ||
            $this->hasControlChars($disponibilite_message)
        ) {
            $this->flash('error', 'Champs texte invalides.');
            $this->redirect('/profil');
        }

        if (
            $this->textLength($disponibilite_slots) > 5000 ||
            $this->textLength($disponibilite_exceptions) > 5000 ||
            $this->textLength($disponibilite_conges) > 5000
        ) {
            $this->flash('error', 'Donnees de disponibilite trop volumineuses.');
            $this->redirect('/profil');
        }

        $invalidSlotsJson = json_decode($disponibilite_slots, true) === null && $disponibilite_slots !== 'null' && $disponibilite_slots !== '[]';
        $invalidExceptionsJson = json_decode($disponibilite_exceptions, true) === null && $disponibilite_exceptions !== 'null' && $disponibilite_exceptions !== '[]';
        $invalidCongesJson = json_decode($disponibilite_conges, true) === null && $disponibilite_conges !== 'null' && $disponibilite_conges !== '[]';

        if ($invalidSlotsJson || $invalidExceptionsJson || $invalidCongesJson) {
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
            $this->redirect('/profil?success=1');
        } catch (Exception $e) {
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
                $this->flash('success', 'Bio supprimee.');
            } else {
                $this->flash('error', 'Suppression impossible.');
            }
        } catch (Exception $e) {
            $this->flash('error', 'Erreur lors de la suppression de la bio.');
        }

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

            $this->flash('success', 'Document ajoute.');
        } catch (Exception $e) {
            $this->flash('error', 'Erreur lors de l\'enregistrement.');
        }

        $this->redirect('/profil');
    }
}
