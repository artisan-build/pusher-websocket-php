<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use Closure;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use Throwable;

/**
 * Wrapper around ratchet/pawl WebSocket to provide a consistent interface.
 */
class WebSocketWrapper
{
    public ?Closure $onopen = null;

    public ?Closure $onerror = null;

    public ?Closure $onclose = null;

    public ?Closure $onmessage = null;

    public ?Closure $onactivity = null;

    private ?WebSocket $connection = null;

    private ?Throwable $error = null;

    public function setConnection(WebSocket $connection): void
    {
        $this->connection = $connection;
        $this->bindEvents();
        $this->triggerOpen();
    }

    public function setError(Throwable $error): void
    {
        $this->error = $error;
        $this->triggerError();
    }

    private function bindEvents(): void
    {
        if (! $this->connection) {
            return;
        }

        $this->connection->on('message', function (Message $message) {
            if ($this->onactivity) {
                ($this->onactivity)();
            }
            if ($this->onmessage) {
                ($this->onmessage)((string) $message);
            }
        });

        $this->connection->on('close', function (?int $code = null, ?string $reason = null) {
            if ($this->onclose) {
                ($this->onclose)([
                    'code' => $code,
                    'reason' => $reason,
                    'wasClean' => true,
                ]);
            }
        });
    }

    private function triggerOpen(): void
    {
        if ($this->onopen) {
            ($this->onopen)();
        }
    }

    private function triggerError(): void
    {
        if ($this->onerror && $this->error) {
            ($this->onerror)($this->error);
        }
    }

    public function send(mixed $data): void
    {
        if (! $this->connection) {
            return;
        }

        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        $this->connection->send($data);
    }

    public function close(): void
    {
        $this->connection?->close();
    }

    public function ping(): void
    {
        // WebSocket ping - ratchet/pawl handles this automatically
        // but we can send a pusher:ping event
        $this->send(json_encode(['event' => 'pusher:ping', 'data' => []]));
    }
}
