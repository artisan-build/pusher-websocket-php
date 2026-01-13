<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Connection\Handshake;

use ArtisanBuild\Pusher\Connection\Connection;
use ArtisanBuild\Pusher\Connection\Protocol\Protocol;
use ArtisanBuild\Pusher\Transports\TransportConnection;
use Closure;

/**
 * Handles Pusher protocol handshakes for transports.
 *
 * Calls back with a result object after handshake is completed.
 */
class Handshake
{
    public TransportConnection $transport;

    public Closure $callback;

    public ?Closure $onMessage = null;

    public ?Closure $onClosed = null;

    public function __construct(
        TransportConnection $transport,
        Closure $callback
    ) {
        $this->transport = $transport;
        $this->callback = $callback;
        $this->bindListeners();
    }

    public function close(): void
    {
        $this->unbindListeners();
        $this->transport->close();
    }

    private function bindListeners(): void
    {
        $this->onMessage = function (mixed $m): void {
            $this->unbindListeners();

            try {
                $result = Protocol::processHandshake($m);
            } catch (\Throwable $e) {
                $this->finish('error', ['error' => $e]);
                $this->transport->close();

                return;
            }

            if ($result->action === 'connected') {
                $this->finish('connected', [
                    'connection' => new Connection($result->id, $this->transport),
                    'activityTimeout' => $result->activityTimeout,
                ]);
            } else {
                $this->finish($result->action, ['error' => $result->error]);
                $this->transport->close();
            }
        };

        $this->onClosed = function (mixed $closeEvent): void {
            $this->unbindListeners();

            $action = Protocol::getCloseAction($closeEvent ?? []) ?? 'backoff';
            $error = Protocol::getCloseError($closeEvent ?? []);
            $this->finish($action, ['error' => $error]);
        };

        $this->transport->bind('message', $this->onMessage);
        $this->transport->bind('closed', $this->onClosed);
    }

    private function unbindListeners(): void
    {
        $this->transport->unbind('message', $this->onMessage);
        $this->transport->unbind('closed', $this->onClosed);
    }

    private function finish(string $action, array $params): void
    {
        $payload = new HandshakePayload(
            action: $action,
            activityTimeout: $params['activityTimeout'] ?? null,
            error: $params['error'] ?? null,
            transport: $this->transport,
            connection: $params['connection'] ?? null,
        );
        ($this->callback)($payload);
    }
}
