<?php

namespace Aztech\Events\Bus\Plugins\Wamp;

use Aztech\Events\Event;
use Aztech\Events\Bus\Channel\ChannelWriter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Thruway\ClientSession;
use Thruway\Connection;
use Thruway\Logging\Logger;

/**
 * Class WampChannelWriter
 * @package Aztech\Events\Bus\Plugins\Wamp
 */
class WampChannelWriter implements ChannelWriter, LoggerAwareInterface
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var NullLogger
     */
    private $logger;

    /**
     * @var null|\SplStack
     */
    private $buffer = null;

    /**
     * @var bool
     */
    private $isOpen = false;

    /**
     * @var bool
     */
    private $isPending = false;

    /**
     * @var int
     */
    private $pendingAcks = 0;

    /**
     * @var string
     */
    private $topic;

    private $self;

    /**
     * @param string $endpoint
     * @param string $realm
     * @param string $topic
     */
    public function __construct($endpoint, $realm, $topic = '')
    {
        $this->setLogger(new NullLogger());

        $this->topic = (string) $topic;
        $this->buffer = new \SplStack();
        $this->connection = new Connection([
            'realm' => $realm,
            'url' => $endpoint
        ]);

        $this->self = $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        Logger::set($logger);
        $this->logger = $logger;
    }

    /**
     * Writes an event. If connection is not yet open, the event is buffered until the connection is open.
     *
     * @param Event $event
     * @param string $serializedRepresentation
     */
    public function write(Event $event, $serializedRepresentation)
    {
        if (! $this->isOpen) {
            $this->buffer->push([ $event, $serializedRepresentation ]);
            $this->open();

            return;
        }

        $this->writeEvent($event, $serializedRepresentation);
    }

    /**
     * @param Event $event
     * @param $data
     */
    private function writeEvent(Event $event, $data)
    {
        $topic = trim($this->topic) == '' ? $event->getCategory() : $this->topic;
        $data = [ 'id' => $event->getId(), 'data' => $data ];
        $options = [ 'acknowledge' => true ];

        $this->pendingAcks++;
        $this->logger->debug('Publishing event: ' . $event->getCategory() . '#' . $event->getId());
        $this->session
            ->publish(
                $topic,
                [ $data ],
                [],
                $options
            )
            ->then(
                $this->getCallback('onAck'),
                $this->getCallback('onError')
            );
    }


    /**
     * Disposes of internal ressources.
     */
    public function dispose()
    {
        $this->connection->close();
    }

    /**
     * Opens the current connection
     */
    private function open()
    {
        if ($this->isPending || $this->isOpen) {
            return;
        }

        $this->isPending = true;
        $this->connection->on('open', $this->getCallback('onConnectionOpen'));
        $this->connection->on('close', $this->getCallback('onConnectionClose'));
        $this->connection->open();
    }

    private function closeIfAllAcked()
    {
        $this->pendingAcks--;

        if ($this->pendingAcks <= 0) {
            $this->connection->close();
        }
    }

    /**
     * Generates a callback function from a method name
     *
     * @param $name
     * @return callable
     */
    private function getCallback($name)
    {
        return function () use ($name) {
            call_user_func_array([ $this, $name ], func_get_args());
        };
    }

    /**
     * Handles message acks
     */
    private function onAck()
    {
        $this->logger->debug('Message ack');
        $this->closeIfAllAcked();
    }

    /**
     * Handles message errors
     */
    private function onError()
    {
        $this->logger->warning('Message error');
        $this->closeIfAllAcked();
    }

    /**
     * Invoked when connection is opened.
     *
     * @param ClientSession $session
     */
    private function onConnectionOpen(ClientSession $session)
    {
        $this->isPending = false;
        $this->isOpen = true;
        $this->session = $session;

        while (! $this->buffer->isEmpty()) {
            $pending = $this->buffer->shift();

            $this->writeEvent($pending[0], $pending[1]);
        }
    }

    /**
     * Invoked when connection is closed.
     */
    private function onConnectionClose()
    {
        $this->logger->debug('Closed connection');

        $this->isOpen = false;
        $this->session = null;
    }
}
