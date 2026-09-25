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
     * @param array<string, string> $expected
     *
     * @dataProvider mapToEntityProvider
     */
    public function testMapToEntity(array $data, array $expected)
    {
        $money = (new MoneyNormalizer())->mapToEntity($data);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame($expected, array('amount' => $money->getAmount(), 'currency' => $money->getCurrency()));
    }

    /**
     * @return array[]
     */
    public function mapToEntityProvider()
    {
        return array(
            'decimal point' => array(
                array('amount' => '10.50', 'currency' => 'EUR'),
                array('amount' => '10.50', 'currency' => 'EUR'),
            ),
            'decimal comma and lower-case currency' => array(
                array('amount' => '10,50', 'currency' => 'eur'),
                array('amount' => '10.50', 'currency' => 'EUR'),
            ),
            'integer amount' => array(
                array('amount' => 7, 'currency' => 'JPY'),
                array('amount' => '7', 'currency' => 'JPY'),
            ),
            'negative amount' => array(
                array('amount' => '-0.01', 'currency' => 'USD'),
                array('amount' => '-0.01', 'currency' => 'USD'),
            ),
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

    /**
     * @param string $method
     * @param int $amount
     * @param string $currency
     * @param array<string, string> $expected
     *
     * @dataProvider mapFromCentsAndMinorUnitsProvider
     */
    public function testMapFromCentsAndMinorUnits($method, $amount, $currency, array $expected)
    {
        $money = (new MoneyNormalizer())->$method($amount, $currency);

        $this->assertSame($expected, array('amount' => $money->getAmount(), 'currency' => $money->getCurrency()));
    }

    /**
     * @return array[]
     */
    public function mapFromCentsAndMinorUnitsProvider()
    {
        return array(
            'cents' => array('mapFromCents', 1050, 'EUR', array('amount' => '10.500000', 'currency' => 'EUR')),
            'minor units of a currency with two decimals' => array(
                'mapFromMinorUnits',
                1050,
                'EUR',
                array('amount' => '10.500000', 'currency' => 'EUR'),
            ),
            'minor units of a currency without decimals' => array(
                'mapFromMinorUnits',
                1050,
                'JPY',
                array('amount' => '1050.000000', 'currency' => 'JPY'),
            ),
            'minor units of a currency with three decimals' => array(
                'mapFromMinorUnits',
                1050,
                'KWD',
                array('amount' => '1.050000', 'currency' => 'KWD'),
            ),
        );
    }

    /**
     * @param string $method
     * @param mixed $amount
     * @param string $currency
     * @param string $message
     *
     * @dataProvider mapFromCentsAndMinorUnitsRejectsProvider
     */
    public function testMapFromCentsAndMinorUnitsRejects($method, $amount, $currency, $message)
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage($message);

        (new MoneyNormalizer())->$method($amount, $currency);
    }

    /**
     * @return array[]
     */
    public function mapFromCentsAndMinorUnitsRejectsProvider()
    {
        return array(
            'cents that are not an integer' => array('mapFromCents', '10.5', 'EUR', 'Invalid amount specified'),
            'cents of a currency without decimals' => array(
                'mapFromCents',
                1,
                'JPY',
                'Too small fraction for the amount specified',
            ),
            'minor units that are not an integer' => array(
                'mapFromMinorUnits',
                '10.5',
                'EUR',
                'Invalid amount specified',
            ),
        );
    }

    /**
     * @param string $amount
     * @param string $currency
     * @param array<string, string> $expected
     *
     * @dataProvider mapFromEntityProvider
     */
    public function testMapFromEntity($amount, $currency, array $expected)
    {
        $this->assertSame($expected, (new MoneyNormalizer())->mapFromEntity(new Money($amount, $currency)));
    }

    /**
     * @return array[]
     */
    public function mapFromEntityProvider()
    {
        return array(
            'padded to the decimals of the currency' => array(
                '10.5',
                'EUR',
                array('amount' => '10.50', 'currency' => 'EUR'),
            ),
            'formatted to the decimals of the currency (2.0.0)' => array(
                '42.421234',
                'EUR',
                array('amount' => '42.42', 'currency' => 'EUR'),
            ),
            'currency without decimals' => array('1050', 'JPY', array('amount' => '1050', 'currency' => 'JPY')),
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
