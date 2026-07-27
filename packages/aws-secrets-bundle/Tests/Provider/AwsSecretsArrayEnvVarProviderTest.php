<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsArrayEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
class AwsSecretsArrayEnvVarProviderTest extends TestCase
{
    use ProphecyTrait;

    private $decorated;
    private $provider;

    protected function setUp(): void
    {
        $this->decorated = $this->prophesize(AwsSecretsEnvVarProviderInterface::class);
        $this->provider = new AwsSecretsArrayEnvVarProvider($this->decorated->reveal());
    }

    #[Test]
    public function itReturnsDecoratedValue(): void
    {
        $this->decorated->get('key')->shouldBeCalledTimes(1)->willReturn('value');
        $result = $this->provider->get('key');
        static::assertSame('value', $result);
    }

    #[Test]
    public function itReturnsCachedValueOnSecondCall(): void
    {
        $this->decorated->get('key')->shouldBeCalledTimes(1)->willReturn('value');
        $this->provider->get('key');
        $result = $this->provider->get('key');
        static::assertSame('value', $result);
    }
}
