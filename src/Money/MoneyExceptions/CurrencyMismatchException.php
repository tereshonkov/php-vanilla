<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

use App\Money\Currency;

final class CurrencyMismatchException extends \InvalidArgumentException implements MoneyException
{
    public static function create(Currency $first, Currency $second): self
    {
        return new self("Cannot operate on different currencies: {$first->value} and {$second->value}");
    }
}
