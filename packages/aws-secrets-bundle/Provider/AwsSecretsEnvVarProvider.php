<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Provider;

use Aws\SecretsManager\SecretsManagerClient;

class AwsSecretsEnvVarProvider implements AwsSecretsEnvVarProviderInterface
{
    public const AWS_SECRET_ID = 'SecretId';
    public const AWS_SECRET_STRING = 'SecretString';
    private SecretsManagerClient $secretsManagerClient;

    public function __construct(SecretsManagerClient $secretsManagerClient)
    {
        $this->secretsManagerClient = $secretsManagerClient;
    }

    public function get(string $name): string
    {
        return $this->secretsManagerClient
            ->getSecretValue([self::AWS_SECRET_ID => $name])
            ->get(self::AWS_SECRET_STRING)
        ;
    }
}
