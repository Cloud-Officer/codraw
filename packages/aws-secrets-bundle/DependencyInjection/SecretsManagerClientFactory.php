<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\DependencyInjection;

use Aws\SecretsManager\SecretsManagerClient;

class SecretsManagerClientFactory
{
    /**
     * @param string|null $version
     *
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

        if ($key && $secret) {
            $config['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        } elseif (($key && !$secret) || (!$key && $secret)) {
            throw new \Exception('Both key and secret must be provided or neither');
        }

        if ($endpoint) {
            $config['endpoint'] = $endpoint;
        }

        return new SecretsManagerClient($config);
    }
}
