<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

set_exception_handler(function (\Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal Server Error',
        // В dev-окружении можно добавить для удобства:
        'debug' => $exception->getMessage()
    ], JSON_THROW_ON_ERROR);
    exit;
});

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$uri = new \Uri\Rfc3986\Uri($_SERVER['REQUEST_URI']);
$path = $uri->getPath();

if ($path === '/health') {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed'], JSON_THROW_ON_ERROR);
        exit;
    }

    $dbConnection = new App\PdoConnection(
        (string) getenv('DB_HOST'),
        (string) getenv('DB_DATABASE'),
        (string) getenv('DB_USER'),
        (string) getenv('DB_PASSWORD')
    );

    $controller = new App\HealthController($dbConnection);
    $responseJson = $controller->info();

    $responseData = json_decode($responseJson, true);
    if (($responseData['status'] ?? 'error') === 'ok') {
        http_response_code(200);
    } else {
        http_response_code(503);
    }

    echo $responseJson;
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found'], JSON_THROW_ON_ERROR);
