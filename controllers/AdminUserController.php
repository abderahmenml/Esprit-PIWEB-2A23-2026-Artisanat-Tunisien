<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../config/config.php';

class AdminUserController extends Controller {
    private UserModel $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->requireAdmin();
        $this->userModel = new UserModel();
    }

    public function index(): void {
        $filters = [
            'role' => trim($_GET['role'] ?? ''),
            'etat_compte' => trim($_GET['status'] ?? ''),
            'search' => trim($_GET['search'] ?? ''),
        ];

        $users = $this->userModel->findAll(array_filter($filters), 'date_creation DESC');
        $stats = [
            'total' => $this->userModel->count(),
            'actif' => $this->userModel->count(['etat_compte' => 'actif']),
            'inactif' => $this->userModel->count(['etat_compte' => 'inactif']),
            'byRole' => $this->userModel->countByRole(),
        ];

        $this->render('admin/users/index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function create(): void {
        $errors = [];
        $old = [];

        if ($this->isPost()) {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            $role = trim($_POST['role'] ?? '');
            $etat_compte = trim($_POST['etat_compte'] ?? 'actif');
            $old = compact('nom', 'prenom', 'email', 'role', 'etat_compte');

            $v = new Validator();
            $v->required('nom', $nom, 'Nom')
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

            if (!$v->isValid()) {
                $errors = $v->getErrors();
            } elseif ($this->userModel->emailExists($email)) {
                $errors['email'] = 'Cette adresse e-mail est deja utilisee.';
            } else {
                $this->userModel->createDirect([
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'etat_compte' => $etat_compte,
                ]);

                $_SESSION['flash'] = ['type' => 'success', 'msg' => "Utilisateur $prenom $nom cree avec succes."];
                $this->redirect('index.php?action=users');
            }
        }

        $this->render('admin/users/create', ['errors' => $errors, 'old' => $old]);
    }

    public function edit(): void {
        $id = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
            $this->redirect('index.php?action=users');
        }

        $errors = [];
        if ($this->isPost()) {
            $nom = trim($_POST['nom'] ?? '');
            $prenom = trim($_POST['prenom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $etat_compte = trim($_POST['etat_compte'] ?? '');

            $v = new Validator();
            $v->required('nom', $nom, 'Nom')
              ->alpha('nom', $nom, 'Nom')
              ->required('prenom', $prenom, 'Prenom')
              ->alpha('prenom', $prenom, 'Prenom')
              ->required('email', $email, 'Email')
              ->email('email', $email, 'Email')
              ->required('role', $role, 'Role')
              ->inList('role', $role, ROLES, 'Role')
              ->required('etat_compte', $etat_compte, 'Etat du compte')
              ->inList('etat_compte', $etat_compte, ['actif', 'inactif', 'suspendu'], 'Etat du compte');

            if (!$v->isValid()) {
                $errors = $v->getErrors();
                $user = array_merge($user, compact('nom', 'prenom', 'email', 'role', 'etat_compte'));
            } elseif ($this->userModel->emailExists($email, $id)) {
                $errors['email'] = 'Cette adresse e-mail est deja utilisee par un autre compte.';
                $user = array_merge($user, compact('nom', 'prenom', 'email', 'role', 'etat_compte'));
            } else {
                $this->userModel->update($id, compact('nom', 'prenom', 'email', 'role', 'etat_compte'));
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Utilisateur mis a jour avec succes.'];
                $this->redirect('index.php?action=users');
            }
        }

        $this->render('admin/users/edit', ['user' => $user, 'errors' => $errors]);
    }

    public function delete(): void {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vous ne pouvez pas supprimer votre propre compte.'];
            $this->redirect('index.php?action=users');
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
        } else {
            $this->userModel->delete($id);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Utilisateur {$user['prenom']} {$user['nom']} supprime."];
        }

        $this->redirect('index.php?action=users');
    }

    public function toggleStatus(): void {
        header('Content-Type: application/json');
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        $v = new Validator();
        $v->inList('status', $status, ['actif', 'inactif', 'suspendu'], 'Etat');

        if (!$v->isValid() || !$id) {
            $this->json(['success' => false, 'message' => 'Parametres invalides.']);
        }

        $ok = $this->userModel->updateStatus($id, $status);
        $this->json(['success' => $ok, 'message' => $ok ? 'Statut mis a jour.' : 'Erreur.']);
    }

    public function show(): void {
        $id = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
            $this->redirect('index.php?action=users');
        }

        $this->render('admin/users/show', ['user' => $user]);
    }

    public function toggleBlock(): void {
        $id = (int) ($_POST['id'] ?? 0);
        $expectsJson = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        $respond = function (bool $success, string $message, ?bool $isBlocked = null) use ($expectsJson): void {
            if ($expectsJson) {
                $payload = ['success' => $success, 'message' => $message];
                if ($isBlocked !== null) {
                    $payload['is_blocked'] = $isBlocked;
                }
                $this->json($payload, $success ? 200 : 422);
            }

            $_SESSION['flash'] = ['type' => $success ? 'success' : 'error', 'msg' => $message];
            $this->redirect('index.php?action=users');
        };

        if (!$id) {
            $respond(false, 'ID utilisateur invalide.');
        }

        $user = $this->userModel->findById($id);
        if (!$user) {
            $respond(false, 'Utilisateur introuvable.');
        }

        if ($id === (int) $_SESSION['user_id']) {
            $respond(false, 'Vous ne pouvez pas bloquer votre propre compte.');
        }

        if (($user['email'] ?? '') === 'admin@craftlink.tn' || ($user['role'] ?? '') === 'admin') {
            $respond(false, 'Le compte administrateur ne peut pas etre bloque.');
        }

        $securityLocked = !empty($user['permanently_locked'])
            || (!empty($user['blocked_until']) && strtotime((string) $user['blocked_until']) > time());
        $currentlyLocked = !empty($user['is_blocked']) || $securityLocked;
        $newBlockedStatus = !$currentlyLocked;

        if ($newBlockedStatus) {
            $ok = $this->userModel->toggleBlocked($id, true);
        } else {
            $ok = $this->userModel->toggleBlocked($id, false);
        }

        $respond($ok, $ok ? ($newBlockedStatus ? 'Compte bloque.' : 'Compte debloque.') : 'Erreur.', $newBlockedStatus);
    }

    public function blockForm(): void {
        $id = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
            $this->redirect('index.php?action=users');
        }

        if ($id === (int) $_SESSION['user_id']) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Vous ne pouvez pas bloquer votre propre compte.'];
            $this->redirect('index.php?action=users');
        }

        $this->render('admin/users/block_confirm', ['user' => $user]);
    }
}
