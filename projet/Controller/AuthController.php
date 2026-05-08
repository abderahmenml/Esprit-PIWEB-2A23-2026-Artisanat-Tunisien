<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/SupabaseSync.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../Model/UserModel.php';
require_once __DIR__ . '/../Model/PendingUserModel.php';
require_once __DIR__ . '/../Model/PasswordResetModel.php';
require_once __DIR__ . '/../security/LoginSecurity.php';
require_once __DIR__ . '/../Authentification intelligente RF/FaceRecognitionService.php';
require_once __DIR__ . '/../reCAPTCHA v2/RecaptchaV2.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class AuthController extends Controller {
    private UserModel $userModel;
    private PendingUserModel $pendingModel;
    private PasswordResetModel $resetModel;
    private LoginSecurity $loginSecurity;
    private FaceRecognitionService $faceRecognition;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->pendingModel = new PendingUserModel();
        $this->resetModel = new PasswordResetModel();
        $this->loginSecurity = new LoginSecurity();
        $this->faceRecognition = new FaceRecognitionService();
    }

    public function login(): void {
        if ($this->isPost()) {
            header('Content-Type: application/json');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = trim($_POST['role'] ?? '');

            $validator = new Validator();
            $validator
                ->required('email', $email, 'Email')
                ->email('email', $email, 'Email')
                ->required('password', $password, 'Mot de passe')
                ->required('role', $role, 'Role')
                ->inList('role', $role, ROLES, 'Role');

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
                $firstField = array_key_first($errors);
                $this->json([
                    'success' => false,
                    'field' => $firstField,
                    'message' => $errors[$firstField],
                ]);
            }

            if (!RecaptchaV2::verify($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? null)) {
                $this->json([
                    'success' => false,
                    'field' => 'recaptcha',
                    'message' => 'Veuillez confirmer que vous n etes pas un robot.',
                ]);
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $this->json([
                    'success' => false,
                    'field' => 'email',
                    'message' => 'Aucun compte trouve avec cette adresse e-mail.',
                ]);
            }

            $lockState = $this->userModel->getLoginLockState($user);
            if ($lockState['locked']) {
                if ($lockState['permanent']) {
                    $this->json([
                        'success' => false,
                        'field' => 'general',
                        'message' => 'Votre compte est bloque. Veuillez contacter l administrateur.',
                    ]);
                }

                $this->json([
                    'success' => false,
                    'field' => 'general',
                    'message' => 'Votre compte est temporairement bloque. Reessayez dans ' . $this->loginSecurity->formatSeconds($lockState['remaining_seconds']) . '.',
                ]);
            }

            if (!$this->userModel->verifyPassword($password, $user['mot_de_passe'])) {
                $attempt = $this->userModel->registerFailedPasswordAttempt((int) $user['id_user'], $this->loginSecurity);

                if ($attempt['permanent']) {
                    $this->json([
                        'success' => false,
                        'field' => 'general',
                        'message' => 'Votre compte est bloque. Veuillez contacter l administrateur.',
                    ]);
                }

                if ($attempt['temporary_seconds'] > 0) {
                    $this->json([
                        'success' => false,
                        'field' => 'general',
                        'message' => 'Mot de passe incorrect. Votre compte est bloque pendant ' . $this->loginSecurity->formatSeconds($attempt['temporary_seconds']) . '.',
                    ]);
                }

                $this->json([
                    'success' => false,
                    'field' => 'password',
                    'message' => 'Mot de passe incorrect.',
                ]);
            }

            $adminViaFrontRole = $user['role'] === 'admin'
                && in_array($role, ['entrepreneur', 'artisan', 'mentor', 'investisseur'], true)
                && strcasecmp($user['email'], 'admin@craftlink.tn') === 0;

            if ($user['role'] !== $role && !$adminViaFrontRole) {
                $this->json([
                    'success' => false,
                    'field' => 'role',
                    'message' => 'Role incorrect. Ce compte est enregistre en tant que ' . ucfirst($user['role']) . '.',
                ]);
            }

            if ($user['etat_compte'] !== 'actif') {
                $this->json([
                    'success' => false,
                    'field' => 'general',
                    'message' => 'Votre compte est inactif. Contactez l administrateur.',
                ]);
            }

            if ($this->userModel->isBlocked((int) $user['id_user'])) {
                $this->json([
                    'success' => false,
                    'field' => 'general',
                    'message' => 'Votre compte a ete bloque par un administrateur.',
                ]);
            }

            $this->userModel->resetLoginSecurity((int) $user['id_user']);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nom'] = $user['nom'];
            $_SESSION['prenom'] = $user['prenom'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            $payload = [
                'success' => true,
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'role' => $user['role'],
                'email' => $user['email'],
                'redirect' => $user['role'] === 'admin' ? '../../admin/index.php' : 'homepage.html',
            ];

            $expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
                || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

            if ($expectsJson) {
                $this->json($payload);
            }

            header('Location: ' . ($payload['redirect']));
            exit;
        }

        $this->render('auth/login');
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('login.php');
    }

    public function resetSuccess(): void {
        $this->render('auth/reset_success');
    }

    public function register(): void {
        if ($this->isPost()) {
            header('Content-Type: application/json');

            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            $role = trim($_POST['role'] ?? '');
            $faceDescriptor = trim($_POST['face_descriptor'] ?? '');
            $faceDescriptor = $faceDescriptor !== '' ? $faceDescriptor : null;

            $validator = new Validator();
            $validator
                ->required('nom', $nom, 'Nom')
                ->alpha('nom', $nom, 'Nom')
                ->maxLength('nom', $nom, 100, 'Nom')
                ->required('prenom', $prenom, 'Prenom')
                ->alpha('prenom', $prenom, 'Prenom')
                ->maxLength('prenom', $prenom, 100, 'Prenom')
                ->required('email', $email, 'Email')
                ->email('email', $email, 'Email')
                ->required('password', $password, 'Mot de passe')
                ->minLength('password', $password, 6, 'Mot de passe')
                ->matches('confirm', $password, $confirm, 'Confirmation du mot de passe')
                ->required('role', $role, 'Role')
                ->inList('role', $role, ROLES, 'Role');

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
                $firstField = array_key_first($errors);
                $this->json([
                    'success' => false,
                    'field' => $firstField,
                    'message' => $errors[$firstField],
                ]);
            }

            if (!RecaptchaV2::verify($_POST['g-recaptcha-response'] ?? '', $_SERVER['REMOTE_ADDR'] ?? null)) {
                $this->json([
                    'success' => false,
                    'field' => 'recaptcha',
                    'message' => 'Veuillez confirmer que vous n etes pas un robot.',
                ]);
            }

            if ($this->userModel->emailExists($email)) {
                $this->json(['success' => false, 'field' => 'email', 'message' => 'Cette adresse e-mail est deja utilisee.']);
            }

            try {
                $faceId = null;
                if ($faceDescriptor !== null) {
                    $faceId = $this->faceRecognition->generateFaceId($faceDescriptor);

                    if ($faceId === null) {
                        $this->json([
                            'success' => false,
                            'field' => 'general',
                            'message' => 'Impossible de generer l identifiant du visage.',
                        ]);
                    }
                }

                $created = $this->userModel->createDirect([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'etat_compte' => 'actif',
                ]);

                if (!$created) {
                    $this->json([
                        'success' => false,
                        'field' => 'general',
                        'message' => 'Impossible de creer le compte. Reessayez plus tard.',
                    ]);
                }

                if ($faceDescriptor !== null) {
                    $user = $this->userModel->findByEmail($email);
                    if ($user) {
                        $this->userModel->updateFaceData((int) $user['id_user'], $faceDescriptor, $faceId ?? '');
                    }
                }

                $this->json([
                    'success' => true,
                    'message' => 'Compte cree avec succes. Vous pouvez vous connecter.',
                    'face_id' => $faceId,
                ]);
            } catch (Throwable $e) {
                $this->json([
                    'success' => false,
                    'field' => 'general',
                    'message' => 'Erreur lors de la creation du compte : ' . $e->getMessage(),
                ]);
            }
        }

        $this->render('auth/register');
    }

    public function loginFace(): void {
        if (!$this->isPost()) {
            $this->redirect('login.php');
        }

        header('Content-Type: application/json');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $faceId = trim($_POST['face_id'] ?? '');
        $faceDescriptor = trim($_POST['face_descriptor'] ?? '');

        if ($this->faceRecognition->normalizeDescriptor($faceDescriptor) === null) {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Capture faciale invalide. Veuillez reessayer.',
            ]);
        }

        $user = null;
        if ($email !== '') {
            $validator = new Validator();
            $validator->email('email', $email, 'Email');

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
                $this->json([
                    'success' => false,
                    'field' => 'email',
                    'message' => $errors['email'],
                ]);
            }

            $user = $this->userModel->findByEmail($email);
        } elseif ($faceId !== '') {
            $user = $this->userModel->findByFaceId($faceId);
        }

        if (!$user && $email === '') {
            $capturedDescriptor = $this->faceRecognition->normalizeDescriptor($faceDescriptor);
            $usersWithFace = $this->userModel->findAllWithFaceDescriptor();
            $bestDistance = null;
            $bestUser = null;

            foreach ($usersWithFace as $candidate) {
                $storedFaceDescriptor = trim((string) ($candidate['face_descriptor'] ?? ''));
                $stored = $this->faceRecognition->normalizeDescriptor($storedFaceDescriptor);

                if ($stored === null || $capturedDescriptor === null) {
                    continue;
                }

                $distance = $this->faceRecognition->distance($stored, $capturedDescriptor);
                if ($bestDistance === null || $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestUser = $candidate;
                }
            }

            if ($bestUser) {
                $user = $bestUser;
            }
        }

        if (!$user) {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Visage non reconnu. Connexion refusee.',
            ]);
        }

        $lockState = $this->userModel->getLoginLockState($user);
        if ($lockState['locked']) {
            if ($lockState['permanent']) {
                $this->json([
                    'success' => false,
                    'field' => 'general',
                    'message' => 'Votre compte est bloque. Veuillez contacter l administrateur.',
                ]);
            }

            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Votre compte est temporairement bloque. Reessayez dans ' . $this->loginSecurity->formatSeconds($lockState['remaining_seconds']) . '.',
            ]);
        }

        if ($email !== '' && $role !== '' && in_array($role, ROLES, true)) {
            $adminViaFrontRole = $user['role'] === 'admin'
                && in_array($role, ['entrepreneur', 'artisan', 'mentor', 'investisseur'], true)
                && strcasecmp($user['email'], 'admin@craftlink.tn') === 0;

            if ($user['role'] !== $role && !$adminViaFrontRole) {
                $this->json([
                    'success' => false,
                    'field' => 'role',
                    'message' => 'Role incorrect. Ce compte est enregistre en tant que ' . ucfirst($user['role']) . '.',
                ]);
            }
        }

        if ($user['etat_compte'] !== 'actif') {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Votre compte est inactif. Contactez l administrateur.',
            ]);
        }

        if ($this->userModel->isBlocked((int) $user['id_user'])) {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Votre compte a ete bloque par un administrateur.',
            ]);
        }

        $storedFaceDescriptor = trim((string) ($user['face_descriptor'] ?? ''));
        if ($storedFaceDescriptor === '') {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Aucune donnee faciale n est enregistree pour ce compte.',
            ]);
        }

        if ($email !== '' && !$this->faceRecognition->isMatch($storedFaceDescriptor, $faceDescriptor, FACE_MATCH_THRESHOLD)) {
            $this->json([
                'success' => false,
                'field' => 'general',
                'message' => 'Visage non reconnu. Connexion refusee.',
            ]);
        }

        $this->userModel->resetLoginSecurity((int) $user['id_user']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nom'] = $user['nom'];
        $_SESSION['prenom'] = $user['prenom'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        $payload = [
            'success' => true,
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'role' => $user['role'],
            'email' => $user['email'],
            'face_id' => $user['face_id'] ?? null,
            'redirect' => $user['role'] === 'admin' ? '../../admin/index.php' : 'homepage.html',
        ];

        $expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if ($expectsJson) {
            $this->json($payload);
        }

        header('Location: ' . ($payload['redirect']));
        exit;
    }

    public function verify(): void {
        $token = trim($_GET['token'] ?? '');
        $success = false;
        $message = '';

        if (!$token) {
            $message = 'Lien invalide.';
        } else {
            $pending = $this->pendingModel->findByToken($token);

            if (!$pending) {
                $message = 'Lien introuvable ou deja utilise.';
            } elseif (strtotime($pending['expires_at']) < time()) {
                $message = 'Ce lien a expire. Veuillez vous reinscrire.';
            } elseif ($this->userModel->emailExists($pending['email'])) {
                $message = 'Cet utilisateur existe deja.';
            } else {
                $stmt = Database::getInstance()->getConnection()->prepare(
                    "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte, face_descriptor, face_id)
                     VALUES (:nom, :prenom, :email, :mdp, :role, :date, 'actif', :face_descriptor, :face_id)"
                );
                $stmt->execute([
                    ':nom' => $pending['nom'],
                    ':prenom' => $pending['prenom'],
                    ':email' => $pending['email'],
                    ':mdp' => $pending['mot_de_passe'],
                    ':role' => $pending['role'],
                    ':date' => date('Y-m-d'),
                    ':face_descriptor' => $pending['face_descriptor'] ?? null,
                    ':face_id' => $pending['face_id'] ?? null,
                ]);

                $this->pendingModel->deleteById($pending['id']);
                $success = true;
                $message = 'Compte verifie avec succes.';
            }
        }

        if ($success) {
            $this->redirect('login.php?success=verified');
        } else {
            $this->render('auth/verify', ['success' => $success, 'message' => $message]);
        }
    }

    public function forgotPassword(): void {
        if (!isset($_SESSION)) {
            session_start();
        }

        $step = $_SESSION['reset_step'] ?? 'email';
        $emailValue = $_SESSION['reset_email'] ?? '';
        $success = '';
        $error = '';

        if (isset($_GET['restart'])) {
            unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
            $this->redirect('forgot_password.php');
        }

        if ($this->isPost()) {
            $action = $_POST['action'] ?? '';

            if ($action === 'send_code') {
                $email = trim($_POST['email'] ?? '');
                $v = new Validator();
                $v->required('email', $email, 'Email')->email('email', $email, 'Email');

                if (!$v->isValid()) {
                    $error = $v->getError('email');
                } else {
                    $user = $this->userModel->findByEmail($email);
                    if (!$user) {
                        $error = 'Aucun compte trouve avec cette adresse e-mail.';
                    } else {
                        $code = (string) random_int(100000, 999999);
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $this->resetModel->upsert($email, $code, $expiresAt);

                        try {
                            $this->sendResetCode($email, "{$user['prenom']} {$user['nom']}", $code);
                            $_SESSION['reset_step'] = 'code';
                            $_SESSION['reset_email'] = $email;
                            $step = 'code';
                            $emailValue = $email;
                            $success = 'Un code de verification a ete envoye a votre adresse e-mail.';
                        } catch (Throwable) {
                            $error = 'Erreur SMTP. Verifiez la configuration Gmail.';
                        }
                    }
                }
            }

            if ($action === 'verify_code') {
                $email = $_SESSION['reset_email'] ?? '';
                $code = trim($_POST['code'] ?? '');
                $step = 'code';

                $v = new Validator();
                $v->required('code', $code, 'Code');

                if (!$v->isValid()) {
                    $error = 'Veuillez entrer le code recu par e-mail.';
                } else {
                    $reset = $this->resetModel->findByEmail($email);
                    if (!$reset) {
                        $error = 'Aucune demande trouvee.';
                    } elseif (strtotime($reset['expires_at']) < time()) {
                        $error = 'Code expire. Recommencez.';
                        unset($_SESSION['reset_step'], $_SESSION['reset_email']);
                        $step = 'email';
                    } elseif ($reset['code'] !== $code) {
                        $error = 'Code incorrect.';
                    } else {
                        $this->resetModel->markVerified($email);
                        $_SESSION['reset_step'] = 'password';
                        $_SESSION['reset_verified'] = true;
                        $step = 'password';
                        $success = 'Code correct. Choisissez votre nouveau mot de passe.';
                    }
                }
                $emailValue = $email;
            }

            if ($action === 'change_password') {
                $email = $_SESSION['reset_email'] ?? '';
                $newPw = $_POST['new_password'] ?? '';
                $confPw = $_POST['confirm_password'] ?? '';
                $step = 'password';

                $v = new Validator();
                $v->required('new_password', $newPw, 'Nouveau mot de passe')
                  ->minLength('new_password', $newPw, 6, 'Nouveau mot de passe')
                  ->matches('confirm_password', $newPw, $confPw, 'Confirmation');

                if (!$v->isValid()) {
                    $errors = $v->getErrors();
                    $error = array_values($errors)[0];
                } elseif (empty($_SESSION['reset_verified'])) {
                    $error = 'Session expiree. Recommencez.';
                    $step = 'email';
                } else {
                    $reset = $this->resetModel->findByEmail($email);
                    if (!$reset || !$reset['verified'] || strtotime($reset['expires_at']) < time()) {
                        $error = 'Verification invalide ou expiree.';
                        $step = 'email';
                    } else {
                        $this->userModel->updatePasswordByEmail($email, $newPw);
                        $this->resetModel->deleteByEmail($email);
                        unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
                        $this->redirect('reset_success.php');
                    }
                }
                $emailValue = $email;
            }
        }

        $this->render('auth/forgot_password', [
            'step' => $step,
            'emailValue' => $emailValue,
            'success' => $success,
            'error' => $error,
        ]);
    }

    private function sendVerificationEmail(string $to, string $name, string $token): void {
        $baseUrl = rtrim(APP_URL, '/');
        if (preg_match('/\.html?$/i', $baseUrl)) {
            $baseUrl = rtrim(dirname($baseUrl), '/');
        }
        $link = $baseUrl . '/verify.php?token=' . urlencode($token);
        $this->sendMail(
            $to,
            $name,
            'Verification de votre compte - ' . APP_NAME,
            "<h2>" . APP_NAME . "</h2><p>Bonjour $name,</p><p>Cliquez pour activer votre compte :</p><p><a href='$link' style='display:inline-block;padding:12px 20px;background:#2E6B3E;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;'>Autoriser</a></p><p>Ce lien expire dans 24 heures.</p>"
        );
    }

    private function sendResetCode(string $to, string $name, string $code): void {
        $this->sendMail(
            $to,
            $name,
            'Code de reinitialisation - ' . APP_NAME,
            "<h2>" . APP_NAME . "</h2><p>Bonjour $name,</p><p>Votre code de reinitialisation :</p><div style='font-size:32px;font-weight:bold;letter-spacing:8px;background:#F5ECD7;padding:14px 18px;border-radius:10px;display:inline-block;color:#2E6B3E;'>$code</div><p style='margin-top:20px;'>Ce code expire dans <strong>15 minutes</strong>.</p>"
        );
    }

    private function sendMail(string $to, string $name, string $subject, string $body): void {
        $phpMailerFiles = [
            __DIR__ . '/../src/Exception.php',
            __DIR__ . '/../src/PHPMailer.php',
            __DIR__ . '/../src/SMTP.php',
        ];
        foreach ($phpMailerFiles as $f) {
            if (!file_exists($f)) {
                throw new RuntimeException('PHPMailer introuvable.');
            }
        }

        require_once __DIR__ . '/../src/Exception.php';
        require_once __DIR__ . '/../src/PHPMailer.php';
        require_once __DIR__ . '/../src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
    }
}
