<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
        $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: '';
        $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: '';
        $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: null;

        if ($name === '' || $user === '' || $pass === null || $pass === '') {
            throw new RuntimeException('Database credentials are not configured.');
        }

        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name);

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$pdo;
    }
}