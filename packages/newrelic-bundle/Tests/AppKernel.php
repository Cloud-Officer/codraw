<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests;

use Draw\Bundle\NewRelicBundle\DrawNewRelicBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollection;

class AppKernel extends Kernel
{
    public function __construct(private string $cachePrefix)
    {
        parent::__construct($cachePrefix, true);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/draw-new-relic/'.$this->cachePrefix;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/draw-new-relic/log';
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DrawNewRelicBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'router' => [
                    'resource' => 'kernel:loadRoutes',
                    'type' => 'service',
                ],
            ]);

            $container->loadFromExtension('framework', [
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'session' => [
                    'cookie_secure' => 'auto',
                    'cookie_samesite' => 'lax',
                    'handler_id' => null,
                ],
                'php_errors' => [
                    'log' => true,
                ],
            ]);

            $container->addObjectResource($this);
        });
    }

    /**
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        return new RouteCollection();
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ($container->getDefinitions() as $id => $definition) {
                    if (preg_match('|Draw.*|i', $id)) {
                        $definition->setPublic(true);
                    }
                }

                foreach ($container->getAliases() as $id => $alias) {
                    if (preg_match('|Draw.*|i', $id)) {
                        $alias->setPublic(true);
                    }
                }
            }
        });

        return $container;
    }
}
