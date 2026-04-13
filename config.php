<?php
class Config {
    private static $pdo = null;
    
    public static function getConnexion() {
        if (self::$pdo === null) {
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "Projet";
            
            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;dbname=$dbname;charset=utf8",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e) {
                die("Erreur de connexion: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

function getPDO() {
    return Config::getConnexion();
}