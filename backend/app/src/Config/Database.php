<?php

namespace App\Config;

use PDO;
use PDOException;

/**
 * Database connection configuration
 *
 * Uses the Singleton pattern so only one PDO connection is created per request.
 * Connection details come from environment variables set in docker-compose.yml.
 *
 * Usage:
 *   $db = Database::getConnection();
 *   $stmt = $db->prepare("SELECT * FROM ingredients");
 */
class Database
{
    // The single PDO instance shared across the application
    private static ?PDO $connection = null;

    /**
     * Get the PDO database connection.
     * Creates a new connection on first call, then reuses it.
     *
     * @return PDO
     * @throws PDOException if connection fails
     */
    public static function getConnection(): PDO
    {
        // If we already have a connection, return it (Singleton)
        if (self::$connection !== null) {
            return self::$connection;
        }

        // Read connection details from environment variables (set in docker-compose.yml)
        $host = getenv('DB_HOST');
        $name = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        // Build the DSN (Data Source Name) string for PDO
        // charset=utf8mb4 supports full Unicode including emoji
        $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

        // Create the PDO connection with error handling options
        self::$connection = new PDO($dsn, $user, $pass, [
            // Throw exceptions on database errors (instead of silent failures)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Return results as associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use real prepared statements (not emulated ones)
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
