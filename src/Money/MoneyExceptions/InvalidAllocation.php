<?php

declare(strict_types=1);

namespace App\Money\MoneyExceptions;

use InvalidArgumentException;

final class InvalidAllocation extends InvalidArgumentException implements MoneyException
{
    public static function nonPositiveSlices(int $slices): self
    {
        return new self("Number of slices must be positive, got {$slices}");
    }

    public static function emptyArguments(): self
    {
        return new self("At least one allocation ratio must be provided.");
    }

    public static function zeroSum(): self
    {
        return new self("Allocation ratios must be positive integers greater than 0.");
    }

    public static function negativeValue(int $ratio): self
    {
        return new self("Allocation ratio cannot be negative, got {$ratio}");
    }
}
