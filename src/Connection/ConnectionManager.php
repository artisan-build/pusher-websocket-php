<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection;

use ArtisanBuild\Pusher\Connection\Handshake\HandshakePayload;
use ArtisanBuild\Pusher\Connection\Protocol\Action;
use ArtisanBuild\Pusher\Events\Dispatcher;
use ArtisanBuild\Pusher\Logger;
use ArtisanBuild\Pusher\Strategies\Strategy;
use ArtisanBuild\Pusher\Strategies\StrategyRunner;
use ArtisanBuild\Pusher\Timeline\Timeline;
use ArtisanBuild\Pusher\Utils\Timers\OneOffTimer;
use Closure;

/**
 * Manages connection to Pusher.
 *
 * Uses a strategy, timers and network availability info to establish
 * a connection and export its state.
 *
 * States:
 * - initialized - initial state
 * - connecting - connection is being established
 * - connected - connection has been fully established
 * - disconnected - on requested disconnection
 * - unavailable - after connection timeout or when there's no network
 * - failed - when the connection strategy is not supported
 */
class ConnectionManager extends Dispatcher
{
    public string $key;

    public ConnectionManagerOptions $options;

    public string $state;

    public ?Connection $connection = null;

    public bool $usingTLS;

    public Timeline $timeline;

    public ?string $socket_id = null;

    public ?OneOffTimer $unavailableTimer = null;

    public ?OneOffTimer $activityTimer = null;

    public ?OneOffTimer $retryTimer = null;

    public ?int $activityTimeout = null;

    public ?Strategy $strategy = null;

    public ?StrategyRunner $runner = null;

    public ErrorCallbacks $errorCallbacks;

    public HandshakeCallbacks $handshakeCallbacks;

    public ConnectionCallbacks $connectionCallbacks;

    public function __construct(string $key, ConnectionManagerOptions $options)
    {
        parent::__construct();
        $this->state = 'initialized';
        $this->connection = null;

        $this->key = $key;
        $this->options = $options;
        $this->timeline = $this->options->timeline;
        $this->usingTLS = $this->options->useTLS;

        $this->errorCallbacks = $this->buildErrorCallbacks();
        $this->connectionCallbacks = $this->buildConnectionCallbacks($this->errorCallbacks);
        $this->handshakeCallbacks = $this->buildHandshakeCallbacks($this->errorCallbacks);

        // Network binding would go here in a real implementation
        // Runtime::getNetwork()->bind('online', ...)
        // Runtime::getNetwork()->bind('offline', ...)

        $this->updateStrategy();
    }

    /**
     * Establishes a connection to Pusher.
     */
    public function connect(): void
    {
        if ($this->connection || $this->runner) {
            return;
        }
        if (! $this->strategy->isSupported()) {
            $this->updateState('failed');

            return;
        }
        $this->updateState('connecting');
        $this->startConnecting();
        $this->setUnavailableTimer();
    }

    /**
     * Sends raw data.
     */
    public function send(mixed $data): bool
    {
        if ($this->connection) {
            return $this->connection->send($data);
        }

        return false;
    }

    /**
     * Sends an event.
     */
    public function sendEvent(string $name, mixed $data, ?string $channel = null): bool
    {
        if ($this->connection) {
            return $this->connection->sendEvent($name, $data, $channel);
        }

        return false;
    }

    /**
     * Closes the connection.
     */
    public function disconnect(): void
    {
        $this->disconnectInternally();
        $this->updateState('disconnected');
    }

    public function isUsingTLS(): bool
    {
        return $this->usingTLS;
    }

    private function startConnecting(): void
    {
        $callback = function (mixed $error, mixed $handshake) use (&$callback): void {
            if ($error) {
                $this->runner = $this->strategy->connect(0, $callback);
            } else {
                if ($handshake->action === 'error') {
                    $this->emit('error', [
                        'type' => 'HandshakeError',
                        'error' => $handshake->error,
                    ]);
                    $this->timeline->error(['handshakeError' => $handshake->error]);
                } else {
                    $this->abortConnecting();
                    $action = $handshake->action;
                    $cb = match ($action) {
                        'connected' => $this->handshakeCallbacks->connected,
                        'tls_only' => $this->errorCallbacks->tls_only,
                        'refused' => $this->errorCallbacks->refused,
                        'backoff' => $this->errorCallbacks->backoff,
                        'retry' => $this->errorCallbacks->retry,
                        default => null,
                    };
                    if ($cb) {
                        $cb($handshake);
                    }
                }
            }
        };
        $this->runner = $this->strategy->connect(0, $callback);
    }

    private function abortConnecting(): void
    {
        if ($this->runner) {
            $this->runner->abort();
            $this->runner = null;
        }
    }

    private function disconnectInternally(): void
    {
        $this->abortConnecting();
        $this->clearRetryTimer();
        $this->clearUnavailableTimer();
        if ($this->connection) {
            $connection = $this->abandonConnection();
            $connection?->close();
        }
    }

    private function updateStrategy(): void
    {
        $this->strategy = ($this->options->getStrategy)([
            'key' => $this->key,
            'timeline' => $this->timeline,
            'useTLS' => $this->usingTLS,
        ]);
    }

    private function retryIn(int $delay): void
    {
        $this->timeline->info(['action' => 'retry', 'delay' => $delay]);
        if ($delay > 0) {
            $this->emit('connecting_in', (int) round($delay / 1000));
        }
        $this->retryTimer = new OneOffTimer($delay, function () {
            $this->disconnectInternally();
            $this->connect();
        });
    }

