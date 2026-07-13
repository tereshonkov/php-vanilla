<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class HealthController
{
    public function __construct(private PdoConnection $pdoConnection) {}

    public function info(): array
    {

        $status = 'ok';
        $mysqlVersion = null;

        try {
            $pdo = $this->pdoConnection->connect();
            $pdo->query('SELECT 1');
            $mysqlVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (RuntimeException $e) {
            $status = 'degraded';
            // Записываем ошибку в системный лог Docker (контейнер php-fpm сразу её покажет) 
            // Это намеренный шаг что бы показать красивый json и не уложить роут
            error_log("HealthCheck DB Error: " . $e->getMessage());
        }

        return [
            'status' => $status,
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion
        ];
    }
}
