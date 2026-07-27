# DrawAwsSecretsBundle

Use AWS Secrets Manager secrets as service container parameters in Symfony.

## Prerequisites

### Install the AWS SDK

Following Symfony's recommendation that a bundle must not embed third-party PHP libraries, the
`aws/aws-sdk-php` package is only a dev dependency of this bundle. Install it in your project:

```shell
composer require aws/aws-sdk-php
```

### AWS credentials

To connect to AWS Secrets Manager, your application must be authenticated on that AWS service.
Since there are several scenarios for this depending on your environment setup, configuring
environments and using credentials is covered here:
[AWS credentials and authentication](./doc/aws_credentials.md)

## Installation

```shell
composer require codraw/aws-secrets-bundle
```

Then register the bundle with your kernel:

```php
<?php

return [
    // ...
    Draw\Bundle\AwsSecretsBundle\DrawAwsSecretsBundle::class => ['all' => true],
];
```

## Configuration

Configuration is loaded from `config/packages/draw_aws_secrets.yaml` or its environment-specific
alternatives (for example `config/packages/test/draw_aws_secrets.yaml`). The following properties
are available:

```yaml
draw_aws_secrets:
  client_config:
    region:           # Required if "ignore" is false.
    version: 'latest' # Defaults to "latest".
    endpoint: ~
    credentials:
        key: ~
        secret: ~
  cache: 'array'      # One of: apcu, array, filesystem. Defaults to "array".
  delimiter: ','      # Delimiter separating the secret name from the key.
  ignore: false       # Pass through without calling AWS (set to "true" for local dev environments).
```

## Usage

Set an env var to an AWS Secrets Manager secret name:

    AWS_SECRET=secret_name

To grab a single key out of a JSON secret, separate the secret name and the key with the delimiter:

    AWS_SECRET=secret_name,key

Then read the environment variable through the `aws` processor:

```yaml
parameters:
    my_parameter: '%env(aws:AWS_SECRET)%'
```

The secret is resolved at runtime.

## Examples

* [Configure Doctrine to use AWS Secret values as MySQL connection parameters](./doc/sample_doctrine_mysql_connection.md)

## Credits

This bundle is a fork of [constup-foss/aws-secrets-bundle](https://github.com/constup-foss/aws-secrets-bundle)
(`constup/aws-secrets-bundle`) at version 2.0.0, re-namespaced under `Draw\Bundle\AwsSecretsBundle`
and updated for PHP 8.5 / Symfony 7.4. It is itself based on
[incompass/aws-secrets-bundle](https://github.com/casechek/aws-secrets-bundle). The original MIT
license is preserved in `LICENSE`.
