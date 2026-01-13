<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

use Closure;

class HTTPSocket
{
    public SocketHooks $hooks;

    public string $session;

    public URLLocation $location;

    public State $readyState;

    public ?HTTPRequest $stream = null;

    public ?Closure $onopen = null;

    public ?Closure $onerror = null;

    public ?Closure $onclose = null;

    public ?Closure $onmessage = null;

    public ?Closure $onactivity = null;

    public function __construct(SocketHooks $hooks, string $url)
    {
        $this->hooks = $hooks;
        $this->session = self::randomNumber(1000).'/'.self::randomString(8);
        $this->location = self::getLocation($url);
        $this->readyState = State::CONNECTING;
        $this->openStream();
    }

    public function send(mixed $payload): bool
    {
        return $this->sendRaw(json_encode([$payload]));
    }

    public function ping(): void
    {
        $this->hooks->sendHeartbeat($this);
    }

    public function close(int $code, string $reason): void
    {
        $this->onClose($code, $reason, true);
    }

    /**
     * For internal use only.
     */
    public function sendRaw(string $payload): bool
    {
        if ($this->readyState === State::OPEN) {
            // TODO: Implement SockJS transport
            // Runtime::createSocketRequest(
            //     'POST',
            //     self::getUniqueURL(self::getSendURL($this->location, $this->session))
            // )->start($payload);
            return true;
        }

        return false;
    }

    /**
     * For internal use only.
     */
    public function reconnect(): void
    {
        $this->closeStream();
        $this->openStream();
    }

    /**
     * For internal use only.
     */
    public function onClose(int $code, string $reason, bool $wasClean): void
    {
        $this->closeStream();
        $this->readyState = State::CLOSED;
        if ($this->onclose) {
            ($this->onclose)([
                'code' => $code,
                'reason' => $reason,
                'wasClean' => $wasClean,
            ]);
        }
    }

    private function openStream(): void
    {
        // $this->stream = Runtime::createSocketRequest(
        //     'POST',
        //     self::getUniqueURL($this->hooks->getReceiveURL($this->location, $this->session))
        // );
        //
        // $this->stream->bind('chunk', fn($chunk) => $this->onChunk($chunk));
        // $this->stream->bind('finished', fn($status) => $this->hooks->onFinished($this, $status));
        // $this->stream->bind('buffer_too_long', fn() => $this->reconnect());
        //
        // try {
        //     $this->stream->start();
        // } catch (\Throwable $error) {
        //     $this->onError($error);
        //     $this->onClose(1006, 'Could not start streaming', false);
        // }
    }

    private function closeStream(): void
    {
        if ($this->stream) {
            $this->stream->unbind_all();
            $this->stream->close();
            $this->stream = null;
        }
    }

    private static function getLocation(string $url): URLLocation
    {
        preg_match('/([^\?]*)\/*(\??.*)/', $url, $parts);

        return new URLLocation(
            base: $parts[1] ?? '',
            queryString: $parts[2] ?? '',
        );
    }

    private static function randomNumber(int $max): int
    {
        return random_int(0, $max);
    }

    private static function randomString(int $length): string
    {
        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = base_convert((string) self::randomNumber(32), 10, 32);
        }

        return implode('', $result);
    }
}
