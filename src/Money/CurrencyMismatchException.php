<?php

declare(strict_types=1);

namespace App\Money;

class CurrencyMismatchException extends \InvalidArgumentException
{
    public static function create(Currency $expected, Currency $actual): self
    {
        return new self("Cannot operate on different currencies: {$expected->value} and {$actual->value}");
    }
}