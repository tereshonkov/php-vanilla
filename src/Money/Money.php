<?php

declare(strict_types=1);

namespace App\Money;

use InvalidArgumentException;
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
        if (!preg_match('/^-?\d+(\.\d+)?$/', $amount)) {
            throw new InvalidArgumentException("Invalid money format");
        }

        $parts = explode('.', $amount);

        if (count($parts) === 2 && strlen($parts[1]) > $c->decimals()) {
            throw new InvalidArgumentException();
        }

        $cents = (int) bcmul($amount, (string) $c->subunitFactor(), 0);

        return new self($cents, $c);
    }

    public static function zero(Currency $c): self
    {
        return new self(0, $c);
    }

    public function add(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException();
        };

        return new self($this->amount + $other->amount, $this->currency);
    }
    public function subtract(Money $other): self
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException();
        }

        return new self($this->amount - $other->amount, $this->currency);
    }
    public function multiply(int|string $factor): self
    {
        $mult = bcmul((string) $this->amount, (string) $factor, 4);
        $rounded = bcround($mult, 0, RoundingMode::HalfEven);
        return new self((int) $rounded, $this->currency);
    }
    public function divide(int|string $divisor): self
    {
        $div = bcdiv((string) $this->amount, (string) $divisor, 4);
        $rounded = bcround($div, 0, RoundingMode::HalfEven);
        return new self((int) $rounded, $this->currency);
    }
    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }
    public function absolute(): self
    {
        return new self(abs($this->amount), $this->currency);
    }
}
