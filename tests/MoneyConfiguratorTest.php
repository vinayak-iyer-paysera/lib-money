<?php

namespace Evp\Component\Money\Tests;

use Evp\Component\Money\MoneyConfigurator;
use Evp\Component\Money\MoneyNormalizer;
use Paysera\Component\DependencyInjection\ConfiguratorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MoneyConfiguratorTest extends TestCase
{
    public function testIsAConfiguratorOfPayseraDependencyInjection()
    {
        $this->assertInstanceOf(ConfiguratorInterface::class, new MoneyConfigurator());
    }

    public function testLoadRegistersTheMoneyNormalizer()
    {
        $container = new ContainerBuilder();

        (new MoneyConfigurator())->load($container);

        $this->assertSame(
            MoneyNormalizer::class,
            $container->getDefinition('evp_money.normalizer.money')->getClass()
        );
        $this->assertInstanceOf(MoneyNormalizer::class, $this->compileAndGet($container));
    }

    /**
     * The definition is private on Symfony 3.4 and later, so a compiled container keeps it only when something refers to it.
     * Resource tracking is off: it needs symfony/config's resource classes, and the oldest symfony/config the dependencies allow (2.0)
     * declares no autoloading.
     */
    private function compileAndGet(ContainerBuilder $container)
    {
        $container->setAlias('test.evp_money.normalizer.money', new Alias('evp_money.normalizer.money', true));
        $container->setResourceTracking(false);
        $container->compile();

        return $container->get('test.evp_money.normalizer.money');
    }
}
