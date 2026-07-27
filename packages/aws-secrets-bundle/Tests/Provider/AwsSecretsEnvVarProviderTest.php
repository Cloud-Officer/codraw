<?php

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Aws\Result;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProvider;
use Draw\Bundle\AwsSecretsBundle\Tests\Fixtures\FakeSecretsManagerClient;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class AwsSecretsEnvVarProviderTest extends TestCase
{
    #[Test]
    public function itGetsValueFromSecretsManager(): void
    {
        $client = new FakeSecretsManagerClient(
            new Result([AwsSecretsEnvVarProvider::AWS_SECRET_STRING => 'value'])
        );

        $provider = new AwsSecretsEnvVarProvider($client);

        static::assertSame('value', $provider->get('key'));
        static::assertSame(
            [[AwsSecretsEnvVarProvider::AWS_SECRET_ID => 'key']],
            $client->calls
        );
    }
}
