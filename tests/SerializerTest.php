<?php

namespace Evp\Component\Money\Tests;

use Evp\Component\Money\Money;
use Evp\Component\Money\Serializer;
use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    public function testMetadataDirectoryHoldsTheMoneyMapping()
    {
        $file = Serializer::getMetadataPath() . DIRECTORY_SEPARATOR . 'Money.xml';

        $this->assertFileExists($file);
        $this->assertSame(Money::class, (string) simplexml_load_file($file)->class['name']);
    }

    public function testNamespacePrefixIsTheNamespaceOfMoney()
    {
        $this->assertSame('Evp\Component\Money', Serializer::getNamespacePrefix());
        $this->assertStringStartsWith(Serializer::getNamespacePrefix() . '\\', Money::class);
    }
}
