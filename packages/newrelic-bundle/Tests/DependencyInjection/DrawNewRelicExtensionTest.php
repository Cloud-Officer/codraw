<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\DependencyInjection;

use Draw\Bundle\NewRelicBundle\DependencyInjection\DrawNewRelicExtension;
use Draw\Bundle\NewRelicBundle\Listener\CommandListener;
use Draw\Bundle\NewRelicBundle\Listener\DeprecationListener;
use Draw\Bundle\NewRelicBundle\Listener\ExceptionListener;
use Draw\Bundle\NewRelicBundle\NewRelic\BlackholeInteractor;
use Draw\Bundle\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Draw\Bundle\NewRelicBundle\Twig\NewRelicExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\ContainerHasParameterConstraint;
use PHPUnit\Framework\Constraint\LogicalNot;

/**
 * @internal
 */
class DrawNewRelicExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new DrawNewRelicExtension()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', []);
    }

    public function testDefaultConfiguration(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(NewRelicExtension::class);
        $this->assertContainerBuilderHasService(CommandListener::class);
        $this->assertContainerBuilderHasService(ExceptionListener::class);
    }

    public function testAlternativeConfiguration(): void
    {
        $this->load([
            'exceptions' => false,
            'commands' => false,
            'twig' => false,
        ]);

        $this->assertContainerBuilderNotHasService(NewRelicExtension::class);
        $this->assertContainerBuilderNotHasService(CommandListener::class);
        $this->assertContainerBuilderNotHasService(ExceptionListener::class);
    }

    public function testDeprecation(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(DeprecationListener::class);
    }

    public function testMonolog(): void
    {
        $this->load(['monolog' => true]);

        $this->assertContainerBuilderHasParameter('draw.new_relic.monolog');
        $this->assertContainerBuilderHasParameter('draw.new_relic.application_name');
        $this->assertContainerBuilderHasService('draw.new_relic.logs_handler');
    }

    public function testMonologDisabled(): void
    {
        $this->load(['monolog' => false]);

        static::assertThat(
            $this->container,
            new LogicalNot(new ContainerHasParameterConstraint('draw.new_relic.monolog', null, false))
        );
    }

    public function testConfigDisabled(): void
    {
        $this->load([
            'enabled' => false,
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, BlackholeInteractor::class);
    }

    public function testConfigDisabledWithInteractor(): void
    {
        $this->load([
            'enabled' => false,
            'interactor' => 'draw.new_relic.interactor.adaptive',
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, BlackholeInteractor::class);
    }

    public function testConfigEnabledWithInteractor(): void
    {
        $this->load([
            'enabled' => true,
            'interactor' => 'draw.new_relic.interactor.adaptive',
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, 'draw.new_relic.interactor.adaptive');
    }
}
