<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Classe Database — Connexion PDO (Singleton)
 * Pattern Singleton : une seule instance de connexion partagée
 */
class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode([
                'success' => false,
                'message' => 'Connexion DB échouée : ' . $e->getMessage()
            ]));
        }
    }

    /**
     * Récupère l'instance unique (Singleton)
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne l'objet PDO
     */
    public function getConnection(): PDO {
        return $this->pdo;
    }

    // Empêcher le clonage et la désérialisation
    private function __clone() {}
    public function __wakeup() {}
}
