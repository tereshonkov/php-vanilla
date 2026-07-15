<?php

declare(strict_types=1);

namespace App\Money;

use App\Money\MoneyExceptions\InvalidAmount;
use App\Money\MoneyExceptions\CurrencyMismatchException;
use App\Money\MoneyExceptions\DivisionByZeroException;
use App\Money\MoneyExceptions\MoneyOverflowException;
use RoundingMode;

final readonly class Money
{
    private function __construct(
        public int $amount,
        public Currency $currency,
    ) {}

    public static function fromCents(int $amount, Currency $c): self
    {
        return new self($amount, $c);
    }

    public static function fromString(string $amount, Currency $c): self
    {
        $cleanAmount = self::validateNumericFactor($amount);

        $parts = explode('.', $amount);

        if (count($parts) === 2) {
            $actualDecimals = strlen($parts[1]);
            $expectedDecimals = $c->decimals();

            if ($actualDecimals > $expectedDecimals) {
                throw InvalidAmount::invalidDecimals($amount, $expectedDecimals, $actualDecimals);
            }
        }

        $centsStirng = bcmul($cleanAmount, (string) $c->subunitFactor(), 0);

        $cents = self::assertNoOverflow($centsStirng);

        return new self($cents, $c);
    }

    public static function zero(Currency $c): self
    {
        return new self(0, $c);
    }

    public function add(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw CurrencyMismatchException::create($this->currency, $other->currency);
        };

        $sum = bcadd((string) $this->amount, (string) $other->amount, 0);
        $safe = self::assertNoOverflow($sum);

        return new self($safe, $this->currency);
    }
    public function subtract(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw CurrencyMismatchException::create($this->currency, $other->currency);
        };

        $sub = bcsub((string) $this->amount, (string) $other->amount, 0);
        $safe = self::assertNoOverflow($sub);

        return new self($safe, $this->currency);
    }
    public function multiply(int|string $factor): self
    {
        $factorStr = self::validateNumericFactor($factor);

        $mult = bcmul((string) $this->amount, $factorStr, 4);
        $rounded = bcround($mult, 0, RoundingMode::HalfEven);
        $safe = self::assertNoOverflow($rounded);

        return new self($safe, $this->currency);
    }
    public function divide(int|string $divisor): self
    {
        $divisorString = self::validateNumericFactor($divisor);
        $decimalPos = strpos($divisorString, '.');
        $scale = $decimalPos !== false ? strlen($divisorString) - $decimalPos - 1 : 0;

        if (bccomp($divisorString, '0', $scale) === 0) {
            throw DivisionByZeroException::create($divisor);
        }

        $div = bcdiv((string) $this->amount, $divisorString, 4);
        $rounded = bcround($div, 0, RoundingMode::HalfEven);
        $safe = self::assertNoOverflow($rounded);

        return new self($safe, $this->currency);
    }
    public function negate(): self
    {
        $safe = self::assertNoOverflow(-$this->amount);
        return new self($safe, $this->currency);
    }
    public function absolute(): self
    {
        $safe = self::assertNoOverflow(abs($this->amount));
        return new self($safe, $this->currency);
    }
    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
    public function isGreaterThan(Money $other): bool
    {
        return $this->compareTo($other) === 1;
    }
    public function isLessThan(Money $other): bool
    {
        return $this->compareTo($other) === -1;
    }
    public function isGreaterThanOrEqual(Money $other): bool
    {
        return $this->compareTo($other) >= 0;
    }
    public function isLessThanOrEqual(Money $other): bool
    {
        return $this->compareTo($other) <= 0;
    }
    public function isZero(): bool
    {
        return $this->amount === 0;
    }
    public function isPositive(): bool
    {
        return $this->amount > 0;
    }
    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Helpers
     */

    private function compareTo(Money $other): int
    {
        if ($other->currency !== $this->currency) {
            throw CurrencyMismatchException::create($other->currency, $this->currency);
        }
        $money = bccomp((string) $this->amount, (string) $other->amount);

        return $money;
    }

    private static function validateNumericFactor(int|string $value): string
    {
        $strValue = (string) $value;

        $isValidFormat = preg_match('/^(0|-?[1-9]\d*)(\.\d+)?$/', $strValue);
        $isNegativeZero = str_starts_with($strValue, '-') && bccomp($strValue, '0', 10) === 0;

        if (!$isValidFormat || $isNegativeZero) {
            throw InvalidAmount::create($strValue);
        }

        return $strValue;
    }

    private static function assertNoOverflow(string | int $element): int
    {
        $isTooLarge = bccomp($element, (string) PHP_INT_MAX) === 1;
        $isTooSmall = bccomp($element, (string) PHP_INT_MIN) === -1;

        if ($isTooLarge || $isTooSmall) {
            throw MoneyOverflowException::forAmount($element);
        }

        return (int) $element;
    }
}
