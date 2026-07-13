<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;

class PdoService
{
    private string $host;
    private string $db;
    private string $user;
    private string $pass;

    public function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'php-db';
        $this->db   = getenv('DB_DATABASE') ?: 'starter_db';
        $this->user = getenv('DB_USER') ?: 'db_user';
        $this->pass = getenv('DB_PASSWORD') ?: 'db_password';
    }

    public function connectPDO(): ?PDO
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db};charset=utf8mb4";
            return new PDO($dsn, $this->user, $this->pass);
        } catch (PDOException $e) {
            echo "Ошибка базы данных: " . $e->getMessage();
            return null;
        }
    }
}
