<?php

namespace Draw\Bundle\PusherBundle\Tests\IntegrationTests;

use Draw\Bundle\PusherBundle\Authenticator\ChannelAuthenticatorPresenceInterface;
use Draw\Bundle\PusherBundle\Controller\AuthController;
use Draw\Bundle\PusherBundle\Tests\DrawPusherTestKernel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Pusher\Pusher;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
final class PusherServiceDefinitionTest extends TestCase
{
    private ?KernelInterface $kernel = null;

    protected function tearDown(): void
    {
        if (null !== $this->kernel) {
            $this->kernel->shutdown();
            $this->kernel = null;
            restore_exception_handler();
        }

        parent::tearDown();
    }

    #[DataProvider('bundleServiceDefinitionDataProvider')]
    public function testBundleServiceDefinitions(string $serviceId, string $className, bool $expectException): void
    {
        $container = $this->getConfiguredContainer($serviceId, ['auth_service_id' => ChannelAuthenticator::class]);
        $service = $container->get($serviceId);

        self::assertInstanceOf($className, $service);
    }

    #[DataProvider('bundleServiceDefinitionDataProvider')]
    public function testBundleMinimalServiceDefinitions(string $serviceId, string $className, bool $expectException): void
    {
        if ($expectException) {
            $this->expectException(ServiceNotFoundException::class);
        }

        $container = $this->getConfiguredContainer($serviceId);
        $service = $container->get($serviceId);

        self::assertInstanceOf($className, $service);
    }

    public static function bundleServiceDefinitionDataProvider(): iterable
    {
        $prefix = 'draw.pusher.';

        yield [$prefix.'pusher',  Pusher::class, false];
        yield [AuthController::class, AuthController::class, true];
    }

    private function getConfiguredContainer(string $serviceId, array $bundleConfig = []): ContainerInterface
    {
        // Make private services public
        $pass = new DefinitionPublicCompilerPass();
        $pass->definition = $serviceId;

        $kernel = new PusherServiceDefinitionTestKernel(null, [], $bundleConfig);
        $kernel->compilerPass = $pass;
        $kernel->boot();
        $this->kernel = $kernel;

        return $kernel->getContainer();
    }
}

/**
 * @internal
 */
final class ChannelAuthenticator implements ChannelAuthenticatorPresenceInterface
{
    public function authenticate(string $socketId, string $channelName): bool
    {
        return true;
    }

    public function getUserInfo(): array
    {
        return [];
    }

    public function getUserId(): string
    {
        return 'user-id';
    }
}

/**
 * @internal
 */
final class DefinitionPublicCompilerPass implements CompilerPassInterface
{
    public $definition;

    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition($this->definition)) {
            $container->getDefinition($this->definition)
                ->setPublic(true)
            ;
        }
    }
}

/**
 * @internal
 */
final class PusherServiceDefinitionTestKernel extends DrawPusherTestKernel
{
    public $compilerPass;

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this->compilerPass);
        $container->register(ChannelAuthenticator::class);
    }
}
