<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Bus\Channel\ChannelProvider;
use Aztech\Events\Bus\Factory\GenericOptionsDescriptor;
use Aztech\Events\Bus\Factory\OptionsDescriptor;
use Aztech\Events\Bus\PluginFactory;

class WampPluginFactory implements PluginFactory
{

    /**
     *
     * @return OptionsDescriptor
     */
    function getOptionsDescriptor()
    {
        $descriptor = new GenericOptionsDescriptor();

        $descriptor->addOption('endpoint', true);
        $descriptor->addOption('realm', true);

        return $descriptor;
    }

    /**
     *
     * @return ChannelProvider
     */
    function getChannelProvider()
    {
        return new WampChannelProvider();
    }
}