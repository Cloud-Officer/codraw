<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Provider;

interface AwsSecretsEnvVarProviderInterface
{
    public function get(string $name): string;
}
