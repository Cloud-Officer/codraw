<?php

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsCachedEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProviderInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @internal
 */
class AwsSecretsCachedEnvVarProviderTest extends TestCase
{
    private AwsSecretsEnvVarProviderInterface&MockObject $decorated;

    private CacheItemPoolInterface&MockObject $cacheItemPool;

    private AwsSecretsCachedEnvVarProvider $provider;

    protected function setUp(): void
    {
        $this->decorated = $this->createMock(AwsSecretsEnvVarProviderInterface::class);
        $this->cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $this->provider = new AwsSecretsCachedEnvVarProvider(
            $this->cacheItemPool,
            $this->decorated,
            60
        );
    }

    #[Test]
    public function itReturnsCachedItemIfHit(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(true);
        $cacheItem->expects($this->once())->method('get')->willReturn('value');

        $this->cacheItemPool->expects($this->once())
            ->method('getItem')
            ->with(AwsSecretsCachedEnvVarProvider::CACHE_KEY_PREFIX.'.'.md5('key'))
            ->willReturn($cacheItem)
        ;
        $this->decorated->expects($this->never())->method('get');

        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }

    #[Test]
    public function itSetsCacheItemAndReturnsDecoratedValueOnNoHit(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())->method('isHit')->willReturn(false);
        $cacheItem->expects($this->once())->method('set')->with('value')->willReturnSelf();
        $cacheItem->expects($this->once())->method('expiresAfter')->with(60)->willReturnSelf();

        $this->cacheItemPool->expects($this->once())
            ->method('getItem')
            ->with(AwsSecretsCachedEnvVarProvider::CACHE_KEY_PREFIX.'.'.md5('key'))
            ->willReturn($cacheItem)
        ;
        $this->cacheItemPool->expects($this->once())->method('save')->with($cacheItem)->willReturn(true);
        $this->decorated->expects($this->once())->method('get')->with('key')->willReturn('value');

        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }
}
