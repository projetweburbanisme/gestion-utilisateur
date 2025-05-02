<?php
// filepath: c:\xampp1\htdocs\ghodwa\config\database.php
class Database {
    private static $connection = null;

    public static function getConnection() { // Ensure this method is static
        if (self::$connection === null) {
            try {
                self::$connection = new PDO("mysql:host=localhost;dbname=clyptorweb", "root", "");
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {
                die("Connection error: " . $exception->getMessage());
            }
        }
        return self::$connection;
    }
}

function getDatabaseConnection() {
    try {
        $host = 'localhost'; // Replace with your database host
        $dbname = 'clyptorweb';   // Replace with your database name
        $username = 'root';  // Replace with your database username
        $password = '';      // Replace with your database password

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $username, $password, $options);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>