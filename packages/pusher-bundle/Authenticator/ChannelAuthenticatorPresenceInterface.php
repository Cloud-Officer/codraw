<?php

namespace Draw\Bundle\PusherBundle\Authenticator;

/**
 * ChannelAuthenticatorPresenceInterface.
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
interface ChannelAuthenticatorPresenceInterface extends ChannelAuthenticatorInterface
{
    /**
     * Returns an optional array of user info.
     */
    public function getUserInfo(): array;

    /**
     * Return the user id when authenticated, used for presence channels.
     */
    public function getUserId(): string;
}
