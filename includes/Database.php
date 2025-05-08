<?php
class Database {
    private static $instance = null;
    private static $pdo = null;

    private function __construct() {
        // Empêcher l'instanciation directe
    }

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $config = require __DIR__ . '/../config/database.php';
                
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ];
                
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            } catch (PDOException $e) {
                throw new Exception("Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    // Empêcher le clonage de l'instance
    private function __clone() {}

    // Empêcher la désérialisation de l'instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
