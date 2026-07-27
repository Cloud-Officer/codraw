<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\DependencyInjection\Compiler;

use Draw\Bundle\NewRelicBundle\DependencyInjection\Compiler\MonologHandlerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
class MonologHandlerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MonologHandlerPass());
    }

    public function testProcessChannel(): void
    {
        $this->container->setParameter('draw.new_relic.monolog', ['level' => 100, 'channels' => ['type' => 'inclusive', 'elements' => ['app', 'foo']]]);
        $this->container->setParameter('draw.new_relic.application_name', 'app');
        $this->registerService('draw.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('draw.new_relic.logs_handler', 'draw.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('draw.new_relic.logs_handler')]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger.foo', 'pushHandler', [new Reference('draw.new_relic.logs_handler')]);
    }

    public function testProcessChannelAllChannels(): void
    {
        $this->container->setParameter('draw.new_relic.monolog', ['level' => 100, 'channels' => null]);
        $this->container->setParameter('draw.new_relic.application_name', 'app');
        $this->registerService('draw.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('draw.new_relic.logs_handler', 'draw.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('draw.new_relic.logs_handler')]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger.foo', 'pushHandler', [new Reference('draw.new_relic.logs_handler')]);
    }

    public function testProcessChannelExcludeChannels(): void
    {
        $this->container->setParameter('draw.new_relic.monolog', ['level' => 100, 'channels' => ['type' => 'exclusive', 'elements' => ['foo']]]);
        $this->container->setParameter('draw.new_relic.application_name', 'app');
        $this->registerService('draw.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('draw.new_relic.logs_handler', 'draw.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('draw.new_relic.logs_handler')]);
    }
}
