<?php

namespace App\Service;

use PDO;
use PDOException;

class DatabaseConnection
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = $_ENV['DB_HOST'] ?? '';
            $dbname = $_ENV['DB_NAME'] ?? '';
            $username = $_ENV['DB_USER'] ?? '';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            if (empty($host) || empty($dbname) || empty($username) || empty($password)) {
                throw new PDOException("Database connection parameters are not set.");
            }

            try {
                self::$connection = self::createPDOConnection($host, $dbname, $username, $password);
            } catch (PDOException $e) {
                throw new PDOException("Connection failed: " . $e->getMessage());
            }
        }

        return self::$connection;
    }

    protected static function createPDOConnection(string $host, string $dbname, string $username, string $password): PDO
    {
        return new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
} 