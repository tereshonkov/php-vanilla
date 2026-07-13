<?php

declare(strict_types=1);

namespace App;

use PDO;


final class HealthController
{
    public function __construct(private PdoConnection $pdo_connection) {}

    public function info()
    {
        $status = 'degraded';
        $mysqlVersion = null;

        try {
            $pdo = $this->pdo_connection->connect();
            $status = 'ok';
            $mysqlVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        } catch (\Exception $e) {
            // Записываем ошибку в системный лог Docker (контейнер php-fpm сразу её покажет) 
            // Это намеренный шаг что бы показать красивый json и не уложить роут
            error_log("HealthCheck DB Error: " . $e->getMessage());
        }

        $data = [
            'status' => $status,
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion
        ];

        // Если внутри $data будет ошибка, PHP выбросит JsonException
        return json_encode($data, JSON_THROW_ON_ERROR);
    }
}
