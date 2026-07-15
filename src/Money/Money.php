<?php

declare(strict_types=1);

namespace App\Money;

use App\Money\MoneyExceptions\InvalidAmount;
use App\Money\MoneyExceptions\CurrencyMismatchException;
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
        if (!preg_match('/^(0|-?[1-9]\d*)(\.\d+)?$/', $amount)) {
            throw InvalidAmount::create($amount);
        }

        $parts = explode('.', $amount);

        if (count($parts) === 2 && strlen($parts[1]) > $c->decimals()) {
            throw InvalidAmount::create($amount);
        }

        $centsStirng = bcmul($amount, (string) $c->subunitFactor(), 0);

        $cent = self::assertNoOverflow($centsStirng);

        return new self($cent, $c);
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
        $mult = bcmul((string) $this->amount, (string) $factor, 4);
        $rounded = bcround($mult, 0, RoundingMode::HalfEven);
        $safe = self::assertNoOverflow($rounded);
        return new self($safe, $this->currency);
    }
    public function divide(int|string $divisor): self
    {
        $divisorString = (string) $divisor;
        $decimalPos = strpos($divisorString, '.');
        $scale = $decimalPos !== false ? strlen($divisorString) - $decimalPos - 1 : 0;

        if (bccomp($divisorString, '0', $scale) === 0) {
            throw InvalidAmount::divide($divisor);
        }

        $div = bcdiv((string) $this->amount, $divisorString, 4);
        $rounded = bcround($div, 0, RoundingMode::HalfEven);
        $safe = self::assertNoOverflow($rounded);

        return new self($safe, $this->currency);
    }
    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }
    public function absolute(): self
    {
        return new self(abs($this->amount), $this->currency);
    }

    private static function assertNoOverflow(string | int $element)
    {
        $isTooLarge = bccomp($element, (string) PHP_INT_MAX) === 1;
        $isTooSmall = bccomp($element, (string) PHP_INT_MIN) === -1;

        if ($isTooLarge || $isTooSmall) {
            throw MoneyOverflowException::forAmount($element);
        }

        return (int) $element;
    }
}
