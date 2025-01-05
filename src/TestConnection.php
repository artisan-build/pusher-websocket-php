<?php

namespace ArtisanBuild\Pusher;

use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Message;
use React\Promise\Deferred;
use React\Promise\Timer\TimeoutException;

use function React\Async\await;
use function React\Promise\Timer\timeout;

class TestConnection
{
    public string $socketId;

    /**
     * Messages received by the connection.
     *
     * @var array<int, string>
     */
    public array $receivedMessages = [];

    /**
     * Create a new test connection instance.
     */
    public function __construct(public WebSocket $connection)
    {
        $connection->on('message', function (Message $message) {
            $this->receivedMessages[] = $message;
        });

        $connection->on('close', function (mixed $code, Message $message) {
            $this->receivedMessages[] = $message;
        });
    }

    /**
     * Get the socket ID of the connection.
     */
    public function socketId(): string
    {
        return $this->socketId;
    }

    /**
     * Await all messages to the connection to be resolved.
     */
    public function await(): mixed
    {
        $promise = new Deferred;

        $this->connection->on('message', function (Message $message) use ($promise) {
            $promise->resolve($message);
        });

        $this->connection->on('close', function (mixed $code, Message $message) use ($promise) {
            $promise->resolve($message);
        });

        return await(
            timeout($promise->promise(), 2)
                ->then(
                    fn (Message $message) => (string) $message,
                    fn (TimeoutException $error) => false
                )
        );
    }

    /**
     * Assert that the connection received the given message.
     */
    public function assertReceived(string $message, ?int $count = null): void
    {
        if (! in_array($message, $this->receivedMessages) || $count !== null) {
            $this->await();
        }

        if ($count) {
            $matches = array_filter($this->receivedMessages, fn (string $m) => $m === $message);

            expect($matches)->toHaveCount($count);
        }

        expect($this->receivedMessages)->toContain($message);
    }

    /**
     * Proxy method calls to the connection.
     */
    public function __call(string $method, array $arguments)
    {
        return $this->connection->{$method}(...$arguments);
    }
}
