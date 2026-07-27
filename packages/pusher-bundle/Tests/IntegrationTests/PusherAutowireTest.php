<?php

namespace Draw\Bundle\PusherBundle\Tests\IntegrationTests;

use Draw\Bundle\PusherBundle\Tests\DrawPusherTestKernel;
use PHPUnit\Framework\TestCase;
use Pusher\Pusher;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class PusherAutowireTest extends TestCase
{
    public function testPusherIsAutowiredByContainer(): void
    {
        $builder = new ContainerBuilder();
        $builder->autowire(PusherAutowireClass::class)
            ->setPublic(true)
        ;

        $kernel = new DrawPusherTestKernel($builder);
        $kernel->boot();

        $container = $kernel->getContainer();
        $service = $container->get(PusherAutowireClass::class);

        self::assertInstanceOf(PusherAutowireClass::class, $service);
        self::assertInstanceOf(Pusher::class, $service->getPusher());

        $kernel->shutdown();
        restore_exception_handler();
    }
}

/**
 * @internal
 */
final class PusherAutowireClass
{
    public function __construct(private Pusher $pusher)
    {
    }

    public function getPusher(): Pusher
    {
        return $this->pusher;
    }
}
