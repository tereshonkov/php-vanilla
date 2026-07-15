<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

use InvalidArgumentException;

final class DivisionByZeroException extends InvalidArgumentException implements MoneyException
{
    public static function create(int|string|float $divisor): self
    {
        return new self("Division by zero or invalid divisor: '{$divisor}' is not allowed.");
    }
}
