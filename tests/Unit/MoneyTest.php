<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Money\Money;
use App\Money\Currency;
use App\Money\CurrencyMismatchException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_can_be_created_from_strings(): void
    {
        $usdMoney = Money::fromString('19.99', Currency::USD);
        $this->assertSame(1999, $usdMoney->amount);

        $jpyMoney = Money::fromString('1999', Currency::JPY);
        $this->assertSame(1999, $jpyMoney->amount);
    }

    public function test_it_throws_exception_when_adding_bad_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('19.5', Currency::JPY);

        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('abc', Currency::USD);

        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('', Currency::USD);

        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('1.99', Currency::USD);

        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('1.2.3', Currency::USD);

        $this->expectException(\InvalidArgumentException::class);
        Money::fromString('19.999', Currency::USD);
    }

    public function test_it_throws_exception_when_adding_different_currencies(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $eur = Money::fromCents(100, Currency::EUR);

        $this->expectException(CurrencyMismatchException::class);
        $usd->add($eur);

        $this->expectException(CurrencyMismatchException::class);
        $usd->subtract($eur);
    }

    public function test_it_HalfEven(): void
    {
        $usd = Money::fromCents(15, Currency::USD);
        $result = $usd->multiply('0.5');
        $this->assertSame(8, $result->amount);
    }

    public function test_it_Immutable(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $new = $usd->add(Money::fromCents(50, Currency::USD));

        $this->assertSame(100, $usd->amount);
        $this->assertSame(150, $new->amount);
        $this->assertNotSame($usd, $new);
    }

    public function test_it_negate_and_absolute(): void
    {
        $money = Money::fromCents(10, Currency::USD);
        $negate = $money->negate();
        $this->assertSame(-10, $negate->amount);
        $absolute = $negate->absolute();
        $this->assertSame(10, $absolute->amount);
    }

    // equals
    // равные суммы, одна валюта → true  (ГЛАВНЫЙ кейс, его у тебя не было)
    public function test_equals_returns_true_for_same_amount(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertTrue($a->equals($b));
    }

    // разные суммы, одна валюта → false
    public function test_equals_returns_false_for_different_amount(): void
    {
        $a = Money::fromCents(200, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertFalse($a->equals($b));
    }

    // разные валюты → false (не исключение!)
    public function test_equals_returns_false_for_different_currencies(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $uah = Money::fromCents(100, Currency::UAH);
        $this->assertFalse($usd->equals($uah));
    }

    // isGreaterThan
    public function test_greater_than_throws_for_different_currencies(): void
    {
        $this->expectException(CurrencyMismatchException::class);
        Money::fromCents(100, Currency::USD)
            ->isGreaterThan(Money::fromCents(100, Currency::UAH));
    }

    public function test_greater_than_returns_true_when_larger(): void
    {
        $a = Money::fromCents(200, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        self::assertTrue($a->isGreaterThan($b));      // 200 > 100
    }

    public function test_greater_than_returns_false_when_smaller(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(200, Currency::USD);
        self::assertFalse($a->isGreaterThan($b));     // 100 > 200
    }

    public function test_greater_than_returns_false_when_equal(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        self::assertFalse($a->isGreaterThan($b));     // 100 > 100 — строго больше!
    }
}
