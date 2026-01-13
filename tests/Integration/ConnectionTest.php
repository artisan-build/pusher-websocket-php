<?php

declare(strict_types=1);

use ArtisanBuild\Pusher\Options;
use ArtisanBuild\Pusher\Pusher;

use function ArtisanBuild\Pusher\Tests\createTestClient;
use function ArtisanBuild\Pusher\Tests\runEventLoop;

describe('Pusher Connection', function () {
    it('can establish a connection to the server', function () {
        $pusher = createTestClient();
        $connected = false;

        $pusher->connection->bind('connected', function () use (&$connected) {
            $connected = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$connected) {
            return $connected;
        });

        expect($connected)->toBeTrue();
        expect($pusher->connection->state)->toBe('connected');
    });

    it('receives a socket_id upon connection', function () {
        $pusher = createTestClient();
        $socketId = null;

        $pusher->connection->bind('connected', function () use ($pusher, &$socketId) {
            $socketId = $pusher->connection->socket_id;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$socketId) {
            return $socketId !== null;
        });

        expect($socketId)->not->toBeNull();
        expect($socketId)->toBeString();
        expect($socketId)->toMatch('/^\d+\.\d+$/'); // Format: "123456.789012"
    });

    it('can disconnect from the server', function () {
        $pusher = createTestClient();
        $disconnected = false;

        $pusher->connection->bind('connected', function () use ($pusher) {
            $pusher->disconnect();
        });

        $pusher->connection->bind('disconnected', function () use (&$disconnected) {
            $disconnected = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$disconnected) {
            return $disconnected;
        });

        expect($disconnected)->toBeTrue();
        expect($pusher->connection->state)->toBe('disconnected');
    });

    it('transitions through expected connection states', function () {
        $pusher = createTestClient();
        $states = [];
        $connected = false;

        $pusher->connection->bind('state_change', function ($data) use (&$states) {
            $states[] = $data['current'];
        });

        $pusher->connection->bind('connected', function () use (&$connected) {
            $connected = true;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$connected) {
            return $connected;
        });

        expect($states)->toContain('connecting');
        expect($states)->toContain('connected');
    });

    it('can reconnect after disconnection', function () {
        $pusher = createTestClient();
        $connectionCount = 0;

        $pusher->connection->bind('connected', function () use (&$connectionCount) {
            $connectionCount++;
        });

        $pusher->connect();
        runEventLoop(until: function () use (&$connectionCount) {
            return $connectionCount >= 1;
        });

        expect($connectionCount)->toBe(1);

        $pusher->disconnect();
        runEventLoop(timeout: 0.1);

        $pusher->connect();
        runEventLoop(until: function () use (&$connectionCount) {
            return $connectionCount >= 2;
        });

        expect($connectionCount)->toBe(2);
    });

    it('requires an app key to instantiate', function () {
        expect(fn () => new Pusher('', new Options(cluster: 'mt1')))
            ->toThrow(RuntimeException::class, 'You must pass your app key');
    });

    it('requires a cluster option', function () {
        expect(fn () => new Pusher('test-key', new Options))
            ->toThrow(RuntimeException::class, 'Options object must provide a cluster');
    });
});
