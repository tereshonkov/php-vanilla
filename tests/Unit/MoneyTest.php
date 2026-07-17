<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Money\Currency;
use App\Money\Money;
use App\Money\MoneyExceptions\CurrencyMismatchException;
use App\Money\MoneyExceptions\InvalidAmount;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    // DATA PROVIDER fromString

    /**
     * @return iterable<string, array{0: string, 1: Currency, 2: int}>
     */
    public static function validStringValues(): iterable
    {
        yield 'USD cents representation' => ['19.99', Currency::USD, 1999]; // Тут три аргумента
        yield 'JPY no cents representation' => ['1999', Currency::JPY, 1999];
    }

    #[DataProvider('validStringValues')]
    public function test_from_string_parses_valid_values(string $input, Currency $currency, int $expectedCents): void
    {
        $money = Money::fromString($input, $currency);
        $this->assertSame($expectedCents, $money->amount);
    }

    // DATA PROVIDER for invalid values

    /**
     * @return iterable<string, array{0: string, 1: Currency}>
     */
    public static function invalidStringValues(): iterable
    {
        yield 'JPY with fraction' => ['19.5', Currency::JPY];
        yield 'letters'          => ['abc', Currency::USD];
        yield 'empty string'     => ['', Currency::USD];
        // yield 'too precise'   => ['1.99', Currency::USD];
        yield 'double dot'       => ['1.2.3', Currency::USD];
        yield 'too many decimals' => ['19.999', Currency::USD];
    }

    #[DataProvider('invalidStringValues')]
    public function test_from_string_rejects_invalid_values(string $input, Currency $currency): void
    {
        $this->expectException(InvalidAmount::class);
        Money::fromString($input, $currency);
    }

    // different courses
    public function test_add_throws_exception_on_currency_mismatch(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $eur = Money::fromCents(100, Currency::EUR);

        $this->expectException(CurrencyMismatchException::class);
        $usd->add($eur);
    }

    public function test_subtract_throws_exception_on_currency_mismatch(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $eur = Money::fromCents(100, Currency::EUR);

        $this->expectException(CurrencyMismatchException::class);
        $usd->subtract($eur);
    }


    // Rounded (HalfEven / Bankers Rounding)

    /**
     * @return iterable<string, array{0: int, 1: string, 2: int}>
     */
    public static function roundingCases(): iterable
    {
        yield '15 * 0.5 -> 7.5 -> 8 (even)' => [15, '0.5', 8];
        yield '25 * 0.5 -> 12.5 -> 12 (even)' => [25, '0.5', 12];
    }

    #[DataProvider('roundingCases')]
    public function test_multiply_uses_bankers_rounding(int $initialCents, string $multiplier, int $expectedCents): void
    {
        $usd = Money::fromCents($initialCents, Currency::USD);
        $result = $usd->multiply($multiplier);

        $this->assertSame($expectedCents, $result->amount);
    }

    // Immutable
    public function test_operations_are_immutable(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $new = $usd->add(Money::fromCents(50, Currency::USD));

        $this->assertSame(100, $usd->amount);
        $this->assertSame(150, $new->amount);
        $this->assertNotSame($usd, $new);
    }

    // negative and absolute

    public function test_negate_and_absolute_calculations(): void
    {
        $money = Money::fromCents(10, Currency::USD);

        $negate = $money->negate();
        $this->assertSame(-10, $negate->amount);

        $absolute = $negate->absolute();
        $this->assertSame(10, $absolute->amount);
    }


    // (equals)

    /**
     * @return iterable<string, array{0: int, 1: Currency, 2: int, 3: Currency, 4: bool}>
     */
    public static function equalsCases(): iterable
    {
        yield 'same amount and currency' => [100, Currency::USD, 100, Currency::USD, true];
        yield 'different amount'         => [200, Currency::USD, 100, Currency::USD, false];
        yield 'different currency'       => [100, Currency::USD, 100, Currency::UAH, false];
    }

    #[DataProvider('equalsCases')]
    public function test_equals(int $amountA, Currency $currencyA, int $amountB, Currency $currencyB, bool $expected): void
    {
        $a = Money::fromCents($amountA, $currencyA);
        $b = Money::fromCents($amountB, $currencyB);

        $this->assertSame($expected, $a->equals($b));
    }


    // (isGreaterThan)
    public function test_greater_than_throws_exception_on_currency_mismatch(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $uah = Money::fromCents(100, Currency::UAH);

        $this->expectException(CurrencyMismatchException::class);
        $usd->isGreaterThan($uah);
    }

    public function test_greater_than_returns_true_when_larger(): void
    {
        $a = Money::fromCents(200, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertTrue($a->isGreaterThan($b));
    }

    public function test_greater_than_returns_false_when_smaller(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(200, Currency::USD);
        $this->assertFalse($a->isGreaterThan($b));
    }

    public function test_greater_than_returns_false_when_equal(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertFalse($a->isGreaterThan($b));
    }

    //isLessThan
    public function test_less_than_throws_exception_on_currency_mismatch(): void
    {
        $usd = Money::fromCents(100, Currency::USD);
        $uah = Money::fromCents(100, Currency::UAH);

        $this->expectException(CurrencyMismatchException::class);
        $usd->isLessThan($uah);
    }
    public function test_less_than_returns_true_when_smaller(): void
    {
        $a = Money::fromCents(200, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertTrue($b->isLessThan($a));
    }
    public function test_less_than_returns_false_when_larger(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(200, Currency::USD);
        $this->assertFalse($b->isLessThan($a));
    }
    public function test_less_than_returns_false_when_equal(): void
    {
        $a = Money::fromCents(100, Currency::USD);
        $b = Money::fromCents(100, Currency::USD);
        $this->assertFalse($a->isLessThan($b));
    }
    public function test_to_decimal_string_basic_USD(): void
    {
        $string = Money::fromCents(1999, Currency::USD);
        $this->assertSame('19.99', $string->toDecimalString());
    }
    public function test_to_decimal_string_basic_JPY(): void
    {
        $string = Money::fromCents(1999, Currency::JPY);
        $this->assertSame('1999', $string->toDecimalString());
    }
    public function test_to_decimal_string_negative(): void
    {
        $negative = Money::fromCents(-5, Currency::USD);
        $this->assertSame('-0.05', $negative->toDecimalString());
    }
    public function test_to_format_USD(): void
    {
        $money = Money::fromString('100', Currency::USD);
        $this->assertSame('100.00 $', $money->format());
    }

    public function test_to_format_JPY(): void
    {
        $money = Money::fromString('100', Currency::JPY);
        $this->assertSame('100 ¥', $money->format());
    }
    public function test_to_coppied_with_currency(): void
    {
        $original = Money::fromString('100', Currency::USD);
        $uah = $original->withCurrency(Currency::UAH);
        $this->assertNotSame($original, $uah);
        $this->assertSame($original->toDecimalString(), $uah->toDecimalString());
    }
}
