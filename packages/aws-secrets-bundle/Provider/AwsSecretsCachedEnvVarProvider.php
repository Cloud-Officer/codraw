<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Provider;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class AwsSecretsCachedEnvVarProvider implements AwsSecretsEnvVarProviderInterface
{
    public const CACHE_KEY_PREFIX = 'aws_secret';
    private CacheItemPoolInterface $cacheItemPool;
    private AwsSecretsEnvVarProviderInterface $decorated;
    private int $ttl;

    public function __construct(CacheItemPoolInterface $cacheItemPool, AwsSecretsEnvVarProviderInterface $decorated, int $ttl = 60)
    {
        $this->cacheItemPool = $cacheItemPool;
        $this->decorated = $decorated;
        $this->ttl = $ttl;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $name): string
    {
        $cacheKey = self::CACHE_KEY_PREFIX.'.'.md5($name);
        $cacheItem = $this->cacheItemPool->getItem($cacheKey);

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $value = $this->decorated->get($name);

        if (isset($cacheItem)) {
            $cacheItem->set($value);
            $cacheItem->expiresAfter($this->ttl);
            $this->cacheItemPool->save($cacheItem);
        }

        return $value;
    }
}
