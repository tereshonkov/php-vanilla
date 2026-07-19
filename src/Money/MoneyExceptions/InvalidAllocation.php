<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

use InvalidArgumentException;

final class InvalidAllocation extends InvalidArgumentException implements MoneyException
{
    public static function nonPositiveSlices(int $slices): self
    {
        return new self("{$slices} can't be non positive!");
    }
}
