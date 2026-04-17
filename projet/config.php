<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            $servername = 'localhost';
            $username = 'root';
            $password = '';
            $dbname = 'projet';

            try {
                self::$pdo = new PDO(
                    "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
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
