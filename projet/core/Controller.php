<?php
/**
 * Classe Controller - Classe de base pour tous les controleurs.
 */
abstract class Controller {
    protected function render(string $view, array $data = []): void {
        extract($data);
        $viewFile = __DIR__ . '/../View/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("Vue introuvable : " . htmlspecialchars($view));
        }
        ob_start();
        require $viewFile;
        $output = ob_get_clean();

        echo $output;
    }

    protected function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    protected function json(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }

    protected function isPost(): bool {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function requireAuth(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('login.php');
        }
    }

    protected function requireLogin(): void {
        $this->requireAuth();
    }

    protected function requireAdmin(): void {
        $this->requireAuth();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->redirect('index.php');
        }
    }

    protected function sanitize(string $value): string {
        return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
    }
}
