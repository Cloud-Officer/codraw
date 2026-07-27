<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests;

use Draw\Bundle\NewRelicBundle\DrawNewRelicBundle;
use Draw\Bundle\NewRelicBundle\NewRelic\AdaptiveInteractor;
use Draw\Bundle\NewRelicBundle\NewRelic\BlackholeInteractor;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractor;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test to see if the bundle can run.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @internal
 */
class BundleInitializationTest extends TestCase
{
    protected function getBundleClass(): string
    {
        return DrawNewRelicBundle::class;
    }

    public function testInitBundle(): void
    {
        $kernel = new AppKernel(uniqid('cache'));
        $kernel->boot();

        // Get the container
        $container = $kernel->getContainer();

        $services = [
            NewRelicInteractorInterface::class => AdaptiveInteractor::class,
            BlackholeInteractor::class,
            NewRelicInteractor::class,
        ];

        // Test if you services exists
        foreach ($services as $id => $class) {
            if (\is_int($id)) {
                $id = $class;
            }
            static::assertTrue($container->has($id));
            $service = $container->get($id);
            static::assertInstanceOf($class, $service);
        }

        $kernel->shutdown();

        // Booting in debug mode registers Symfony's ErrorHandler globally.
        restore_exception_handler();
    }
}
