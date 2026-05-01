<?php
// controllers/AuthController.php

require_once dirname(__DIR__, 3) . '/config/Config.php';

class AuthController
{
    private function redirect(string $path): void
    {
        header('Location: ' . app_url($path));
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

    private function isValidName(string $value): bool
    {
        $len = $this->textLength($value);
        if ($len < 2 || $len > 60) {
            return false;
        }

        return (bool)preg_match("/^[\\p{L}\\s\\-'’]+$/u", $value);
    }

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isValidPassword(string $password): bool
    {
        return strlen($password) >= 8;
    }

    public function login()
    {
        $pdo = getPDO();
        $erreur = '';
        $erreur_insc = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['register'])) {
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');

            if (!$this->isValidEmail($email) || $password === '') {
                $erreur = "Email ou mot de passe incorrect";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email AND etat_compte = 'actif'");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['mot_de_passe'])) {

                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_role'] = $user['role'];

                    $this->redirect('/profil');
                } else {
                    $erreur = "Email ou mot de passe incorrect";
                }
            }
        }

        if (isset($_POST['register'])) {
            $nom = $this->normalizeText($_POST['reg_nom'] ?? '', 60);
            $prenom = $this->normalizeText($_POST['reg_prenom'] ?? '', 60);
            $email = strtolower(trim((string)($_POST['reg_email'] ?? '')));
            $password = (string)($_POST['reg_password'] ?? '');

            if (!$this->isValidName($nom) || !$this->isValidName($prenom)) {
                $erreur_insc = "Nom et prenom invalides.";
            } elseif (!$this->isValidEmail($email)) {
                $erreur_insc = "Adresse email invalide.";
            } elseif (!$this->isValidPassword($password)) {
                $erreur_insc = "Le mot de passe doit contenir au moins 8 caracteres.";
            }

            if ($erreur_insc !== '') {
                require_once 'views/auth/login.php';
                return;
            }

            $stmt = $pdo->prepare("SELECT * FROM user WHERE email = :email");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $erreur_insc = "Cet email est déjà utilisé.";
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO user (nom, prenom, email, mot_de_passe, etat_compte)
                    VALUES (:nom, :prenom, :email, :mdp, 'actif')
                ");

                $stmt->execute([
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':mdp' => $hash
                ]);

                $user_id = $pdo->lastInsertId();

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_prenom'] = $prenom;
                $_SESSION['user_role'] = 'utilisateur';

                $this->redirect('/profil');
            }
        }

        require_once 'views/auth/login.php';
    }

    public function register()
    {
        $pdo = getPDO();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $nom = $this->normalizeText($_POST['nom'] ?? '', 60);
            $prenom = $this->normalizeText($_POST['prenom'] ?? '', 60);
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $role = (string)($_POST['role'] ?? 'candidat');
            $allowedRoles = ['candidat', 'recruteur', 'utilisateur', 'entrepreneur', 'mentor', 'investisseur'];

            if (!$this->isValidName($nom) || !$this->isValidName($prenom)) {
                $error = "Nom et prenom invalides.";
            } elseif (!$this->isValidEmail($email)) {
                $error = "Adresse email invalide.";
            } elseif (!$this->isValidPassword($password)) {
                $error = "Le mot de passe doit contenir au moins 8 caracteres.";
            } elseif (!in_array($role, $allowedRoles, true)) {
                $error = "Role invalide.";
            }

            if ($error !== '') {
                require_once 'views/auth/register.php';
                return;
            }

            $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = "Cet email est déjà utilisé.";
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $sql = "INSERT INTO user 
                        (nom, prenom, email, mot_de_passe, role, date_creation, etat_compte) 
                        VALUES (?, ?, ?, ?, ?, CURDATE(), 'actif')";

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nom, $prenom, $email, $hash, $role]);

                    $user_id = $pdo->lastInsertId();

                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_role'] = $role;

                    $this->redirect('/profil');

                } catch (PDOException $e) {
                    $error = "Erreur lors de l'inscription.";
                }
            }
        }

        require_once 'views/auth/register.php';
    }

    public function logout()
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/auth/login');
    }
}
