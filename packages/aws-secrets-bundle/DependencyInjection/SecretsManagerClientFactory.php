<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\DependencyInjection;

use Aws\SecretsManager\SecretsManagerClient;

class SecretsManagerClientFactory
{
    /**
     * @throws \Exception
     */
    public static function createClient(
        string $region,
        string $version,
        ?string $endpoint,
        ?string $key,
        ?string $secret,
    ): SecretsManagerClient {
        $config = [
            'region' => $region,
            'version' => $version,
        ];

        $hasKey = (bool) $key;
        $hasSecret = (bool) $secret;

        if ($hasKey !== $hasSecret) {
            throw new \Exception('Both key and secret must be provided or neither');
        }

        if ($hasKey) {
            $config['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        if ($endpoint) {
            $config['endpoint'] = $endpoint;
        }

        return new SecretsManagerClient($config);
    }
}
