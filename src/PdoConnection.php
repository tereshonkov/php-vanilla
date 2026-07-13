<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

final class PdoConnection
{
    // Ленивое свойства (фишка в том что создается один раз при первом вызове)
    private ?PDO $pdo = null;

    public function __construct(
        private string $host,
        private string $db,
        private string $user,
        private string $pass,
        private int $port = 3306
    ) {
        // Убрал тихие fallback'и. Если передали пустые строки — падаем сразу
        if (empty($this->host) || empty($this->db) || empty($this->user)) {
            throw new InvalidArgumentException('Database configuration is incomplete. Host, DB name, and User are required.');
        }
    }

    public function connect(): PDO
    {
        // ПРоверка если PDO уже создан то его и возвращаем
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        // Стандартный конфиг
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Выбрасывать исключения при ошибках SQL
            PDO::ATTR_EMULATE_PREPARES => false, // Использовать реальные подготовленные запросы (защита от SQL-инъекций)
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Чистый ассоциативный массив по умолчанию
            PDO::ATTR_STRINGIFY_FETCHES => false, // Не превращать инты и флоаты из БД в строки
        ];

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db};charset=utf8mb4";

            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);

            return $this->pdo;
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }
}
