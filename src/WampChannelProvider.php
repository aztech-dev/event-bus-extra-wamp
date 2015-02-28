<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Bus\Channel;
use Aztech\Events\Bus\Channel\ChannelProvider;

class WampChannelProvider implements ChannelProvider
{

    /**
     *
     * @param array $options
     * @return Channel
     */
    function createChannel(array $options = array())
    {
        return new Channel\WriteOnlyChannel(
            new WampChannelWriter($options['endpoint'], $options['realm']);
        );
    }
}