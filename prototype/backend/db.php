<?php
//load database connection using environment variables
require_once __DIR__ . '/env_loader.php';

load_env(__DIR__ . '/../.env');

class DatabaseConnection
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        //checks if connection already exists and if yes, use it
        if ($this->conn !== null) {
            return $this->conn;
        }
        //else creaete new connection
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NAME']
        );

        try {
            $this->conn = new PDO(
                $dsn,
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
            //if conn fails throw exception error
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'errors' => ['Database connection failed.']]);
            exit;
        }

        return $this->conn;
    }
}
