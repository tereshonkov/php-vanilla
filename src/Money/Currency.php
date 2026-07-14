<?php

declare(strict_types=1);

enum Currency: string
{
    case UAH = 'UAH';
    case USD = 'USD';
    case EUR = 'EUR';
    case JPY = 'JPY';

    public function decimals(): int
    {
        return match ($this) {
            self::UAH => 2,
            self::USD => 2,
            self::EUR => 2,
            self::JPY => 0,
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::UAH => '₴',
            self::USD => '$',
            self::EUR => '€',
            self::JPY => '¥',
        };
    }

    public function subunitFactor(): int {
        return 10 ** $this->decimals();
    }
};
