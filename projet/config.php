<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            $servername = defined('DB_HOST') ? DB_HOST : 'localhost';
            $username = defined('DB_USER') ? DB_USER : 'root';
            $password = defined('DB_PASS') ? DB_PASS : '';
            $dbname = defined('DB_NAME') ? DB_NAME : 'craftlink_db';
            $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;dbname=$dbname;charset=$charset",
                    $username,
                    $password
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                die('Erreur: ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
config::getConnexion();

function getCurrentUserId()
{
    if (isset($_SESSION['id_user'])) {
        return (int)$_SESSION['id_user'];
    }
    return 0;
}
?>
