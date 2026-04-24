<?php
/**
 * Classe Controller — Classe de base pour tous les contrôleurs
 * Fournit des méthodes utilitaires pour les vues, la session, etc.
 */
abstract class Controller {

    /**
     * Charge et affiche une vue
     * @param string $view   Chemin relatif au dossier views/ (ex: 'admin/users')
     * @param array  $data   Variables à extraire et rendre disponibles dans la vue
     */
    protected function render(string $view, array $data = []): void {
        extract($data); // rend les clés du tableau disponibles comme variables
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("Vue introuvable : " . htmlspecialchars($view));
        }
        require_once $viewFile;
    }

    /**
     * Redirige vers une URL
     */
    protected function redirect(string $url): void {
        header("Location: " . $url);
        exit;
    }

    /**
     * Retourne une réponse JSON et termine le script
     */
    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Vérifie si la requête est POST
     */
    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Vérifie si l'utilisateur est connecté, sinon redirige
     */
    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('../index.php');
        }
    }

    /**
     * Vérifie si l'utilisateur est admin, sinon redirige
     */
    protected function requireAdmin(): void {
        $this->requireAuth();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('../index.php');
        }
    }

    /**
     * Nettoie et sécurise une valeur
     */
    protected function sanitize(string $value): string {
        return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }
}
