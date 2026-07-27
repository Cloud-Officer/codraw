<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsArrayEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AwsSecretsArrayEnvVarProviderTest extends TestCase
{
    private AwsSecretsEnvVarProviderInterface&MockObject $decorated;

    private AwsSecretsArrayEnvVarProvider $provider;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(AwsSecretsEnvVarProviderInterface::class);
        $this->provider = new AwsSecretsArrayEnvVarProvider($this->decorated);
    }

    #[Test]
    public function itReturnsDecoratedValue(): void
    {
        $this->decorated->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn('value')
        ;

        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }

    #[Test]
    public function itReturnsCachedValueOnSecondCall(): void
    {
        $this->decorated->expects($this->once())
            ->method('get')
            ->with('key')
            ->willReturn('value')
        ;

        $this->provider->get('key');
        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }
}
