<?php

namespace Evp\Component\Money\Tests;

use Evp\Component\Money\Money;
use Evp\Component\Money\MoneyFactory;
use Maba\Component\Math\BcMath;
use Maba\Component\Math\Math;
use Maba\Component\Math\NumberValidator;
use Maba\Component\Monetary\Exception\InvalidCurrencyException;
use Maba\Component\Monetary\Factory\MoneyFactoryInterface;
use Maba\Component\Monetary\Information\MoneyInformationProvider;
use Maba\Component\Monetary\Validation\MoneyValidator;
use PHPUnit\Framework\TestCase;

class MoneyFactoryTest extends TestCase
{
    public function testImplementsTheMonetaryFactoryInterface()
    {
        $this->assertInstanceOf(MoneyFactoryInterface::class, $this->createFactory());
    }

    public function testCreate()
    {
        $money = $this->createFactory()->create('10.50', 'EUR');

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame('10.50', $money->getAmount());
        $this->assertSame('EUR', $money->getCurrency());
    }

    public function testCreateZero()
    {
        $money = $this->createFactory()->createZero('EUR');

        $this->assertSame('0', $money->getAmount());
        $this->assertSame('EUR', $money->getCurrency());
    }

    public function testCreateFromCents()
    {
        $money = $this->createFactory()->createFromCents(1050, 'EUR');

        $this->assertTrue($money->isEqual(new Money('10.50', 'EUR')));
        $this->assertSame('EUR', $money->getCurrency());
    }

    public function testCreateValidatesTheCurrencyAgainstTheInformationProvider()
    {
        $this->expectException(InvalidCurrencyException::class);

        $this->createFactory(array('USD'))->create('1', 'EUR');
    }

    /**
     * @param string[]|null $availableCurrencies
     *
     * @return MoneyFactory
     */
    private function createFactory($availableCurrencies = null)
    {
        $numberValidator = new NumberValidator();
        $math = new Math(new BcMath(6, $numberValidator));

        return new MoneyFactory(
            $math,
            new MoneyValidator($math, new MoneyInformationProvider(null, $availableCurrencies), $numberValidator)
        );
    }
}
