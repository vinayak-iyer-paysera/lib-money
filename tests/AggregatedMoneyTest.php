<?php

namespace Evp\Component\Money\Tests;

use Evp\Component\Money\AggregatedMoney;
use Evp\Component\Money\Money;
use PHPUnit\Framework\TestCase;

class AggregatedMoneyTest extends TestCase
{
    /**
     * @param Money $one
     * @param Money $two
     * @param Money $expectedAmount
     *
     * @dataProvider addProvider
     */
    public function testAdd(Money $one, Money $two, Money $expectedAmount)
    {
        $aggregateMoney = new AggregatedMoney();

        $aggregateMoney
            ->add($one)
            ->add($two);

        $this->assertEquals($aggregateMoney->get($expectedAmount->getCurrency()), $expectedAmount);
    }

    public function addProvider()
    {
        return array(
            array(new Money('1', 'EUR'), new Money('2', 'EUR'), new Money('3', 'EUR')),
            array(new Money('-10', 'EUR'), new Money('2', 'EUR'), new Money('-8', 'EUR')),
            array(new Money('-10', 'EUR'), new Money('-10', 'EUR'), new Money('-20', 'EUR')),
            array(new Money('1', 'EUR'), new Money('-2', 'EUR'), new Money('-1', 'EUR'))
        );
    }

    public function testAddAllAndGetAll()
    {
        $aggregatedMoney = new AggregatedMoney();

        $result = $aggregatedMoney->addAll(array(
            new Money('1', 'EUR'),
            new Money('2', 'USD'),
            new Money('3', 'EUR'),
        ));

        $this->assertSame($aggregatedMoney, $result);
        $this->assertSame(
            array(
                array('amount' => '4.000000', 'currency' => 'EUR'),
                array('amount' => '2.000000', 'currency' => 'USD'),
            ),
            array_map(
                function (Money $money) {
                    return array('amount' => $money->getAmount(), 'currency' => $money->getCurrency());
                },
                $aggregatedMoney->getAll()
            )
        );
        $this->assertNull($aggregatedMoney->get('GBP'));
    }
}
