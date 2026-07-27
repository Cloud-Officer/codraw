<?php

declare(strict_types=1);

namespace Draw\Bundle\AwsSecretsBundle\Provider;

class AwsSecretsArrayEnvVarProvider implements AwsSecretsEnvVarProviderInterface
{
    private array $values = [];
    private AwsSecretsEnvVarProviderInterface $decorated;

    public function __construct(AwsSecretsEnvVarProviderInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function get(string $name): string
    {
        if (!isset($this->values[$name])) {
            $this->values[$name] = $this->decorated->get($name);
        }

        return $this->values[$name];
    }
}
