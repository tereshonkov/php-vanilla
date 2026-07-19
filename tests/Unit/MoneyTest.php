<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Money\Currency;
use App\Money\Money;
use App\Money\MoneyExceptions\CurrencyMismatchException;
use App\Money\MoneyExceptions\InvalidAllocation;
use App\Money\MoneyExceptions\InvalidAmount;
use App\Money\MoneyExceptions\MoneyOverflowException;
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

    public function test_absolute_calculations(): void
    {
        $money = Money::fromCents(10, Currency::USD);

        $negate = $money->negate();
        $absolute = $negate->absolute();

        $this->assertSame(10, $absolute->amount);
    }
    public function test_negate_calculations(): void
    {
        $money = Money::fromCents(10, Currency::USD);

        $negate = $money->negate();
        $this->assertSame(-10, $negate->amount);
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
    public function test_currency_decimals_and_symbols(): void
    {
        $this->assertSame(2, Currency::EUR->decimals());
        $this->assertSame('€', Currency::EUR->symbol());

        $this->assertSame('₴', Currency::UAH->symbol());
    }
    public function test_to_coppied_with_currency(): void
    {
        $original = Money::fromString('100', Currency::USD);
        $uah = $original->withCurrency(Currency::UAH);
        $this->assertNotSame($original, $uah);
        $this->assertSame($original->toDecimalString(), $uah->toDecimalString());
    }
    public function test_absolute_overflow_with_php_int_min(): void
    {
        $money = Money::fromCents(PHP_INT_MIN, Currency::USD);

        $this->expectException(MoneyOverflowException::class);
        $money->absolute();
    }

    public function test_split_3_slices(): void
    {
        $sum = Money::fromCents(100, Currency::USD);
        $split = $sum->split(3); //[34, 33, 33]

        $this->assertCount(3, $split);
        $this->assertSame(34, $split[0]->amount);
        $this->assertSame(33, $split[1]->amount);
        $this->assertSame(33, $split[2]->amount);
    }
    public function test_split_4_slices(): void
    {
        $sum = Money::fromCents(10, Currency::USD);
        $split = $sum->split(4); //[3, 3, 2, 2]

        $this->assertCount(4, $split);
        $this->assertSame(3, $split[0]->amount);
        $this->assertSame(3, $split[1]->amount);
        $this->assertSame(2, $split[2]->amount);
        $this->assertSame(2, $split[3]->amount);
    }
    public function test_split_1_slices(): void
    {
        $sum = Money::fromCents(100, Currency::USD);
        $split = $sum->split(1); //100

        $this->assertCount(1, $split);
        $this->assertSame(100, $split[0]->amount);
    }
    public function test_split_0_slices_exception(): void
    {
        $sum = Money::fromCents(100, Currency::USD);

        $this->expectException(InvalidAllocation::class);
        $sum->split(0);
    }
    public function test_split_negative_slices(): void
    {
        $num = Money::fromCents(100, Currency::USD);
        $this->expectException(InvalidAllocation::class);
        $num->split(-1);
    }
    public function test_split_negative_amount_preserves_sum(): void
    {
        $money = Money::fromCents(-10, Currency::USD);
        $parts = $money->split(3);

        $sum = array_sum(array_map(fn(Money $m) => $m->amount, $parts));
        $this->assertSame(-10, $sum);
    }
    public function test_split_never_loses_money(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $amount = random_int(-100000, 100000);
            $slices = random_int(1, 20);

            $parts = Money::fromCents($amount, Currency::USD)->split($slices);
            $sum = array_sum(array_map(fn(Money $m) => $m->amount, $parts));

            $this->assertSame(
                $amount,
                $sum,
                "Lost money: amount=$amount, slices=$slices",
            );
        }
    }
    public function test_split_by_zero_slices_throws_exception(): void
    {
        $money = Money::fromCents(100, Currency::USD);

        $this->expectException(InvalidAllocation::class);
        $money->split(0);
    }

    /**
     * @return array<string, array{0: int, 1: array<int>, 2: array<int>}>
     */
    public static function allocateDataProvider(): array
    {
        return [
            '100 cents with 1:3:1 ratios' => [100, [1, 3, 1], [20, 60, 20]],
            '100 cents with 7:3 ratios'   => [100, [7, 3], [70, 30]],
            '99 cents with 1:1:1 ratios'  => [99, [1, 1, 1], [33, 33, 33]],
            'One ratio takes everything'  => [100, [1], [100]],
            'Zero ratio gets nothing'     => [100, [1, 0], [100, 0]],
            '10 cents with 1:1:1 (remainder)' => [10, [1, 1, 1], [4, 3, 3]],
        ];
    }

    #[DataProvider('allocateDataProvider')]
    public function test_allocate_proportional_cases(int $amount, array $ratios, array $expectedCents): void
    {
        $money = Money::fromCents($amount, Currency::USD);
        $result = $money->allocate(...$ratios);

        $this->assertCount(count($expectedCents), $result);
        foreach ($expectedCents as $key => $expected) {
            $this->assertSame($expected, $result[$key]->amount);
        }
    }

    public function test_allocate_rounding_to_zero_boundary(): void
    {
        $money = Money::fromCents(1, Currency::USD);
        $result = $money->allocate(1, 1);

        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->amount);
        $this->assertSame(0, $result[1]->amount);
    }

    /**
     * (Exceptions)
     */
    public function test_allocate_empty_arguments_exception(): void
    {
        $money = Money::fromCents(100, Currency::USD);
        $this->expectException(InvalidAllocation::class);
        $money->allocate();
    }

    public function test_allocate_zero_sum_exception(): void
    {
        $money = Money::fromCents(100, Currency::USD);
        $this->expectException(InvalidAllocation::class);
        $money->allocate(0, 0, 0);
    }

    public function test_allocate_negative_ratio_exception(): void
    {
        $money = Money::fromCents(100, Currency::USD);
        $this->expectException(InvalidAllocation::class);
        $money->allocate(1, -1, 2);
    }

    /**
     * Property-based
     */
    public function test_allocate_never_loses_money_including_negative_amounts(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $amount = random_int(-100000, 100000);

            $ratiosCount = random_int(1, 10);
            $ratios = [];
            for ($j = 0; $j < $ratiosCount; $j++) {
                $ratios[] = random_int(0, 50);
            }

            if (array_sum($ratios) === 0) {
                $ratios[0] = 1;
            }

            $money = Money::fromCents($amount, Currency::USD);
            $parts = $money->allocate(...$ratios);

            $totalDistributed = 0;
            foreach ($parts as $part) {
                $totalDistributed += $part->amount;
            }

            $this->assertSame(
                $amount,
                $totalDistributed,
                "Money loss detected: amount=$amount, ratios=" . implode(',', $ratios),
            );
        }
    }
}
