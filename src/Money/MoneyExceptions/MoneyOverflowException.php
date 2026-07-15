<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

use InvalidArgumentException;

class MoneyOverflowException extends InvalidArgumentException
{
    public static function forAmount(string $amount): self
    {
        return new self("The amount '{$amount}' is exceeds the maximum allowed limit.");
    }
}
