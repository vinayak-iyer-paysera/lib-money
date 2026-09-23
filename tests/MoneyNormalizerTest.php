<?php

namespace Evp\Component\Money\Tests;

use Evp\Component\Money\Money;
use Evp\Component\Money\MoneyNormalizer;
use Paysera\Component\Serializer\Exception\InvalidDataException;
use Paysera\Component\Serializer\Normalizer\DenormalizerInterface;
use Paysera\Component\Serializer\Normalizer\NormalizerInterface;
use PHPUnit\Framework\TestCase;

class MoneyNormalizerTest extends TestCase
{
    public function testImplementsBothPayseraSerializerInterfaces()
    {
        $normalizer = new MoneyNormalizer();

        $this->assertInstanceOf(NormalizerInterface::class, $normalizer);
        $this->assertInstanceOf(DenormalizerInterface::class, $normalizer);
    }

    /**
     * @param array<string, string|int> $data
     * @param string $amount
     * @param string $currency
     *
     * @dataProvider mapToEntityProvider
     */
    public function testMapToEntity(array $data, $amount, $currency)
    {
        $money = (new MoneyNormalizer())->mapToEntity($data);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($amount, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
    }

    /**
     * @return array[]
     */
    public function mapToEntityProvider()
    {
        return array(
            array(array('amount' => '10.50', 'currency' => 'EUR'), '10.50', 'EUR'),
            array(array('amount' => '10,50', 'currency' => 'eur'), '10.50', 'EUR'),
            array(array('amount' => 7, 'currency' => 'JPY'), '7', 'JPY'),
            array(array('amount' => '-0.01', 'currency' => 'USD'), '-0.01', 'USD'),
        );
    }

    /**
     * @param array<string, string> $data
     * @param string $message
     *
     * @dataProvider mapToEntityRejectsProvider
     */
    public function testMapToEntityRejects(array $data, $message)
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage($message);

        (new MoneyNormalizer())->mapToEntity($data);
    }

    /**
     * @return array[]
     */
    public function mapToEntityRejectsProvider()
    {
        return array(
            'no amount' => array(array('currency' => 'EUR'), 'Amount is not set'),
            'no currency' => array(array('amount' => '1'), 'Currency is not set'),
            'not a number' => array(array('amount' => '1.2.3', 'currency' => 'EUR'), 'Invalid amount specified'),
            'unknown currency' => array(array('amount' => '1', 'currency' => 'ZZZ'), 'Invalid amount specified'),
            'more decimals than the currency has' => array(
                array('amount' => '10.555', 'currency' => 'EUR'),
                'Too small fraction for the amount specified',
            ),
        );
    }

    public function testMapFromCents()
    {
        $money = (new MoneyNormalizer())->mapFromCents(1050, 'EUR');

        $this->assertSame('10.500000', $money->getAmount());
        $this->assertSame('EUR', $money->getCurrency());
    }

    /**
     * @param mixed $amountInCents
     * @param string $currency
     * @param string $message
     *
     * @dataProvider mapFromCentsRejectsProvider
     */
    public function testMapFromCentsRejects($amountInCents, $currency, $message)
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage($message);

        (new MoneyNormalizer())->mapFromCents($amountInCents, $currency);
    }

    /**
     * @return array[]
     */
    public function mapFromCentsRejectsProvider()
    {
        return array(
            'not an integer' => array('10.5', 'EUR', 'Invalid amount specified'),
            'cents of a currency without decimals' => array(1, 'JPY', 'Too small fraction for the amount specified'),
        );
    }

    /**
     * @param int $amountInMinorUnits
     * @param string $currency
     * @param string $amount
     *
     * @dataProvider mapFromMinorUnitsProvider
     */
    public function testMapFromMinorUnits($amountInMinorUnits, $currency, $amount)
    {
        $money = (new MoneyNormalizer())->mapFromMinorUnits($amountInMinorUnits, $currency);

        $this->assertSame($amount, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
    }

    /**
     * @return array[]
     */
    public function mapFromMinorUnitsProvider()
    {
        return array(
            array(1050, 'EUR', '10.500000'),
            array(1050, 'JPY', '1050.000000'),
            array(1050, 'KWD', '1.050000'),
        );
    }

    public function testMapFromMinorUnitsRejectsANonInteger()
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Invalid amount specified');

        (new MoneyNormalizer())->mapFromMinorUnits('10.5', 'EUR');
    }

    /**
     * @param Money $money
     * @param array<string, string> $expected
     *
     * @dataProvider mapFromEntityProvider
     */
    public function testMapFromEntity(Money $money, array $expected)
    {
        $this->assertSame($expected, (new MoneyNormalizer())->mapFromEntity($money));
    }

    /**
     * @return array[]
     */
    public function mapFromEntityProvider()
    {
        return array(
            array(new Money('10.5', 'EUR'), array('amount' => '10.50', 'currency' => 'EUR')),
            'formatted to the decimals of the currency (2.0.0)' => array(
                new Money('42.421234', 'EUR'),
                array('amount' => '42.42', 'currency' => 'EUR'),
            ),
            array(new Money('1050', 'JPY'), array('amount' => '1050', 'currency' => 'JPY')),
        );
    }

    public function testMapFromEntityRejectsAnythingButMoney()
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Provided argument is not a Money object.');

        (new MoneyNormalizer())->mapFromEntity(array('amount' => '1', 'currency' => 'EUR'));
    }

    public function testRoundTrip()
    {
        $normalizer = new MoneyNormalizer();
        $money = new Money('1234.56', 'EUR');

        $this->assertTrue($normalizer->mapToEntity($normalizer->mapFromEntity($money))->isEqual($money));
    }
}
