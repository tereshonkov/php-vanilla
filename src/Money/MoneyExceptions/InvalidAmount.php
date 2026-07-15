<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

class InvalidAmount extends \InvalidArgumentException
{
    public static function create(string $format): self
    {
        return new self("Invalid money format {$format}");
    }
}