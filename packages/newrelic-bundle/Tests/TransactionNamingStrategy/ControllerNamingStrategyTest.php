<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\TransactionNamingStrategy;

use Draw\Bundle\NewRelicBundle\TransactionNamingStrategy\ControllerNamingStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ControllerNamingStrategyTest extends TestCase
{
    public function testControllerAsString(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', 'SomeBundle:Some:SomeAction');

        $strategy = new ControllerNamingStrategy();
        static::assertSame('SomeBundle:Some:SomeAction', $strategy->getTransactionName($request));
    }

    public function testControllerAsClosure(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', static function () {
        });

        $strategy = new ControllerNamingStrategy();
        static::assertSame('Closure controller', $strategy->getTransactionName($request));
    }

    public function testControllerAsCallback(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', [$this, 'testControllerAsString']);

        $strategy = new ControllerNamingStrategy();
        static::assertSame('Callback controller: Draw\Bundle\NewRelicBundle\Tests\TransactionNamingStrategy\ControllerNamingStrategyTest::testControllerAsString()', $strategy->getTransactionName($request));
    }

    public function testControllerUnknown(): void
    {
        $request = new Request();

        $strategy = new ControllerNamingStrategy();
        static::assertSame('Unknown Symfony controller', $strategy->getTransactionName($request));
    }
}
