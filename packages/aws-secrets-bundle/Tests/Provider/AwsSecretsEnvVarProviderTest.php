<?php

namespace Draw\Bundle\AwsSecretsBundle\Tests\Provider;

use Aws\Result;
use Aws\SecretsManager\SecretsManagerClient;
use Draw\Bundle\AwsSecretsBundle\Provider\AwsSecretsEnvVarProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 */
class AwsSecretsEnvVarProviderTest extends TestCase
{
    use ProphecyTrait;

    private $secretsManagerClient;
    private $provider;

    protected function setUp(): void
    {
        $this->secretsManagerClient = $this->prophesize(SecretsManagerClient::class);
        $this->provider = new AwsSecretsEnvVarProvider($this->secretsManagerClient->reveal());
    }

    #[Test]
    public function itGetsValueFromSecretsManager(): void
    {
        $result = $this->prophesize(Result::class);
        $this->secretsManagerClient->getSecretValue([AwsSecretsEnvVarProvider::AWS_SECRET_ID => 'key'])->willReturn($result);
        $result->get(AwsSecretsEnvVarProvider::AWS_SECRET_STRING)->willReturn('value');

        $result = $this->provider->get('key');

        static::assertSame('value', $result);
    }
}
