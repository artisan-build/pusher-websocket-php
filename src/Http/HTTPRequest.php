<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Http;

use ArtisanBuild\Pusher\Events\Dispatcher;
use ArtisanBuild\Pusher\Runtimes\Runtime;
use Closure;

const MAX_BUFFER_LENGTH = 256 * 1024;

class HTTPRequest extends Dispatcher
{
    public RequestHooks $hooks;

    public string $method;

    public string $url;

    public int $position = 0;

    public ?Ajax $xhr = null;

    public ?Closure $unloader = null;

    public function __construct(RequestHooks $hooks, string $method, string $url)
    {
        parent::__construct();
        $this->hooks = $hooks;
        $this->method = $method;
        $this->url = $url;
    }

    public function start(mixed $payload = null): void
    {
        $this->position = 0;
        $this->xhr = $this->hooks->getRequest($this);

        $this->unloader = fn () => $this->close();
        // Runtime::addUnloadListener($this->unloader);

        $this->xhr->open($this->method, $this->url, true);
        $this->xhr->setRequestHeader('Content-Type', 'application/json');
        $this->xhr->send($payload);
    }

    public function close(): void
    {
        if ($this->unloader) {
            // Runtime::removeUnloadListener($this->unloader);
            $this->unloader = null;
        }
        if ($this->xhr) {
            $this->hooks->abortRequest($this->xhr);
            $this->xhr = null;
        }
    }

    public function onChunk(int $status, string $data): void
    {
        while (true) {
            $chunk = $this->advanceBuffer($data);
            if ($chunk !== null) {
                $this->emit('chunk', ['status' => $status, 'data' => $chunk]);
            } else {
                break;
            }
        }
        if ($this->isBufferTooLong($data)) {
            $this->emit('buffer_too_long');
        }
    }

    private function advanceBuffer(string $buffer): ?string
    {
        $unreadData = substr($buffer, $this->position);
        $endOfLinePosition = strpos($unreadData, "\n");

        if ($endOfLinePosition !== false) {
            $this->position += $endOfLinePosition + 1;

            return substr($unreadData, 0, $endOfLinePosition);
        }

        // chunk is not finished yet, don't move the buffer pointer
        return null;
    }

    private function isBufferTooLong(string $buffer): bool
    {
        return $this->position === strlen($buffer) && strlen($buffer) > MAX_BUFFER_LENGTH;
    }
}
