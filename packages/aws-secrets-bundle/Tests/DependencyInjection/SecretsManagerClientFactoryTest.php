<?php

namespace Draw\Bundle\AwsSecretsBundle\Tests\DependencyInjection;

use Aws\SecretsManager\SecretsManagerClient;
use Draw\Bundle\AwsSecretsBundle\DependencyInjection\SecretsManagerClientFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class SecretsManagerClientFactoryTest extends TestCase
{
    #[Test]
    public function itThrowsExceptionWhenNoSecretButKeyProvided(): void
    {
        $this->expectExceptionMessage('Both key and secret must be provided or neither');
        $factory = new SecretsManagerClientFactory();
        $factory->createClient(
            'region',
            'latest',
            null,
            'key',
            null
        );
    }

    #[Test]
    public function itThrowsExceptionWhenNoKeyButSecretProvided(): void
    {
        $this->expectExceptionMessage('Both key and secret must be provided or neither');
        $factory = new SecretsManagerClientFactory();
        $factory->createClient(
            'region',
            'latest',
            null,
            null,
            'secret'
        );
    }

    #[Test]
    public function itBuildsClientWithoutKeyOrSecret(): void
    {
        $factory = new SecretsManagerClientFactory();
        $client = $factory->createClient(
            'region',
            'latest',
            null,
            null,
            null
        );
        static::assertInstanceOf(SecretsManagerClient::class, $client);
    }

    #[Test]
    public function itBuildsClientWithKeyAndSecret(): void
    {
        $factory = new SecretsManagerClientFactory();
        $client = $factory->createClient(
            'region',
            'latest',
            null,
            'key',
            'secret'
        );
        static::assertInstanceOf(SecretsManagerClient::class, $client);
    }

    #[Test]
    public function itBuildsClientWithEndpoint(): void
    {
        $factory = new SecretsManagerClientFactory();
        $client = $factory->createClient(
            'region',
            'latest',
            'http://my-endpoint.example.com:4566',
            null,
            null
        );
        static::assertInstanceOf(SecretsManagerClient::class, $client);
    }
}
