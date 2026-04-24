<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/PendingUserModel.php';
require_once __DIR__ . '/../models/PasswordResetModel.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Contrôleur AuthController — Gestion de l'authentification
 * Responsabilités : connexion, inscription, vérification email, reset mdp
 */
class AuthController extends Controller {

    private UserModel $userModel;
    private PendingUserModel $pendingModel;
    private PasswordResetModel $resetModel;

    public function __construct() {
        $this->userModel    = new UserModel();
        $this->pendingModel = new PendingUserModel();
        $this->resetModel   = new PasswordResetModel();
    }

    // ── CONNEXION ────────────────────────────────────────────────────────────

    /**
     * Action : afficher la page de connexion (GET) ou traiter le login (POST JSON)
     */
    public function login(): void {
        if ($this->isPost()) {
            header('Content-Type: application/json');
            $email    = trim($_POST['email']    ?? '');
            $password =      $_POST['password'] ?? '';
            $role     = trim($_POST['role']     ?? '');

            // Validation serveur (sans HTML5)
            $validator = new Validator();
            $validator
                ->required('email',    $email,    'Email')
                ->email('email',       $email,    'Email')
                ->required('password', $password, 'Mot de passe')
                ->required('role',     $role,     'Rôle')
                ->inList('role',       $role,     ROLES, 'Rôle');

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
                $firstField = array_key_first($errors);
                $this->json([
                    'success' => false,
                    'field'   => $firstField,
                    'message' => $errors[$firstField],
                ]);
            }

            $user = $this->userModel->findByEmail($email);

            if (!$user) {
                $this->json(['success' => false, 'field' => 'email',
                    'message' => 'Aucun compte trouvé avec cette adresse e-mail.']);
            }

            if (!$this->userModel->verifyPassword($password, $user['mot_de_passe'])) {
                $this->json(['success' => false, 'field' => 'password',
                    'message' => 'Mot de passe incorrect.']);
            }

            if ($user['role'] !== $role) {
                $this->json(['success' => false, 'field' => 'role',
                    'message' => 'Rôle incorrect. Ce compte est enregistré en tant que « ' . ucfirst($user['role']) . ' ».']);
            }

            if ($user['etat_compte'] !== 'actif') {
                $this->json(['success' => false, 'field' => 'general',
                    'message' => 'Votre compte est inactif. Contactez l\'administrateur.']);
            }

            // Connexion réussie — on stocke la session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['email']   = $user['email'];

            $this->json([
                'success' => true,
                'nom'     => $user['nom'],
                'prenom'  => $user['prenom'],
                'role'    => $user['role'],
            ]);
        }

