<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

class InvalidAmount extends \InvalidArgumentException
{
    public static function create(string $format): self
    {
        return new self("Invalid money format {$format}");
    }

    public static function divide(string $devider): self
    {
        return new self("Invalid format,you can't devide {$devider}")
    }
}