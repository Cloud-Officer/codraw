<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\DependencyInjection;

use Draw\Bundle\NewRelicBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\PrototypedArrayNode;

/**
 * @internal
 */
class ConfigurationTest extends TestCase
{
    public function testIgnoredRoutes(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree()
        ;
        static::assertInstanceOf(ArrayNode::class, $rootNode);
        $httpNode = $rootNode->getChildren()['http'];
        static::assertInstanceOf(ArrayNode::class, $httpNode);

        $ignoredRoutesNode = $httpNode->getChildren()['ignored_routes'];
        static::assertInstanceOf(PrototypedArrayNode::class, $ignoredRoutesNode);
        static::assertFalse($ignoredRoutesNode->isRequired());
        static::assertEmpty($ignoredRoutesNode->getDefaultValue());

        static::assertSame(['ignored_route1', 'ignored_route2'], $ignoredRoutesNode->normalize(['ignored_route1', 'ignored_route2']));
        static::assertSame(['ignored_route'], $ignoredRoutesNode->normalize('ignored_route'));
        static::assertSame(['ignored_route1', 'ignored_route2'], $ignoredRoutesNode->merge(['ignored_route1'], ['ignored_route2']));
    }

    public function testIgnoredPaths(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree()
        ;
        static::assertInstanceOf(ArrayNode::class, $rootNode);
        $httpNode = $rootNode->getChildren()['http'];
        static::assertInstanceOf(ArrayNode::class, $httpNode);

        $ignoredPathsNode = $httpNode->getChildren()['ignored_paths'];
        static::assertInstanceOf(PrototypedArrayNode::class, $ignoredPathsNode);
        static::assertFalse($ignoredPathsNode->isRequired());
        static::assertEmpty($ignoredPathsNode->getDefaultValue());

        static::assertSame(['/ignored/path1', '/ignored/path2'], $ignoredPathsNode->normalize(['/ignored/path1', '/ignored/path2']));
        static::assertSame(['/ignored/path'], $ignoredPathsNode->normalize('/ignored/path'));
        static::assertSame(['/ignored/path1', '/ignored/path2'], $ignoredPathsNode->merge(['/ignored/path1'], ['/ignored/path2']));
    }

    public function testIgnoredCommands(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree()
        ;
        static::assertInstanceOf(ArrayNode::class, $rootNode);
        $commandsNode = $rootNode->getChildren()['commands'];
        static::assertInstanceOf(ArrayNode::class, $commandsNode);

        $ignoredCommandsNode = $commandsNode->getChildren()['ignored_commands'];
        static::assertInstanceOf(PrototypedArrayNode::class, $ignoredCommandsNode);
        static::assertFalse($ignoredCommandsNode->isRequired());
        static::assertEmpty($ignoredCommandsNode->getDefaultValue());

        static::assertSame(['test:ignored-command1', 'test:ignored-command2'], $ignoredCommandsNode->normalize(['test:ignored-command1', 'test:ignored-command2']));
        static::assertSame(['test:ignored-command'], $ignoredCommandsNode->normalize('test:ignored-command'));
        static::assertSame(['test:ignored-command1', 'test:ignored-command2'], $ignoredCommandsNode->merge(['test:ignored-command1'], ['test:ignored-command2']));
    }

    public function testDefaults(): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), []);

        static::assertEmpty($config['http']['ignored_routes']);
        static::assertIsArray($config['http']['ignored_routes']);
        static::assertEmpty($config['http']['ignored_paths']);
        static::assertIsArray($config['http']['ignored_paths']);
        static::assertEmpty($config['commands']['ignored_commands']);
        static::assertIsArray($config['commands']['ignored_commands']);
        static::assertEmpty($config['deployment_names']);
        static::assertIsArray($config['deployment_names']);
    }

    #[DataProvider('provideDeploymentNamesCases')]
    public function testDeploymentNames($deploymentNameConfig, $expected): void
    {
        $processor = new Processor();

        $config1 = $processor->processConfiguration(new Configuration(), ['draw_new_relic' => ['deployment_name' => $deploymentNameConfig]]);
        $config2 = $processor->processConfiguration(new Configuration(), ['draw_new_relic' => ['deployment_names' => $deploymentNameConfig]]);

        static::assertSame($expected, $config1['deployment_names']);
        static::assertSame($expected, $config2['deployment_names']);
    }

    public static function provideDeploymentNamesCases(): iterable
    {
        return [
            ['App1', ['App1']],
            [['App1'], ['App1']],
            [['App1', 'App2'], ['App1', 'App2']],
        ];
    }

    #[DataProvider('provideIgnoreRoutesCases')]
    public function testIgnoreRoutes($ignoredRoutesConfig, $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['draw_new_relic' => ['http' => ['ignored_routes' => $ignoredRoutesConfig]]]);

        static::assertSame($expected, $config['http']['ignored_routes']);
    }

    public static function provideIgnoreRoutesCases(): iterable
    {
        return [
            ['single_ignored_route', ['single_ignored_route']],
            [['single_ignored_route'], ['single_ignored_route']],
            [['ignored_route1', 'ignored_route2'], ['ignored_route1', 'ignored_route2']],
        ];
    }

    #[DataProvider('provideIgnorePathsCases')]
    public function testIgnorePaths($ignoredPathsConfig, $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['draw_new_relic' => ['http' => ['ignored_paths' => $ignoredPathsConfig]]]);

        static::assertSame($expected, $config['http']['ignored_paths']);
    }

    public static function provideIgnorePathsCases(): iterable
    {
        return [
            ['/single/ignored/path', ['/single/ignored/path']],
            [['/single/ignored/path'], ['/single/ignored/path']],
            [['/ignored/path1', '/ignored/path2'], ['/ignored/path1', '/ignored/path2']],
        ];
    }

    #[DataProvider('provideIgnoreCommandsCases')]
    public function testIgnoreCommands($ignoredCommandsConfig, $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['draw_new_relic' => ['commands' => ['ignored_commands' => $ignoredCommandsConfig]]]);

        static::assertSame($expected, $config['commands']['ignored_commands']);
    }

    public static function provideIgnoreCommandsCases(): iterable
    {
        return [
            ['single:ignored:command', ['single:ignored:command']],
            [['single:ignored:command'], ['single:ignored:command']],
            [['ignored:command1', 'ignored:command2'], ['ignored:command1', 'ignored:command2']],
        ];
    }
}