    private function clearRetryTimer(): void
    {
        if ($this->retryTimer) {
            $this->retryTimer->ensureAborted();
            $this->retryTimer = null;
        }
    }

    private function setUnavailableTimer(): void
    {
        $this->unavailableTimer = new OneOffTimer($this->options->unavailableTimeout, function () {
            $this->updateState('unavailable');
        });
    }

    private function clearUnavailableTimer(): void
    {
        if ($this->unavailableTimer) {
            $this->unavailableTimer->ensureAborted();
        }
    }

    private function sendActivityCheck(): void
    {
        $this->stopActivityCheck();
        $this->connection->ping();
        $this->activityTimer = new OneOffTimer($this->options->pongTimeout, function () {
            $this->timeline->error(['pong_timed_out' => $this->options->pongTimeout]);
            $this->retryIn(0);
        });
    }

    private function resetActivityCheck(): void
    {
        $this->stopActivityCheck();
        if ($this->connection && ! $this->connection->handlesActivityChecks()) {
            $this->activityTimer = new OneOffTimer($this->activityTimeout, function () {
                $this->sendActivityCheck();
            });
        }
    }

    private function stopActivityCheck(): void
    {
        if ($this->activityTimer) {
            $this->activityTimer->ensureAborted();
        }
    }

    private function buildConnectionCallbacks(ErrorCallbacks $errorCallbacks): ConnectionCallbacks
    {
        return new ConnectionCallbacks(
            message: function (mixed $message): void {
                $this->resetActivityCheck();
                $this->emit('message', $message);
            },
            ping: function (): void {
                $this->sendEvent('pusher:pong', []);
            },
            activity: function (): void {
                $this->resetActivityCheck();
            },
            error: function (mixed $error): void {
                $this->emit('error', $error);
            },
            closed: function (): void {
                $this->abandonConnection();
                if ($this->shouldRetry()) {
                    $this->retryIn(1000);
                }
            },
        );
    }

    private function buildHandshakeCallbacks(ErrorCallbacks $errorCallbacks): HandshakeCallbacks
    {
        return new HandshakeCallbacks(
            connected: function (HandshakePayload $handshake) {
                $this->activityTimeout = min(
                    $this->options->activityTimeout,
                    $handshake->activityTimeout ?? PHP_INT_MAX,
                    $handshake->connection?->activityTimeout ?? PHP_INT_MAX
                );
                $this->clearUnavailableTimer();
                $this->setConnection($handshake->connection);
                $this->socket_id = $this->connection->id;
                $this->updateState('connected', ['socket_id' => $this->socket_id]);
            },
        );
    }

    private function buildErrorCallbacks(): ErrorCallbacks
    {
        $withErrorEmitted = fn (Closure $callback): Closure => function (mixed $result) use ($callback): void {
            if ($result->error ?? null) {
                $this->emit('error', ['type' => 'WebSocketError', 'error' => $result->error]);
            }
            $callback($result);
        };

        return new ErrorCallbacks(
            tls_only: $withErrorEmitted(function () {
                $this->usingTLS = true;
                $this->updateStrategy();
                $this->retryIn(0);
            }),
            refused: $withErrorEmitted(function () {
                $this->disconnect();
            }),
            backoff: $withErrorEmitted(function () {
                $this->retryIn(1000);
            }),
            retry: $withErrorEmitted(function () {
                $this->retryIn(0);
            }),
        );
    }

    private function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
        $callbacks = [
            'message' => $this->connectionCallbacks->message,
            'ping' => $this->connectionCallbacks->ping,
            'activity' => $this->connectionCallbacks->activity,
            'error' => $this->connectionCallbacks->error,
            'closed' => $this->connectionCallbacks->closed,
        ];
        foreach ($callbacks as $event => $callback) {
            if ($callback) {
                $this->connection->bind($event, $callback);
            }
        }
        $this->resetActivityCheck();
    }

    private function abandonConnection(): ?Connection
    {
        if (! $this->connection) {
            return null;
        }
        $this->stopActivityCheck();
        $callbacks = [
            'message' => $this->connectionCallbacks->message,
            'ping' => $this->connectionCallbacks->ping,
            'activity' => $this->connectionCallbacks->activity,
            'error' => $this->connectionCallbacks->error,
            'closed' => $this->connectionCallbacks->closed,
        ];
        foreach ($callbacks as $event => $callback) {
            if ($callback) {
                $this->connection->unbind($event, $callback);
            }
        }
        $connection = $this->connection;
        $this->connection = null;

        return $connection;
    }

    private function updateState(string $newState, ?array $data = null): void
    {
        $previousState = $this->state;
        $this->state = $newState;
        if ($previousState !== $newState) {
            $newStateDescription = $newState;
            if ($newStateDescription === 'connected') {
                $newStateDescription .= ' with new socket ID '.($data['socket_id'] ?? '');
            }
            Logger::debug('State changed', $previousState.' -> '.$newStateDescription);
            $this->timeline->info(['state' => $newState, 'params' => $data]);
            $this->emit('state_change', ['previous' => $previousState, 'current' => $newState]);
            $this->emit($newState, $data);
        }
    }

    private function shouldRetry(): bool
    {
        return $this->state === 'connecting' || $this->state === 'connected';
    }
}
