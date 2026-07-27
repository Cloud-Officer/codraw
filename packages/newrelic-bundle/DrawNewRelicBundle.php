<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle;

use Draw\Bundle\NewRelicBundle\DependencyInjection\Compiler\MonologHandlerPass;
use Draw\Bundle\NewRelicBundle\Listener\DeprecationListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DrawNewRelicBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MonologHandlerPass());
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->container->has(DeprecationListener::class)) {
            $this->container->get(DeprecationListener::class)->register();
        }
    }

    public function shutdown(): void
    {
        if ($this->container->has(DeprecationListener::class)) {
            $this->container->get(DeprecationListener::class)->unregister();
        }

        parent::shutdown();
    }
}
