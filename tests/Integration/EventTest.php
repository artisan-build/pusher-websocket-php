<?php

declare(strict_types=1);

use function ArtisanBuild\Pusher\Tests\createTestClient;
use function ArtisanBuild\Pusher\Tests\runEventLoop;
use function ArtisanBuild\Pusher\Tests\triggerServerEvent;

describe('Event Binding', function () {
    it('can bind to a specific event on a channel', function () {
        $pusher = createTestClient();
        $channel = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$channel) {
            $channel = $pusher->subscribe('bind-test');
            $channel->bind('my-event', function ($data) {
                // Event handler
            });
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$channel) { return $channel !== null; });

        // The channel binding should be set up even if no event is received
        expect($pusher->channel('bind-test'))->not->toBeNull();
    });

    it('can bind multiple callbacks to the same event', function () {
        $pusher = createTestClient();
        $callCount = 0;

        $pusher->connection->bind('connected', function () use ($pusher, &$callCount) {
            $channel = $pusher->subscribe('multi-bind-test');

            $channel->bind('test-event', function () use (&$callCount) {
                $callCount++;
            });

            $channel->bind('test-event', function () use (&$callCount) {
                $callCount++;
            });

            // Manually emit to test multiple bindings
            $channel->emit('test-event', ['data' => 'test']);
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$callCount) { return $callCount >= 2; });

        expect($callCount)->toBe(2);
    });

    it('can bind globally to all events on a channel', function () {
        $pusher = createTestClient();
        $events = [];

        $pusher->connection->bind('connected', function () use ($pusher, &$events) {
            $channel = $pusher->subscribe('global-bind-test');

            $channel->bind_global(function ($eventName, $data) use (&$events) {
                $events[] = $eventName;
            });

            // Emit some events
            $channel->emit('event-one', ['data' => 1]);
            $channel->emit('event-two', ['data' => 2]);
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$events) { return count($events) >= 2; });

        expect($events)->toContain('event-one');
        expect($events)->toContain('event-two');
    });

    it('passes event data to the callback', function () {
        $pusher = createTestClient();
        $receivedData = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$receivedData) {
            $channel = $pusher->subscribe('data-test');

            $channel->bind('test-event', function ($data) use (&$receivedData) {
                $receivedData = $data;
            });

            $channel->emit('test-event', ['message' => 'hello', 'count' => 42]);
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$receivedData) { return $receivedData !== null; });

        expect($receivedData)->toBe(['message' => 'hello', 'count' => 42]);
    });
});

describe('Event Unbinding', function () {
    it('can unbind a specific callback from an event', function () {
        $pusher = createTestClient();
        $callCount = 0;
        $done = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$callCount, &$done) {
            $channel = $pusher->subscribe('unbind-test');

            $callback = function () use (&$callCount) {
                $callCount++;
            };

            $channel->bind('test-event', $callback);
            $channel->emit('test-event'); // Should call callback

            $channel->unbind('test-event', $callback);
            $channel->emit('test-event'); // Should NOT call callback
            $done = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$done) { return $done; });

        expect($callCount)->toBe(1);
    });

    it('can unbind all callbacks from a specific event', function () {
        $pusher = createTestClient();
        $callCount = 0;
        $done = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$callCount, &$done) {
            $channel = $pusher->subscribe('unbind-all-event-test');

            $channel->bind('test-event', function () use (&$callCount) {
                $callCount++;
            });

            $channel->bind('test-event', function () use (&$callCount) {
                $callCount++;
            });

            $channel->emit('test-event'); // Should call both callbacks

            $channel->unbind('test-event');
            $channel->emit('test-event'); // Should call neither
            $done = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$done) { return $done; });

        expect($callCount)->toBe(2);
    });

    it('can unbind all callbacks from all events', function () {
        $pusher = createTestClient();
        $callCount = 0;
        $done = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$callCount, &$done) {
            $channel = $pusher->subscribe('unbind-all-test');

            $channel->bind('event-1', function () use (&$callCount) {
                $callCount++;
            });

            $channel->bind('event-2', function () use (&$callCount) {
                $callCount++;
            });

            $channel->emit('event-1');
            $channel->emit('event-2');
            // callCount is now 2

            $channel->unbind_all();
            $channel->emit('event-1');
            $channel->emit('event-2');
            // callCount should still be 2
            $done = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$done) { return $done; });

        expect($callCount)->toBe(2);
    });

    it('can unbind global callbacks', function () {
        $pusher = createTestClient();
        $callCount = 0;
        $done = false;

        $pusher->connection->bind('connected', function () use ($pusher, &$callCount, &$done) {
            $channel = $pusher->subscribe('unbind-global-test');

            $globalCallback = function () use (&$callCount) {
                $callCount++;
            };

            $channel->bind_global($globalCallback);
            $channel->emit('any-event'); // Should call global callback

            $channel->unbind_global($globalCallback);
            $channel->emit('any-event'); // Should NOT call global callback
            $done = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$done) { return $done; });

        expect($callCount)->toBe(1);
    });
});

