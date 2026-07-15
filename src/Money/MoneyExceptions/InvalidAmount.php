<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

class InvalidAmount extends \InvalidArgumentException implements MoneyException
{
    public static function create(string $format): self
    {
        return new self("Invalid money format {$format}");
    }

    public static function invalidDecimals(string $amount, int $expected, int $actual): self
    {
        return new self(
            "The amount '{$amount}' has invalid scale. " .
                "Currency expected maximum {$expected} decimal places, but got {$actual}."
        );
    }

    public static function divide(string $devider): self
    {
        return new self("Invalid format, you can't divide {$devider}");
    }
}
