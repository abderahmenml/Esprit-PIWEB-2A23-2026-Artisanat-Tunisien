<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Contrôleur AdminUserController — CRUD complet sur les utilisateurs (BackOffice)
 * Accessible uniquement aux administrateurs
 */
class AdminUserController extends Controller {

    private UserModel $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->requireAdmin();
        $this->userModel = new UserModel();
    }

    // ── INDEX : Liste des utilisateurs ───────────────────────────────────────

    public function index(): void {
        $filters = [
            'role'        => trim($_GET['role']   ?? ''),
            'etat_compte' => trim($_GET['status'] ?? ''),
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $users = $this->userModel->findAll(
            array_filter($filters),
            'date_creation DESC'
        );

        $stats = [
            'total'       => $this->userModel->count(),
            'actif'       => $this->userModel->count(['etat_compte' => 'actif']),
            'inactif'     => $this->userModel->count(['etat_compte' => 'inactif']),
            'byRole'      => $this->userModel->countByRole(),
        ];

        $this->render('admin/users/index', [
            'users'   => $users,
            'stats'   => $stats,
            'filters' => $filters,
        ]);
    }

    // ── CREATE : Afficher formulaire / Traiter création ──────────────────────

    public function create(): void {
        $errors = [];
        $old    = [];

        if ($this->isPost()) {
            $nom         = trim($_POST['nom']         ?? '');
            $prenom      = trim($_POST['prenom']      ?? '');
            $email       = trim($_POST['email']       ?? '');
            $password    =      $_POST['password']    ?? '';
            $confirm     =      $_POST['confirm']     ?? '';
            $role        = trim($_POST['role']        ?? '');
            $etat_compte = trim($_POST['etat_compte'] ?? 'actif');

            $old = compact('nom', 'prenom', 'email', 'role', 'etat_compte');

            // Validation serveur sans HTML5
            $v = new Validator();
            $v->required('nom',      $nom,      'Nom')
              ->alpha('nom',         $nom,      'Nom')
              ->maxLength('nom',     $nom, 100, 'Nom')
              ->required('prenom',   $prenom,   'Prénom')
              ->alpha('prenom',      $prenom,   'Prénom')
              ->maxLength('prenom',  $prenom, 100, 'Prénom')
              ->required('email',    $email,    'Email')
              ->email('email',       $email,    'Email')
              ->required('password', $password, 'Mot de passe')
              ->minLength('password',$password, 6, 'Mot de passe')
              ->matches('confirm',   $password, $confirm, 'Confirmation du mot de passe')
              ->required('role',     $role,     'Rôle')
              ->inList('role',       $role,     ROLES, 'Rôle');

            if (!$v->isValid()) {
                $errors = $v->getErrors();
            } elseif ($this->userModel->emailExists($email)) {
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée.';
            } else {
                $this->userModel->createDirect([
                    'nom'         => $nom,
                    'prenom'      => $prenom,
                    'email'       => $email,
                    'password'    => $password,
                    'role'        => $role,
                    'etat_compte' => $etat_compte,
                ]);

                $_SESSION['flash'] = ['type' => 'success', 'msg' => "Utilisateur $prenom $nom créé avec succès."];
                $this->redirect('index.php?action=users');
            }
        }

        $this->render('admin/users/create', [
            'errors' => $errors,
            'old'    => $old,
        ]);
    }

    // ── EDIT : Afficher formulaire / Traiter mise à jour ─────────────────────

    public function edit(): void {
        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
            $this->redirect('index.php?action=users');
        }

        $errors = [];

        if ($this->isPost()) {
            $nom         = trim($_POST['nom']         ?? '');
            $prenom      = trim($_POST['prenom']      ?? '');
            $email       = trim($_POST['email']       ?? '');
            $role        = trim($_POST['role']        ?? '');
            $etat_compte = trim($_POST['etat_compte'] ?? '');

            // Validation serveur sans HTML5
            $v = new Validator();
            $v->required('nom',     $nom,     'Nom')
              ->alpha('nom',        $nom,     'Nom')
              ->required('prenom',  $prenom,  'Prénom')
              ->alpha('prenom',     $prenom,  'Prénom')
              ->required('email',   $email,   'Email')
              ->email('email',      $email,   'Email')
              ->required('role',    $role,    'Rôle')
              ->inList('role',      $role,    ROLES, 'Rôle')
              ->required('etat_compte', $etat_compte, 'État du compte')
              ->inList('etat_compte',   $etat_compte, ['actif','inactif','suspendu'], 'État du compte');

            if (!$v->isValid()) {
                $errors = $v->getErrors();
                $user   = array_merge($user, compact('nom','prenom','email','role','etat_compte'));
            } elseif ($this->userModel->emailExists($email, $id)) {
                $errors['email'] = 'Cette adresse e-mail est déjà utilisée par un autre compte.';
                $user = array_merge($user, compact('nom','prenom','email','role','etat_compte'));
            } else {
                $this->userModel->update($id, compact('nom','prenom','email','role','etat_compte'));
                $_SESSION['flash'] = ['type' => 'success', 'msg' => "Utilisateur mis à jour avec succès."];
                $this->redirect('index.php?action=users');
            }
        }

        $this->render('admin/users/edit', [
            'user'   => $user,
            'errors' => $errors,
        ]);
    }

    // ── DELETE : Supprimer un utilisateur ────────────────────────────────────

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
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Utilisateur {$user['prenom']} {$user['nom']} supprimé."];
        }

        $this->redirect('index.php?action=users');
    }

    // ── TOGGLE STATUS : Activer / Désactiver ─────────────────────────────────

    public function toggleStatus(): void {
        header('Content-Type: application/json');
        $id     = (int) ($_POST['id']     ?? 0);
        $status = trim($_POST['status']   ?? '');

        $v = new Validator();
        $v->inList('status', $status, ['actif','inactif','suspendu'], 'État');

        if (!$v->isValid() || !$id) {
            $this->json(['success' => false, 'message' => 'Paramètres invalides.']);
        }

        $ok = $this->userModel->updateStatus($id, $status);
        $this->json(['success' => $ok, 'message' => $ok ? 'Statut mis à jour.' : 'Erreur.']);
    }

    // ── SHOW : Voir le détail d'un utilisateur ───────────────────────────────

    public function show(): void {
        $id   = (int) ($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);

        if (!$user) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Utilisateur introuvable.'];
            $this->redirect('index.php?action=users');
        }

        $this->render('admin/users/show', ['user' => $user]);
    }
}
