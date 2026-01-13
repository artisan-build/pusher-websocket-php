<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use ArtisanBuild\Pusher\Connection\Protocol\MessageTypes\PusherEvent;
use ArtisanBuild\Pusher\Connection\Protocol\Protocol;
use ArtisanBuild\Pusher\Events\Dispatcher;
use ArtisanBuild\Pusher\Logger;
use ArtisanBuild\Pusher\Transports\TransportConnection;

/**
 * Provides Pusher protocol interface for transports.
 *
 * Emits following events:
 * - message - on received messages
 * - ping - on ping requests
 * - pong - on pong responses
 * - error - when the transport emits an error
 * - closed - after closing the transport
 */
class Connection extends Dispatcher
{
    public string $id;

    public ?TransportConnection $transport;

    public ?int $activityTimeout;

    public function __construct(string $id, TransportConnection $transport)
    {
        parent::__construct();
        $this->id = $id;
        $this->transport = $transport;
        $this->activityTimeout = $transport->activityTimeout;
        $this->bindListeners();
    }

    /**
     * Returns whether used transport handles activity checks by itself.
     */
    public function handlesActivityChecks(): bool
    {
        return $this->transport->handlesActivityChecks();
    }

    /**
     * Sends raw data.
     */
    public function send(mixed $data): bool
    {
        return $this->transport->send($data);
    }

    /**
     * Sends an event.
     */
    public function sendEvent(string $name, mixed $data, ?string $channel = null): bool
    {
        $event = new PusherEvent(
            event: $name,
            data: $data,
            channel: $channel,
        );
        Logger::debug('Event sent', (array) $event);

        return $this->send(Protocol::encodeMessage($event));
    }

    /**
     * Sends a ping message to the server.
     */
    public function ping(): void
    {
        if ($this->transport->supportsPing()) {
            $this->transport->ping();
        } else {
            $this->sendEvent('pusher:ping', []);
        }
    }

    /**
     * Closes the connection.
     */
    public function close(): void
    {
        $this->transport->close();
    }

    private function bindListeners(): void
    {
        /** @var ?\Closure $unbindListeners */
        $unbindListeners = null;

        $listeners = [
            'message' => function (mixed $messageEvent): void {
                $pusherEvent = null;
                try {
                    $pusherEvent = Protocol::decodeMessage($messageEvent);
                } catch (\Throwable $e) {
                    $this->emit('error', [
                        'type' => 'MessageParseError',
                        'error' => $e,
                        'data' => $messageEvent['data'] ?? null,
                    ]);
                }

                if ($pusherEvent !== null) {
                    Logger::debug('Event recd', (array) $pusherEvent);

                    switch ($pusherEvent->event) {
                        case 'pusher:error':
                            $this->emit('error', [
                                'type' => 'PusherError',
                                'data' => $pusherEvent->data,
                            ]);
                            break;
                        case 'pusher:ping':
                            $this->emit('ping');
                            break;
                        case 'pusher:pong':
                            $this->emit('pong');
                            break;
                    }
                    $this->emit('message', $pusherEvent);
                }
            },
            'activity' => function (): void {
                $this->emit('activity');
            },
            'error' => function (mixed $error): void {
                $this->emit('error', $error);
            },
            'closed' => function (mixed $closeEvent) use (&$unbindListeners): void {
                if ($unbindListeners !== null) {
                    $unbindListeners();
                }

                if ($closeEvent && isset($closeEvent['code'])) {
                    $this->handleCloseEvent($closeEvent);
                }

                $this->transport = null;
                $this->emit('closed');
            },
        ];

        $unbindListeners = function () use ($listeners): void {
            foreach ($listeners as $event => $listener) {
                $this->transport?->unbind($event, $listener);
            }
        };

        foreach ($listeners as $event => $listener) {
            $this->transport->bind($event, $listener);
        }
    }

    private function handleCloseEvent(array $closeEvent): void
    {
        $action = Protocol::getCloseAction($closeEvent);
        $error = Protocol::getCloseError($closeEvent);

        if ($error) {
            $this->emit('error', $error);
        }
        if ($action) {
            $this->emit($action, ['action' => $action, 'error' => $error]);
        }
    }
}
