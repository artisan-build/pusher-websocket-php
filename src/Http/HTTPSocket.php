<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

use ArtisanBuild\Pusher\Runtimes\Runtime;
use Closure;

class HTTPSocket
{
    private static int $autoIncrement = 1;

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
            try {
                // Runtime::createSocketRequest(
                //     'POST',
                //     self::getUniqueURL(self::getSendURL($this->location, $this->session))
                // )->start($payload);
                return true;
            } catch (\Throwable $e) {
                return false;
            }
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

    private function onChunk(array $chunk): void
    {
        if ($chunk['status'] !== 200) {
            return;
        }
        if ($this->readyState === State::OPEN) {
            $this->onActivity();
        }

        $type = substr($chunk['data'], 0, 1);
        switch ($type) {
            case 'o':
                $payload = json_decode(substr($chunk['data'], 1) ?: '{}', true);
                $this->onOpen($payload);
                break;
            case 'a':
                $payload = json_decode(substr($chunk['data'], 1) ?: '[]', true);
                foreach ($payload as $item) {
                    $this->onEvent($item);
                }
                break;
            case 'm':
                $payload = json_decode(substr($chunk['data'], 1) ?: 'null', true);
                $this->onEvent($payload);
                break;
            case 'h':
                $this->hooks->onHeartbeat($this);
                break;
            case 'c':
                $payload = json_decode(substr($chunk['data'], 1) ?: '[]', true);
                $this->onClose($payload[0], $payload[1], true);
                break;
        }
    }

    private function onOpen(?array $options): void
    {
        if ($this->readyState === State::CONNECTING) {
            if ($options && isset($options['hostname'])) {
                $this->location->base = self::replaceHost($this->location->base, $options['hostname']);
            }
            $this->readyState = State::OPEN;

            if ($this->onopen) {
                ($this->onopen)();
            }
        } else {
            $this->onClose(1006, 'Server lost session', true);
        }
    }

    private function onEvent(mixed $event): void
    {
        if ($this->readyState === State::OPEN && $this->onmessage) {
            ($this->onmessage)(['data' => $event]);
        }
    }

    private function onActivity(): void
    {
        if ($this->onactivity) {
            ($this->onactivity)();
        }
    }

    private function onError(mixed $error): void
    {
        if ($this->onerror) {
            ($this->onerror)($error);
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
            $this->stream->unbindAll();
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

    private static function getSendURL(URLLocation $url, string $session): string
    {
        return $url->base.'/'.$session.'/xhr_send';
    }

    private static function getUniqueURL(string $url): string
    {
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url.$separator.'t='.time().'&n='.self::$autoIncrement++;
    }

    private static function replaceHost(string $url, string $hostname): string
    {
        preg_match('/(https?:\/\/)([^\/:]+)((\/|:)?.*)/', $url, $urlParts);

        return $urlParts[1].$hostname.$urlParts[3];
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
