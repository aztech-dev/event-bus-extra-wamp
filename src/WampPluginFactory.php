<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Bus\Channel\ChannelProvider;
use Aztech\Events\Bus\Factory\GenericOptionsDescriptor;
use Aztech\Events\Bus\Factory\OptionsDescriptor;
use Aztech\Events\Bus\PluginFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class WampPluginFactory implements PluginFactory
{

    private $logger = null;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    /**
     *
     * @return OptionsDescriptor
     */
    function getOptionsDescriptor()
    {
        $descriptor = new GenericOptionsDescriptor();

        $descriptor->addOption('endpoint', true);
        $descriptor->addOption('realm', true);
        $descriptor->addOption('topic', false, '');

        return $descriptor;
    }

    /**
     *
     * @return ChannelProvider
     */
    function getChannelProvider()
    {
        return new WampChannelProvider($this->logger);
    }
}