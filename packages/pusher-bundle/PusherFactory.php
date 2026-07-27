<?php

namespace Draw\Bundle\PusherBundle;

use Pusher\Pusher;

/**
 * @author Pierre-Louis Launay <laupi.frpar@gmail.com>
 */
class PusherFactory
{
    /**
     * @throws \Pusher\PusherException
     */
    public static function create(PusherConfiguration $configuration): Pusher
    {
        return new Pusher(
            $configuration->getAuthKey(),
            $configuration->getSecret(),
            $configuration->getAppId(),
            $configuration->getOptions()
        );
    }
}
