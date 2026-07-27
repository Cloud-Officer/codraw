<?php

namespace Draw\Bundle\PusherBundle\Authenticator;

/**
 * ChannelAuthenticatorInterface.
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
interface ChannelAuthenticatorInterface
{
    /**
     * @param string $socketId    The socket ID
     * @param string $channelName The channel name
     */
    public function authenticate(string $socketId, string $channelName): bool;
}
