<?php

declare(strict_types=1);

namespace Tests;

use App\HealthController;
use App\PdoConnection;
use PHPUnit\Framework\TestCase;

/**
 * Создаем простую заглушку, которая всегда бросает исключение при попытке подключения.
 * PHPStan легко прочитает этот класс, так как тут нет магии PHPUnit.
 */
class FailingPdoConnectionStub extends PdoConnection
{
    public function __construct()
    {
        // Передаем фейковые строки, так как нам не нужно реальное подключение
        parent::__construct('fake_host', 'fake_db', 'fake_user', 'fake_pass');
    }

    public function connect(): \PDO
    {
        throw new \RuntimeException('Database offline');
    }
}

final class ExampleTest extends TestCase
{
    public function testInfoReturnsCorrectArrayStructure(): void
    {
        // Используем нашу заглушку
        $pdoConnectionStub = new FailingPdoConnectionStub();

        $controller = new HealthController($pdoConnectionStub);
        $response = $controller->info();

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('php_version', $response);
        $this->assertArrayHasKey('mysql_version', $response);

        $this->assertSame('degraded', $response['status']);
        $this->assertNull($response['mysql_version']);
        $this->assertSame(PHP_VERSION, $response['php_version']);
    }
}
