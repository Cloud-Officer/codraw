<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Tests\Fixtures;

use Aws\Result;
use Aws\SecretsManager\Exception\SecretsManagerException;
use Aws\SecretsManager\SecretsManagerClient;

/**
 * getSecretValue() is a magic method on the AWS client, so it cannot be mocked
 * with PHPUnit. This double declares it for real instead.
 */
class FakeSecretsManagerClient extends SecretsManagerClient
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $calls = [];

    public function __construct(private Result $result)
    {
        parent::__construct([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => ['key' => 'key', 'secret' => 'secret'],
            // The SDK derives these from the class name, which this subclass changes.
            'service' => 'secretsmanager',
            'exception_class' => SecretsManagerException::class,
        ]);
    }

    public function getSecretValue(array $args = []): Result
    {
        $this->calls[] = $args;

        return $this->result;
    }
}