describe('Global Event Binding (Pusher instance)', function () {
    it('can bind to events globally on the Pusher instance', function () {
        $pusher = createTestClient();
        $events = [];

        $pusher->bind('my-event', function ($data) use (&$events) {
            $events[] = $data;
        });

        $pusher->connection->bind('connected', function () use ($pusher) {
            // Emit via the global emitter
            $pusher->global_emitter->emit('my-event', ['source' => 'global']);
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$events) { return count($events) >= 1; });

        expect($events)->toHaveCount(1);
        expect($events[0])->toBe(['source' => 'global']);
    });

    it('can unbind from global events on the Pusher instance', function () {
        $pusher = createTestClient();
        $callCount = 0;

        $callback = function () use (&$callCount) {
            $callCount++;
        };

        $pusher->bind('test-event', $callback);
        $pusher->global_emitter->emit('test-event');

        $pusher->unbind('test-event', $callback);
        $pusher->global_emitter->emit('test-event');

        expect($callCount)->toBe(1);
    });

    it('can bind_global on the Pusher instance', function () {
        $pusher = createTestClient();
        $events = [];

        $pusher->bind_global(function ($eventName, $data) use (&$events) {
            $events[] = $eventName;
        });

        $pusher->global_emitter->emit('event-a', []);
        $pusher->global_emitter->emit('event-b', []);

        expect($events)->toContain('event-a');
        expect($events)->toContain('event-b');
    });
});

describe('Event Reception', function () {
    it('receives events triggered from the server', function () {
        $pusher = createTestClient();
        $receivedEvent = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$receivedEvent) {
            $channel = $pusher->subscribe('server-events');

            // Bind event handler BEFORE subscription_succeeded
            $channel->bind('test-event', function ($data) use (&$receivedEvent) {
                $receivedEvent = $data;
            });

            $channel->bind('pusher:subscription_succeeded', function () {
                // Trigger an event from the server side
                triggerServerEvent('server-events', 'test-event', ['hello' => 'world']);
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0, until: function () use (&$receivedEvent) { return $receivedEvent !== null; });

        expect($receivedEvent)->toBe(['hello' => 'world']);
    });

    it('receives events on the correct channel only', function () {
        $pusher = createTestClient();
        $channel1Events = [];
        $channel2Events = [];

        $pusher->connection->bind('connected', function () use ($pusher, &$channel1Events, &$channel2Events) {
            $channel1 = $pusher->subscribe('channel-1');
            $channel2 = $pusher->subscribe('channel-2');

            // Bind event handlers BEFORE subscription_succeeded
            $channel1->bind('my-event', function ($data) use (&$channel1Events) {
                $channel1Events[] = $data;
            });

            $channel2->bind('my-event', function ($data) use (&$channel2Events) {
                $channel2Events[] = $data;
            });

            // Trigger event only on channel-1
            $channel1->bind('pusher:subscription_succeeded', function () {
                triggerServerEvent('channel-1', 'my-event', ['target' => 'channel-1']);
            });
        });

        $pusher->connect();
        runEventLoop(timeout: 3.0, until: function () use (&$channel1Events) { return count($channel1Events) >= 1; });

        expect($channel1Events)->toHaveCount(1);
        expect($channel2Events)->toHaveCount(0);
    });
});

describe('Fluent Interface', function () {
    it('supports method chaining on bind', function () {
        $pusher = createTestClient();
        $channel = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$channel) {
            $channel = $pusher->subscribe('chain-test')
                ->bind('event-1', fn () => null)
                ->bind('event-2', fn () => null)
                ->bind('event-3', fn () => null);
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$channel) { return $channel !== null; });

        expect($channel->name)->toBe('chain-test');
    });

    it('supports method chaining on Pusher bind methods', function () {
        $pusher = createTestClient();

        $result = $pusher
            ->bind('event-1', fn () => null)
            ->bind('event-2', fn () => null)
            ->bind_global(fn () => null);

        expect($result)->toBeInstanceOf(ArtisanBuild\Pusher\Pusher::class);
    });
});
