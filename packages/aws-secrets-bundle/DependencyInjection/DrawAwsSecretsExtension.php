<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\DependencyInjection;

use Aws\SecretsManager\SecretsManagerClient;
use Draw\Bundle\AwsSecretsBundle\AwsSecretsEnvVarProcessor;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsArrayEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsCachedEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class DrawAwsSecretsExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $configs = $this->processConfiguration($configuration, $configs);

        $container->setParameter('draw.aws_secrets.ttl', $configs['ttl']);
        $container->setParameter('draw.aws_secrets.ignore', $configs['ignore']);
        $container->setParameter('draw.aws_secrets.delimiter', $configs['delimiter']);

        $container->register('draw.aws_secrets.secrets_manager_client', SecretsManagerClient::class)
            ->setLazy(true)
            ->setPublic(false)
            ->addArgument($configs['client_config']['region'])
            ->addArgument($configs['client_config']['version'])
            ->addArgument($configs['client_config']['endpoint'])
            ->addArgument($configs['client_config']['credentials']['key'])
            ->addArgument($configs['client_config']['credentials']['secret'])
            ->setFactory([SecretsManagerClientFactory::class, 'createClient'])
        ;

        $container->setAlias('draw.aws_secrets.client', 'draw.aws_secrets.secrets_manager_client')
            ->setPublic(true)
        ;

        if ('apcu' === $configs['cache']) {
            $definition = new ChildDefinition('cache.adapter.apcu');
        } elseif ('filesystem' === $configs['cache']) {
            $definition = new ChildDefinition('cache.adapter.filesystem');
        } else {
            $definition = new Definition(ArrayAdapter::class);
        }

        $definition->addTag('cache.pool');
        $container->setDefinition('draw.aws_secrets.cache', $definition);

        $container->register('draw.aws_secrets.env_var_provider', AwsSecretsEnvVarProvider::class)
            ->setArgument('$secretsManagerClient', new Reference('draw.aws_secrets.client'))
            ->setPublic(false)
        ;

        $container->register('draw.aws_secrets.env_var_cached_provider', AwsSecretsCachedEnvVarProvider::class)
            ->setArgument('$cacheItemPool', new Reference('draw.aws_secrets.cache'))
            ->setArgument('$decorated', new Reference('draw.aws_secrets.env_var_provider'))
            ->setArgument('$ttl', $container->getParameter('draw.aws_secrets.ttl'))
            ->setPublic(false)
        ;

        $container->register('draw.aws_secrets.env_var_array_provider', AwsSecretsArrayEnvVarProvider::class)
            ->setArgument('$decorated', new Reference('draw.aws_secrets.env_var_cached_provider'))
            ->setPublic(false)
        ;

        $container->register('draw.aws_secrets.env_var_processor', AwsSecretsEnvVarProcessor::class)
            ->setArgument('$provider', new Reference('draw.aws_secrets.env_var_array_provider'))
            ->setArgument('$ignore', $container->getParameter('draw.aws_secrets.ignore'))
            ->setArgument('$delimiter', $container->getParameter('draw.aws_secrets.delimiter'))
            ->setPublic(false)
            ->addTag('container.env_var_processor')
        ;
    }
}
