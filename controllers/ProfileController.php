<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../Authentification intelligente RF/FaceRecognitionService.php';

class ProfileController extends Controller {
    private UserModel $userModel;
    private FaceRecognitionService $faceRecognition;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->requireAuth();
        $this->userModel = new UserModel();
        $this->faceRecognition = new FaceRecognitionService();
    }

    public function index(): void {
        $user = $this->getCurrentUserOrRedirect();
        $this->render('auth/profile', ['user' => $user]);
    }

    public function edit(): void {
        $user = $this->getCurrentUserOrRedirect();
        $errors = [];

        if ($this->isPost()) {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $dateNaissance = trim($_POST['date_naissance'] ?? '');
            $statutMarital = trim($_POST['statut_marital'] ?? '');
            $faceDescriptor = trim($_POST['face_descriptor'] ?? '');

            $validator = new Validator();
            $validator
                ->required('nom', $nom, 'Nom')
                ->alpha('nom', $nom, 'Nom')
                ->maxLength('nom', $nom, 100, 'Nom')
                ->required('prenom', $prenom, 'Prenom')
                ->alpha('prenom', $prenom, 'Prenom')
                ->maxLength('prenom', $prenom, 100, 'Prenom');

            if ($dateNaissance !== '') {
                $validator->date('date_naissance', $dateNaissance, 'Date de naissance');
            }

            if ($statutMarital !== '') {
                $validator->inList(
                    'statut_marital',
                    $statutMarital,
                    ['celibataire', 'marie', 'divorce', 'veuf', 'autre'],
                    'Statut marital'
                );
            }

            $newFaceId = null;
            if ($faceDescriptor !== '') {
                if ($this->faceRecognition->normalizeDescriptor($faceDescriptor) === null) {
                    $errors['face_descriptor'] = 'Capture faciale invalide. Veuillez reessayer.';
                } else {
                    $newFaceId = $this->faceRecognition->generateFaceId($faceDescriptor);
                    if ($newFaceId === null) {
                        $errors['face_descriptor'] = 'Impossible de generer le nouvel identifiant du visage.';
                    }
                }
            }

            $imagePath = $user['image_profil'] ?? null;
            $uploadError = $this->handleProfileImageUpload($user);
            if (isset($uploadError['error'])) {
                $errors['image_profil'] = $uploadError['error'];
            } elseif (isset($uploadError['path'])) {
                $imagePath = $uploadError['path'];
            }

            if (!$validator->isValid()) {
                $errors = array_merge($errors, $validator->getErrors());
            }

            if (empty($errors)) {
                $ok = $this->userModel->updateProfile((int) $user['id_user'], [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'date_naissance' => $dateNaissance !== '' ? $dateNaissance : null,
                    'statut_marital' => $statutMarital !== '' ? $statutMarital : null,
                    'image_profil' => $imagePath,
                ]);

                if ($ok && $faceDescriptor !== '' && $newFaceId !== null) {
                    $ok = $this->userModel->updateFaceData((int) $user['id_user'], $faceDescriptor, $newFaceId);
                }

                if ($ok) {
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Profil mis a jour avec succes.'];
                    $this->redirect('index.php?page=profile');
                }

                $errors['general'] = 'Erreur lors de la mise a jour du profil.';
            }

            $user = array_merge($user, [
                'nom' => $nom,
                'prenom' => $prenom,
                'date_naissance' => $dateNaissance,
                'statut_marital' => $statutMarital,
                'image_profil' => $imagePath,
                'face_descriptor' => $faceDescriptor !== '' ? $faceDescriptor : ($user['face_descriptor'] ?? null),
                'face_id' => $newFaceId ?? ($user['face_id'] ?? null),
            ]);
        }

        $this->render('auth/edit_profile', [
            'user' => $user,
            'errors' => $errors,
        ]);
    }

    public function deleteForm(): void {
        $user = $this->getCurrentUserOrRedirect();
        $this->render('auth/delete_account', ['user' => $user]);
    }

    public function deleteAccount(): void {
        if (!$this->isPost()) {
            $this->redirect('index.php?page=profile');
        }

        $password = $_POST['password'] ?? '';
        if ($password === '') {
            $this->json(['success' => false, 'message' => 'Mot de passe requis.'], 422);
        }

        $user = $this->getCurrentUserOrRedirect();
        if (!$this->userModel->verifyPassword($password, $user['mot_de_passe'])) {
            $this->json(['success' => false, 'message' => 'Mot de passe incorrect.'], 422);
        }

        $this->deleteProfileImageFile($user['image_profil'] ?? null);

        if ($this->userModel->delete((int) $user['id_user'])) {
            session_destroy();
            $this->json([
                'success' => true,
                'message' => 'Compte supprime avec succes.',
                'redirect' => 'index.php?page=login',
            ]);
        }

        $this->json(['success' => false, 'message' => 'Suppression impossible.'], 500);
    }

    public function settings(): void {
        $user = $this->getCurrentUserOrRedirect();
        $this->render('auth/account_settings', ['user' => $user]);
    }

    private function getCurrentUserOrRedirect(): array {
        $user = $this->userModel->findById((int) $_SESSION['user_id']);
        if (!$user) {
            session_destroy();
            $this->redirect('index.php?page=login');
        }
        return $user;
    }

    private function handleProfileImageUpload(array $user): array {
        if (!isset($_FILES['image_profil']) || ($_FILES['image_profil']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }

        $file = $_FILES['image_profil'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Image de profil invalide.'];
        }

        if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['error' => 'L image doit faire moins de 2 Mo.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
            return ['error' => 'Formats acceptes : JPG, PNG, WEBP.'];
        }

        $uploadDir = __DIR__ . '/../public/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            return ['error' => 'Impossible de creer le dossier des images.'];
        }

        $filename = 'profile_' . (int) $user['id_user'] . '_' . time() . '.' . $allowed[$mime];
        $target = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['error' => 'Impossible d enregistrer l image.'];
        }

        $this->deleteProfileImageFile($user['image_profil'] ?? null);

        return ['path' => 'public/uploads/profiles/' . $filename];
    }

    private function deleteProfileImageFile(?string $relativePath): void {
        if (!$relativePath) {
            return;
        }

        $fullPath = __DIR__ . '/../' . ltrim(str_replace('\\', '/', $relativePath), '/');
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
