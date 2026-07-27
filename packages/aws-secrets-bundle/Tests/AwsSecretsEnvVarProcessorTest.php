<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Tests;

use Draw\Bundle\AwsSecretsBundle\AwsSecretsEnvVarProcessor;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
class AwsSecretsEnvVarProcessorTest extends TestCase
{
    use ProphecyTrait;

    /** @var AwsSecretsEnvVarProcessor */
    private $processor;
    /** @var AwsSecretsEnvVarProviderInterface */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = $this->prophesize(AwsSecretsEnvVarProviderInterface::class);

        $this->processor = new AwsSecretsEnvVarProcessor(
            $this->provider->reveal(),
            false,
            ','
        );
    }

    #[Test]
    public function itCallsClosureIfIgnore(): void
    {
        $this->processor->setIgnore(true);

        $callCount = 0;
        $result = $this->processor->getEnv(
            'aws',
            'AWS_SECRET',
            static function ($name) use (&$callCount) {
                ++$callCount;

                return 'value';
            }
        );
        static::assertSame(1, $callCount);
        static::assertSame('value', $result);
    }

    #[Test]
    public function itReturnsStringForKey(): void
    {
        $this->provider->get('prefix/db')->willReturn('{"key":"value"}');

        $callCount = 0;
        $value = $this->processor->getEnv(
            'aws',
            'AWS_SECRET',
            static function (string $name) use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return 'prefix/db,key';
                }

                return null;
            }
        );
        static::assertSame('value', $value);
    }

    #[Test]
    public function itReturnsString(): void
    {
        $callCount = 0;
        $this->provider->get('prefix/db')->willReturn('value');

        $value = $this->processor->getEnv(
            'aws',
            'AWS_SECRET',
            static function (string $name) use (&$callCount) {
                ++$callCount;
                if (1 === $callCount) {
                    return 'prefix/db';
                }

                return null;
            }
        );

        static::assertSame(1, $callCount);
        static::assertSame('value', $value);
    }
}
