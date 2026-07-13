<?php

declare(strict_types=1);

namespace App;

use App\PdoService;


class HealthController
{
    public function __construct(private PdoService $pdoService) {}

    public function get_info(): string
    {
        $data = [
            'php_version' => PHP_VERSION,
            'mysql_version' => null,
        ];

        $connection = $this->pdoService->connectPDO();

        if ($connection !== null) {
            $version = $connection->query('SELECT VERSION()')->fetchColumn();

            if ($version !== false) {
                $data['mysql_version'] = $version;
            }
        }

        return (string) json_encode($data);
    }
}
