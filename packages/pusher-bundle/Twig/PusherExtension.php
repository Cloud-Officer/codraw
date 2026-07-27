<?php

namespace Draw\Bundle\PusherBundle\Twig;

use Draw\Bundle\PusherBundle\PusherConfiguration;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

/**
 * @author Pierre-Louis Launay <laupi.frpar@gmail.com>
 */
class PusherExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private PusherConfiguration $configuration)
    {
    }

    public function getGlobals(): array
    {
        return [
            'pusher_key' => $this->configuration->getAuthKey(),
        ];
    }
}