        // GET : afficher le formulaire
        $this->render('auth/login');
    }

    // ── DÉCONNEXION ──────────────────────────────────────────────────────────

    public function logout(): void {
        session_destroy();
        $this->redirect('../index.php');
    }

    // ── INSCRIPTION ──────────────────────────────────────────────────────────

    /**
     * Action : afficher le formulaire d'inscription (GET) ou traiter l'inscription (POST JSON)
     */
    public function register(): void {
        if ($this->isPost()) {
            header('Content-Type: application/json');

            $nom      = trim($_POST['nom']      ?? '');
            $prenom   = trim($_POST['prenom']   ?? '');
            $email    = trim($_POST['email']    ?? '');
            $password =      $_POST['password'] ?? '';
            $confirm  =      $_POST['confirm']  ?? '';
            $role     = trim($_POST['role']     ?? '');

            // Validation serveur complète
            $validator = new Validator();
            $validator
                ->required('nom',     $nom,     'Nom')
                ->alpha('nom',        $nom,     'Nom')
                ->maxLength('nom',    $nom,     100, 'Nom')
                ->required('prenom',  $prenom,  'Prénom')
                ->alpha('prenom',     $prenom,  'Prénom')
                ->maxLength('prenom', $prenom,  100, 'Prénom')
                ->required('email',   $email,   'Email')
                ->email('email',      $email,   'Email')
                ->required('password',$password,'Mot de passe')
                ->minLength('password',$password, 6, 'Mot de passe')
                ->matches('confirm',  $password, $confirm, 'Confirmation du mot de passe')
                ->required('role',    $role,    'Rôle')
                ->inList('role',      $role,    ROLES, 'Rôle');

            if (!$validator->isValid()) {
                $errors = $validator->getErrors();
                $firstField = array_key_first($errors);
                $this->json([
                    'success' => false,
                    'field'   => $firstField,
                    'message' => $errors[$firstField],
                ]);
            }

            if ($this->userModel->emailExists($email)) {
                $this->json(['success' => false, 'field' => 'email',
                    'message' => 'Cette adresse e-mail est déjà utilisée.']);
            }

            try {
                $token     = bin2hex(random_bytes(32));
                $hash      = password_hash($password, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

                $this->pendingModel->upsert([
                    'nom'          => $nom,
                    'prenom'       => $prenom,
                    'email'        => $email,
                    'mot_de_passe' => $hash,
                    'role'         => $role,
                    'token'        => $token,
                    'expires_at'   => $expiresAt,
                ]);

                $this->sendVerificationEmail($email, "$prenom $nom", $token);

                $this->json([
                    'success' => true,
                    'message' => "Un e-mail de vérification a été envoyé à $email. Cliquez sur « Autoriser » dans le mail.",
                ]);
            } catch (Throwable $e) {
                $this->json(['success' => false, 'field' => 'general',
                    'message' => 'Erreur lors de l\'envoi du mail : ' . $e->getMessage()]);
            }
        }

        $this->render('auth/register');
    }

    // ── VÉRIFICATION EMAIL ───────────────────────────────────────────────────

    public function verify(): void {
        $token   = trim($_GET['token'] ?? '');
        $success = false;
        $message = '';

        if (!$token) {
            $message = 'Lien invalide.';
        } else {
            $pending = $this->pendingModel->findByToken($token);

            if (!$pending) {
                $message = 'Lien introuvable ou déjà utilisé.';
            } elseif (strtotime($pending['expires_at']) < time()) {
                $message = 'Ce lien a expiré. Veuillez vous réinscrire.';
            } elseif ($this->userModel->emailExists($pending['email'])) {
                $message = 'Cet utilisateur existe déjà.';
            } else {
                $ok = $this->userModel->create([
                    'nom'      => $pending['nom'],
                    'prenom'   => $pending['prenom'],
                    'email'    => $pending['email'],
                    'password' => '', // déjà hashé
                    'role'     => $pending['role'],
                ]);

                // Insérer avec le hash déjà existant
                $stmt = Database::getInstance()->getConnection()->prepare(
                    "INSERT INTO user (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte)
                     VALUES (:nom, :prenom, :email, :mdp, :role, :date, 'actif')"
                );
                $stmt->execute([
                    ':nom'    => $pending['nom'],
                    ':prenom' => $pending['prenom'],
                    ':email'  => $pending['email'],
                    ':mdp'    => $pending['mot_de_passe'],
                    ':role'   => $pending['role'],
                    ':date'   => date('Y-m-d'),
                ]);

                $this->pendingModel->deleteById($pending['id']);
                $success = true;
                $message = 'Compte vérifié avec succès !';
            }
        }

        $this->render('auth/verify', ['success' => $success, 'message' => $message]);
    }

    // ── RESET MOT DE PASSE ───────────────────────────────────────────────────

    public function forgotPassword(): void {
        if (!isset($_SESSION)) session_start();

        $step       = $_SESSION['reset_step'] ?? 'email';
        $emailValue = $_SESSION['reset_email'] ?? '';
        $success    = '';
        $error      = '';

        if (isset($_GET['restart'])) {
            unset($_SESSION['reset_step'], $_SESSION['reset_email'], $_SESSION['reset_verified']);
            $this->redirect('index.php?page=forgot_password');
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
                        $error = 'Aucun compte trouvé avec cette adresse e-mail.';
                    } else {
                        $code      = (string) random_int(100000, 999999);
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $this->resetModel->upsert($email, $code, $expiresAt);

                        try {
                            $this->sendResetCode($email, "{$user['prenom']} {$user['nom']}", $code);
                            $_SESSION['reset_step']  = 'code';
                            $_SESSION['reset_email'] = $email;
                            $step       = 'code';
                            $emailValue = $email;
                            $success    = 'Un code de vérification a été envoyé à votre adresse e-mail.';
                        } catch (Throwable $e) {
                            $error = 'Erreur SMTP. Vérifiez la configuration Gmail.';
                        }
                    }
                }
            }

            if ($action === 'verify_code') {
                $email = $_SESSION['reset_email'] ?? '';
                $code  = trim($_POST['code'] ?? '');
                $step  = 'code';

                $v = new Validator();
                $v->required('code', $code, 'Code');

                if (!$v->isValid()) {
                    $error = 'Veuillez entrer le code reçu par e-mail.';
                } else {
                    $reset = $this->resetModel->findByEmail($email);
                    if (!$reset) {
                        $error = 'Aucune demande trouvée.';
                    } elseif (strtotime($reset['expires_at']) < time()) {
                        $error = 'Code expiré. Recommencez.';
                        unset($_SESSION['reset_step'], $_SESSION['reset_email']);
                        $step = 'email';
                    } elseif ($reset['code'] !== $code) {
                        $error = 'Code incorrect.';
                    } else {
                        $this->resetModel->markVerified($email);
                        $_SESSION['reset_step']     = 'password';
                        $_SESSION['reset_verified']  = true;
                        $step    = 'password';
                        $success = 'Code correct. Choisissez votre nouveau mot de passe.';
                    }
                }
                $emailValue = $email;
            }

            if ($action === 'change_password') {
                $email   = $_SESSION['reset_email'] ?? '';
                $newPw   = $_POST['new_password']     ?? '';
                $confPw  = $_POST['confirm_password'] ?? '';
                $step    = 'password';

                $v = new Validator();
                $v->required('new_password', $newPw, 'Nouveau mot de passe')
                  ->minLength('new_password', $newPw, 6, 'Nouveau mot de passe')
                  ->matches('confirm_password', $newPw, $confPw, 'Confirmation');

                if (!$v->isValid()) {
                    $errors = $v->getErrors();
                    $error  = array_values($errors)[0];
                } elseif (empty($_SESSION['reset_verified'])) {
                    $error = 'Session expirée. Recommencez.';
                    $step  = 'email';
                } else {
                    $reset = $this->resetModel->findByEmail($email);
                    if (!$reset || !$reset['verified'] || strtotime($reset['expires_at']) < time()) {
                        $error = 'Vérification invalide ou expirée.';
                        $step  = 'email';
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
            'step'       => $step,
            'emailValue' => $emailValue,
            'success'    => $success,
            'error'      => $error,
        ]);
    }

    // ── HELPERS PRIVÉS ───────────────────────────────────────────────────────

    private function sendVerificationEmail(string $to, string $name, string $token): void {
        $link = APP_URL . '/index.php?page=verify&token=' . urlencode($token);
        $this->sendMail($to, $name, 'Vérification de votre compte - ' . APP_NAME,
            "<h2>" . APP_NAME . "</h2>
             <p>Bonjour $name,</p>
             <p>Cliquez pour activer votre compte :</p>
             <p><a href='$link' style='display:inline-block;padding:12px 20px;background:#2E6B3E;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;'>Autoriser</a></p>
             <p>Ce lien expire dans 24 heures.</p>"
        );
    }

    private function sendResetCode(string $to, string $name, string $code): void {
        $this->sendMail($to, $name, 'Code de réinitialisation - ' . APP_NAME,
            "<h2>" . APP_NAME . "</h2>
             <p>Bonjour $name,</p>
             <p>Votre code de réinitialisation :</p>
             <div style='font-size:32px;font-weight:bold;letter-spacing:8px;background:#F5ECD7;padding:14px 18px;border-radius:10px;display:inline-block;color:#2E6B3E;'>$code</div>
             <p style='margin-top:20px;'>Ce code expire dans <strong>15 minutes</strong>.</p>"
        );
    }

    private function sendMail(string $to, string $name, string $subject, string $body): void {
        $phpMailerFiles = [
            __DIR__ . '/../src/Exception.php',
            __DIR__ . '/../src/PHPMailer.php',
            __DIR__ . '/../src/SMTP.php',
        ];
        foreach ($phpMailerFiles as $f) {
            if (!file_exists($f)) throw new \RuntimeException('PHPMailer introuvable.');
        }
        require_once __DIR__ . '/../src/Exception.php';
        require_once __DIR__ . '/../src/PHPMailer.php';
        require_once __DIR__ . '/../src/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
    }
}
