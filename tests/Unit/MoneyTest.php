<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Money\Money;
use App\Money\Currency;
use PHPUnit\Framework\TestCase;

class MoneyTest 
{
        private function __construct(
        public int $amount,
        public Currency $currency,
    ) {}
}