<?php

declare(strict_types=1);

use function ArtisanBuild\Pusher\Tests\createTestClient;
use function ArtisanBuild\Pusher\Tests\runEventLoop;

describe('Public Channels', function () {
    it('can subscribe to a public channel', function () {
        $pusher = createTestClient();
        $subscribed = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$subscribed) {
            $channel = $pusher->subscribe('my-channel');
            $channel->bind('pusher:subscription_succeeded', function () use (&$subscribed) {
                $subscribed = true;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($subscribed)->toBeTrue();
    });

    it('returns the channel object when subscribing', function () {
        $pusher = createTestClient();
        $channel = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$channel) {
            $channel = $pusher->subscribe('test-channel');
        });

        $pusher->connect();
        runEventLoop(timeout: 2.0);

        expect($channel)->not->toBeNull();
        expect($channel->name)->toBe('test-channel');
    });

    it('can retrieve a subscribed channel by name', function () {
        $pusher = createTestClient();
        $foundChannel = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$foundChannel) {
            $pusher->subscribe('lookup-channel');
            $foundChannel = $pusher->channel('lookup-channel');
        });

        $pusher->connect();
        runEventLoop(timeout: 2.0);

        expect($foundChannel)->not->toBeNull();
        expect($foundChannel->name)->toBe('lookup-channel');
    });

    it('returns null for non-existent channel', function () {
        $pusher = createTestClient();
        $channel = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$channel) {
            $channel = $pusher->channel('non-existent');
        });

        $pusher->connect();
        runEventLoop(timeout: 2.0);

        expect($channel)->toBeNull();
    });

    it('can unsubscribe from a channel', function () {
        $pusher = createTestClient();
        $channelAfterUnsub = 'not-checked';

        $pusher->connection->bind('connected', function () use ($pusher, &$channelAfterUnsub) {
            $channel = $pusher->subscribe('temp-channel');
            $channel->bind('pusher:subscription_succeeded', function () use ($pusher, &$channelAfterUnsub) {
                $pusher->unsubscribe('temp-channel');
                $channelAfterUnsub = $pusher->channel('temp-channel');
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($channelAfterUnsub)->toBeNull();
    });

    it('can list all subscribed channels', function () {
        $pusher = createTestClient();
        $channels = [];

        $pusher->connection->bind('connected', function () use ($pusher, &$channels) {
            $pusher->subscribe('channel-1');
            $pusher->subscribe('channel-2');
            $pusher->subscribe('channel-3');
            $channels = $pusher->allChannels();
        });

        $pusher->connect();
        runEventLoop(timeout: 2.0);

        expect($channels)->toHaveCount(3);
    });
});

describe('Private Channels', function () {
    it('can subscribe to a private channel with valid auth', function () {
        $pusher = createTestClient(withAuth: true);
        $subscribed = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$subscribed) {
            $channel = $pusher->subscribe('private-test-channel');
            $channel->bind('pusher:subscription_succeeded', function () use (&$subscribed) {
                $subscribed = true;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($subscribed)->toBeTrue();
    });

    it('emits subscription_error for private channel without auth', function () {
        $pusher = createTestClient(withAuth: false);
        $error = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$error) {
            $channel = $pusher->subscribe('private-no-auth');
            $channel->bind('pusher:subscription_error', function ($data) use (&$error) {
                $error = $data;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($error)->not->toBeNull();
    });

    it('can send client events on private channels', function () {
        $pusher = createTestClient(withAuth: true);
        $eventSent = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$eventSent) {
            $channel = $pusher->subscribe('private-client-events');
            $channel->bind('pusher:subscription_succeeded', function () use ($channel, &$eventSent) {
                // Client events must be prefixed with 'client-'
                $result = $channel->trigger('client-my-event', ['message' => 'hello']);
                $eventSent = $result !== false;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($eventSent)->toBeTrue();
    });

    it('rejects client events without client- prefix', function () {
        $pusher = createTestClient(withAuth: true);

        $pusher->connection->bind('connected', function () use ($pusher) {
            $channel = $pusher->subscribe('private-bad-event');
            $channel->bind('pusher:subscription_succeeded', function () use ($channel) {
                $channel->trigger('invalid-event', ['data' => 'test']);
            });
        });

        $pusher->connect();

        expect(fn () => runEventLoop(timeout: 3.0))
            ->toThrow(ArtisanBuild\Pusher\Errors\BadEventName::class);
    });
});

describe('Presence Channels', function () {
    it('can subscribe to a presence channel', function () {
        $pusher = createTestClient(withAuth: true);
        $subscribed = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$subscribed) {
            $channel = $pusher->subscribe('presence-test-room');
            $channel->bind('pusher:subscription_succeeded', function () use (&$subscribed) {
                $subscribed = true;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($subscribed)->toBeTrue();
    });

    it('receives member information on presence channel subscription', function () {
        $pusher = createTestClient(withAuth: true);
        $members = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$members) {
            $channel = $pusher->subscribe('presence-member-test');
            $channel->bind('pusher:subscription_succeeded', function ($data) use ($channel, &$members) {
                $members = $channel->members;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($members)->not->toBeNull();
    });

    it('can access current user on presence channel', function () {
        $pusher = createTestClient(withAuth: true);
        $me = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$me) {
            $channel = $pusher->subscribe('presence-me-test');
            $channel->bind('pusher:subscription_succeeded', function () use ($channel, &$me) {
                $me = $channel->members->me;
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0);

        expect($me)->not->toBeNull();
    });
});
