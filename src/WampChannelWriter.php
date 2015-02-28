<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Event;
use Aztech\Events\Bus\Channel\ChannelWriter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thruway\ClientSession;
use Thruway\Connection;

class WampChannelWriter implements ChannelWriter, LoggerAwareInterface
{

    private $connection;

    private $session;

    private $logger;

    private $buffer = null;

    private $isOpen = false;

    private $isPending = false;

    /**
     * @param string $endpoint
     * @param string $realm
     */
    public function __construct($endpoint, $realm)
    {
        $this->logger = new NullLogger();
        $this->buffer = new \SplStack();
        $this->connection = new Connection([
            'realm' => $realm,
            'url' => $endpoint
        ]);
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function write(Event $event, $serializedRepresentation)
    {
        if (! $this->isOpen) {
            $this->buffer->push([ $event, $serializedRepresentation ]);
            $this->open();
        }

        $data = [ 'id' => $event->getId(), 'data' => $serializedRepresentation ];

        $this->session->publish($event->getCategory(), $data, [], [ 'acknowledge' => false ]);
    }

    public function dispose()
    {
        if ($this->isOpen) {
            $this->connection->close();
        }
    }

    private function open()
    {
        if ($this->isPending || $this->isOpen) {
            return;
        }

        $this->isPending = true;
        $this->connection->on('open', [ $this, 'onConnectionOpen' ]);
        $this->connection->on('close', [ $this, 'onConnectionClose' ]);
        $this->connection->open(false);
    }

    private function onConnectionOpen(ClientSession $session)
    {
        $this->isPending = false;
        $this->isOpen = true;
        $this->session = $session;

        while (! $this->buffer->isEmpty()) {
            $pending = $this->buffer->shift();

            $this->write($pending[0], $pending[1]);
        }
    }

    private function onConnectionClose()
    {
        $this->isOpen = false;
        $this->session = null;
    }
}
