<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher;

use Illuminate\Support\Str;
use Ratchet\RFC6455\Messaging\Message;
use React\Promise\Deferred;

use function Ratchet\Client\connect as connector;
use function React\Async\await;

class NaivePusher
{
    public TestConnection $connection;

    final public function __construct(
        string $key,
        array $options,
    ) {
        $promise = new Deferred;

        $connection = await(
            connector(
                url: "{$options['scheme']}://{$options['host']}:{$options['port']}/app/{$key}?client=php&version=7.0.3&protocol=5",
                headers: $options['headers'],
            ),
        );

        $connection = new TestConnection($connection);

        $connection->on('message', function (Message $message) use ($promise) {
            $promise->resolve((string) $message);
        });

        $message = await($promise->promise());

        expect($message)->toContain('connection_established');

        $connection->socketId = json_decode(json_decode($message)->data)->socket_id;

        $this->connection = $connection;
    }

    /**
     * Send a message to the connected client.
     */
    public function send(array $message): mixed
    {
        $this->connection->send(json_encode($message));

        return $this->connection->await();
    }

    /**
     * Subscribe to a channel.
     */
    public function subscribe(
        string $channel,
        ?array $data = [],
        ?string $auth = null,
    ): string {
        $data = ! empty($data) ? json_encode($data) : null;

        if (! $auth && Str::startsWith($channel, ['private-', 'presence-'])) {
            // $auth = validAuth($this->connection->socketId(), $channel, $data);
        }

        return $this->send([
            'event' => 'pusher:subscribe',
            'data' => array_filter([
                'channel' => $channel,
                'channel_data' => $data,
                'auth' => $auth,
            ]),
        ]);
    }

    /**
     * Unsubscribe to a channel.
     */
    public function unsubscribe(string $channel): ?string
    {
        return $this->send([
            'event' => 'pusher:unsubscribe',
            'data' => ['channel' => $channel],
        ]);
    }

    /**
     * Disconnect the connected client.
     */
    public function disconnect(): string
    {
        $promise = new Deferred;

        $this->connection->on('close', function () use ($promise) {
            $promise->resolve('Connection Closed.');
        });

        $this->connection->close();

        return await($promise->promise());
    }

    public function signin(): void
    {
        throw new \Exception('Not implemented.');
    }
}
