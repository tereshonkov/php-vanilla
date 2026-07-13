<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\HealthController;
use App\PdoService;

$url = $_SERVER['REQUEST_URI'] ?? '/';

if ($url === "/health") {
    header('Content-type: application/json');
    $pdoService = new PdoService();
    $healthController = new HealthController($pdoService);
    echo $healthController->get_info();
    exit;
} else {
    header('Content-type: application/json');
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
