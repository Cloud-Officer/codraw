<?php

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsCachedEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
class AwsSecretsCachedEnvVarProviderTest extends TestCase
{
    use ProphecyTrait;

    private $decorated;
    private $provider;
    private $cacheItemPool;

    protected function setUp(): void
    {
        $this->decorated = $this->prophesize(AwsSecretsEnvVarProviderInterface::class);
        $this->cacheItemPool = $this->prophesize(CacheItemPoolInterface::class);
        $this->provider = new AwsSecretsCachedEnvVarProvider(
            $this->cacheItemPool->reveal(),
            $this->decorated->reveal(),
            60
        );
    }

    #[Test]
    public function itReturnsCachedItemIfHit(): void
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->willReturn(true);
        $cacheItem->get()->willReturn('value');
        $this->cacheItemPool->getItem(AwsSecretsCachedEnvVarProvider::CACHE_KEY_PREFIX.'.'.md5('key'))
            ->willReturn($cacheItem)
        ;

        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }

    #[Test]
    public function itSetsCacheItemAndReturnsDecoratedValueOnNoHit(): void
    {
        $cacheItem = $this->prophesize(CacheItemInterface::class);
        $cacheItem->isHit()->shouldBeCalled()->willReturn(false);
        $cacheItem->set('value')->shouldBeCalled()->willReturn($cacheItem);
        $cacheItem->expiresAfter(60)->shouldBeCalled()->willReturn($cacheItem);
        $this->cacheItemPool->save($cacheItem->reveal())->shouldBeCalled();
        $this->cacheItemPool->getItem(AwsSecretsCachedEnvVarProvider::CACHE_KEY_PREFIX.'.'.md5('key'))
            ->willReturn($cacheItem)
        ;
        $this->decorated->get('key')->willReturn('value');

        $result = $this->provider->get('key');
        static::assertSame('value', $result);
    }
}
