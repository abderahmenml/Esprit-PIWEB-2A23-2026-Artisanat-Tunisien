<?php
require_once __DIR__ . '/Database.php';

/**
 * Classe Model — Classe de base pour tous les modèles
 * Fournit l'accès PDO et des méthodes utilitaires communes
 */
abstract class Model {
    protected PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
}
