<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'wahdi'; // Remplace par le nom exact de ta base de données
    private $username = 'root'; // Par défaut dans XAMPP
    private $password = '';     // Vide par défaut dans XAMPP
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
