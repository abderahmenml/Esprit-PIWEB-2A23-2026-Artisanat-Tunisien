<?php
// controllers/DashboardController.php

require_once 'models/ProfilModel.php';
require_once 'config/config.php';

class DashboardController
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

    public function index()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }

        $user_id = $_SESSION['user_id'];

        $user = $this->model->getUserById($user_id);
        $stats = $this->model->getStats($user_id);
        $competences = $this->model->getCompetences($user_id);

        $fields = [
            'nom' => $user['nom'] ?? '',
            'prenom' => $user['prenom'] ?? '',
            'email' => $user['email'] ?? '',
            'specialite' => $user['specialite'] ?? '',
            'bio' => $user['bio'] ?? '',
            'competences' => count($competences) > 0
        ];
        $filled = 0;
        foreach ($fields as $value) {
            if (!empty($value)) {
                $filled++;
            }
        }
        $completion = (int)round(($filled / count($fields)) * 100);

        require_once 'views/dashboard/index.php';
    }

    public function annuaire()
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/auth/login');
        }

        $pdo = getPDO();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->query("SELECT COUNT(*) FROM user u LEFT JOIN profil_professionnel p ON u.id_user = p.id_user");
        $total = $stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($total / $limit));

        $sql = "SELECT u.id_user, u.nom, u.prenom, u.email, p.specialite, p.bio, p.ville, p.portfolio
                FROM user u
                LEFT JOIN profil_professionnel p ON u.id_user = p.id_user
                ORDER BY u.date_creation DESC
                LIMIT $limit OFFSET $offset";
        $profils = $pdo->query($sql)->fetchAll();

        require_once 'views/dashboard/annuaire.php';
    }

}

?>
