<?php

declare(strict_types=1);

namespace Draw\Bundle\NewRelicBundle\Tests\NewRelic;

use Draw\Bundle\NewRelicBundle\NewRelic\Config;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class ConfigTest extends TestCase
{
    public function testGeneric(): void
    {
        $newRelic = new Config('Draw', 'XXX', null, false, [], 'api.host');

        static::assertSame('Draw', $newRelic->getName());
        static::assertSame('XXX', $newRelic->getApiKey());
        static::assertSame('api.host', $newRelic->getApiHost());

        static::assertEmpty($newRelic->getCustomEvents());
        static::assertEmpty($newRelic->getCustomMetrics());
        static::assertEmpty($newRelic->getCustomParameters());

        $newRelic->addCustomEvent('WidgetSale', ['color' => 'red', 'weight' => 12.5]);
        $newRelic->addCustomEvent('WidgetSale', ['color' => 'blue', 'weight' => 12.5]);

        $expected = [
            'WidgetSale' => [
                [
                    'color' => 'red',
                    'weight' => 12.5,
                ],
                [
                    'color' => 'blue',
                    'weight' => 12.5,
                ],
            ],
        ];

        static::assertSame($expected, $newRelic->getCustomEvents());

        $newRelic->addCustomMetric('foo', 4.2);
        $newRelic->addCustomMetric('asd', 1);

        $expected = [
            'foo' => 4.2,
            'asd' => 1.0,
        ];

        static::assertSame($expected, $newRelic->getCustomMetrics());

        $newRelic->addCustomParameter('param1', 1);

        $expected = [
            'param1' => 1,
        ];

        static::assertSame($expected, $newRelic->getCustomParameters());
    }

    public function testDefaults(): void
    {
        $newRelic = new Config('', '');

        static::assertSame(\ini_get('newrelic.appname') ?: '', $newRelic->getName());

        static::assertNotNull($newRelic->getLicenseKey());
        static::assertSame(\ini_get('newrelic.license') ?: '', $newRelic->getLicenseKey());

        static::assertNull($newRelic->getApiHost());
    }
}
