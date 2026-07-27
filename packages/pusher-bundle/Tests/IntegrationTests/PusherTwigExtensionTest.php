<?php

namespace Draw\Bundle\PusherBundle\Tests\IntegrationTests;

use Draw\Bundle\PusherBundle\Tests\DrawPusherTestKernel;
use Draw\Bundle\PusherBundle\Twig\PusherExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Twig\Environment;

/**
 * @internal
 */
final class PusherTwigExtensionTest extends TestCase
{
    public function testExtensionIsLoaded(): void
    {
        $kernel = new DrawPusherTestKernel(null, [new TwigBundle()], ['key' => 'test_key']);
        $kernel->boot();

        $container = $kernel->getContainer();

        /** @var Environment $twig */
        $twig = $container->get(Environment::class);

        self::assertInstanceOf(PusherExtension::class, $twig->getExtension(PusherExtension::class));
        self::assertStringContainsString('test_key', $twig->render('sample.html.twig'));

        $kernel->shutdown();
        restore_exception_handler();
    }
}
