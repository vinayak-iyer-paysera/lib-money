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
        $all = $aggregatedMoney->getAll();
        $this->assertCount(2, $all);
        $this->assertSame(array(0, 1), array_keys($all));
        $this->assertSame('4.000000', $all[0]->getAmount());
        $this->assertSame('EUR', $all[0]->getCurrency());
        $this->assertSame('2.000000', $all[1]->getAmount());
        $this->assertSame('USD', $all[1]->getCurrency());
        $this->assertNull($aggregatedMoney->get('GBP'));
    }
}
