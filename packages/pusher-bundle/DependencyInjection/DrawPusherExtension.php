<?php

namespace Draw\Bundle\PusherBundle\DependencyInjection;

use Draw\Bundle\PusherBundle\Controller\AuthController;
use Pusher\Pusher;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Extension\AbstractExtension;

/**
 * DrawPusherExtension.
 */
class DrawPusherExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $pusherConfigurationDefinition = $container->getDefinition('draw.pusher.pusher_configuration');
        $pusherConfigurationDefinition->setArgument(0, $config);

        if (null === $config['auth_service_id']) {
            $container->removeDefinition(AuthController::class);
        } else {
            $controllerDefinition = $container->getDefinition(AuthController::class);
            $controllerDefinition->setArgument(1, new Reference($config['auth_service_id']));
        }

        $container->setAlias(Pusher::class, 'draw.pusher.pusher');

        if (class_exists(AbstractExtension::class)) {
            $loader->load('twig.xml');
        }
    }
}
