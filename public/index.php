<?php

declare(strict_types=1);

use Uri\InvalidUriException;
use App\HealthController;
use App\PdoConnection;

require __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json');

set_exception_handler(function (\Throwable $exception) {
    http_response_code(500);
    $response = [
        'status' => 'error',
        'message' => 'Internal Server Error',
    ];

    if (getenv('APP_ENV') === 'local') {
        $response['debug'] = $exception->getMessage();
    }

    echo json_encode($response, JSON_THROW_ON_ERROR);
    exit;
});

function env(string $key): string
{
    $value = getenv($key);

    if (is_string($value) && $value !== '') {
        return $value;
    }

    throw new RuntimeException("Environment variable '{$key}' is not set or empty");
}

function serverString(string $key): string
{
    $value = $_SERVER[$key] ?? null;

    if (is_string($value) && $value !== '') {
        return $value;
    }

    throw new RuntimeException("Server variable '{$key}' is missing or empty");
}

$method = serverString('REQUEST_METHOD');
$requestUri = serverString('REQUEST_URI');

try {
    $uri = new \Uri\Rfc3986\Uri($requestUri);
    $path = $uri->getPath();
} catch (InvalidUriException) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad Request'], JSON_THROW_ON_ERROR);
    exit;
}

if ($path === '/health') {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed'], JSON_THROW_ON_ERROR);
        exit;
    }


    $dbConnection = new PdoConnection(
        env('DB_HOST'),
        env('DB_DATABASE'),
        env('DB_USER'),
        env('DB_PASSWORD'),
    );

    $controller = new HealthController($dbConnection);
    $response = $controller->info();

    if (($response['status'] ?? 'error') === 'ok') {
        http_response_code(200);
    } else {
        http_response_code(503);
    }

    echo json_encode($response, JSON_THROW_ON_ERROR);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found'], JSON_THROW_ON_ERROR);
