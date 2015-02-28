<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Bus\Channel;
use Aztech\Events\Bus\Channel\ChannelProvider;
use Psr\Log\LoggerInterface;

class WampChannelProvider implements ChannelProvider
{

    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     *
     * @param array $options
     * @return Channel
     */
    function createChannel(array $options = array())
    {
        $writer = new WampChannelWriter($options['endpoint'], $options['realm'], $options['topic']);
        $writer->setLogger($this->logger);

        return new Channel\WriteOnlyChannel($writer);
    }
}