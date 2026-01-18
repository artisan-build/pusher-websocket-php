# Pusher Channels WebSocket Client for PHP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/artisan-build/pusher-websocket-php.svg?style=flat-square)](https://packagist.org/packages/artisan-build/pusher-websocket-php)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/artisan-build/pusher-websocket-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/artisan-build/pusher-websocket-php/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/artisan-build/pusher-websocket-php.svg?style=flat-square)](https://packagist.org/packages/artisan-build/pusher-websocket-php)

A PHP WebSocket client for Pusher Channels and Pusher-compatible servers. This is a port of [pusher-js](https://github.com/pusher/pusher-js) implementing Pusher Protocol v7.

## When to Use This Package

This package is a **low-level WebSocket client** for connecting to Pusher-compatible servers from PHP. It's designed for:

- CLI applications that need real-time communication
- Background workers or daemons
- Non-HTTP PHP processes that need WebSocket connections
- Building higher-level abstractions

**Laravel users:** You probably want [artisan-build/resonance](https://github.com/artisan-build/resonance) instead, which provides Laravel-specific integrations and brings this package as a dependency.

## Requirements

- PHP 8.3 or higher
- A Pusher-compatible WebSocket server:
  - [Pusher Channels](https://pusher.com/channels)
  - [Soketi](https://soketi.app)
  - [Laravel Reverb](https://reverb.laravel.com)

## Installation

```bash
composer require artisan-build/pusher-websocket-php
```

## Usage

```php
use ArtisanBuild\Pusher\Pusher;
use ArtisanBuild\Pusher\Options;

$pusher = new Pusher('your-app-key', new Options(
    cluster: 'mt1',
    wsHost: '127.0.0.1',
    wsPort: 6001,
    forceTLS: false,
));

// Subscribe to a channel
$channel = $pusher->subscribe('my-channel');

// Listen for events
$channel->bind('my-event', function ($data) {
    echo "Received: " . json_encode($data) . "\n";
});

// Connect to the server
$pusher->connect();
```

### Subscribing to Channels

```php
// Public channel
$channel = $pusher->subscribe('news');

// Private channel (requires authentication)
$privateChannel = $pusher->subscribe('private-user-123');

// Presence channel (requires authentication)
$presenceChannel = $pusher->subscribe('presence-chat-room');
```

### Binding to Events

```php
// Bind to a specific event
$channel->bind('message', function ($data) {
    // Handle the event
});

// Bind to all events on a channel
$channel->bind_global(function ($event, $data) {
    echo "Event: {$event}\n";
});

// Unbind
$channel->unbind('message');
```

### Configuration Options

```php
new Options(
    cluster: 'mt1',           // Pusher cluster (required for Pusher, empty for self-hosted)
    wsHost: '127.0.0.1',      // WebSocket host
    wsPort: 6001,             // WebSocket port (non-TLS)
    wssPort: 443,             // WebSocket port (TLS)
    forceTLS: true,           // Force TLS connection
    activityTimeout: 120000,  // Milliseconds before sending ping
    pongTimeout: 30000,       // Milliseconds to wait for pong
    channelAuthorization: [   // For private/presence channels
        'transport' => 'http',
        'endpoint' => 'https://your-app.com/broadcasting/auth',
        'headers' => [
            'Authorization' => 'Bearer your-token',
        ],
    ],
);
```

### Channel Authorization

Private and presence channels require authorization. The `http` transport makes a POST request to your auth endpoint:

```php
$pusher = new Pusher('app-key', new Options(
    wsHost: '127.0.0.1',
    wsPort: 8080,
    channelAuthorization: [
        'transport' => 'http',
        'endpoint' => 'https://your-app.com/broadcasting/auth',
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
    ],
));

// Now private channels will authenticate automatically
$channel = $pusher->subscribe('private-user-123');
```

For custom authorization logic, use a `customHandler`:

```php
channelAuthorization: [
    'customHandler' => function ($params, $callback) {
        // $params->socketId - the socket ID
        // $params->channelName - the channel being authorized

        // Make your own auth request...
        $authData = myCustomAuth($params);

        // Call back with result
        $callback(null, new ChannelAuthorizationData(auth: $authData['auth']));
    },
],
```

## Development

### Prerequisites

You'll need a Pusher-compatible WebSocket server running locally for integration tests. Options include:

**Soketi** (recommended for development):
```bash
# Via npm (requires Node.js 14-18)
npx soketi start

# Via Docker
docker run -p 6001:6001 quay.io/soketi/soketi:1.6-16-debian
```

**Laravel Reverb** (if using Laravel Herd):
```bash
php artisan reverb:start
```

### Environment Setup

Copy the example environment file and configure your server:

```bash
cp .env.example .env
```

Edit `.env` with your server details:

```env
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=ws

PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_CLUSTER=mt1
```

### Running Tests

```bash
# Unit and feature tests (no server required)
composer test

# Integration tests (requires running server)
composer test:integration

# All tests
composer test:all

# With coverage
composer test:coverage

# Type coverage
composer test:types
```

### Code Quality

```bash
# Linting
composer lint

# Static analysis
composer stan
```

## Architecture

This package is built on [ReactPHP](https://reactphp.org/) for asynchronous I/O:

- `ratchet/pawl` - WebSocket client
- `react/event-loop` - Event loop
- `react/promise` - Promises/async

The codebase follows the structure of pusher-js, ported to idiomatic PHP with full type coverage.

## Contributing

Contributions are welcome! Please ensure:

1. Tests pass: `composer test`
2. Code style is correct: `composer lint`
3. Static analysis passes: `composer stan`
4. Type coverage remains at 100%: `composer test:types`

## Security Vulnerabilities

Please report security vulnerabilities to [security@artisan.build](mailto:security@artisan.build).

## Credits

- [Len Woodward](https://github.com/ProjektGopher)
- [Ed Grosvenor](https://github.com/edgrosvenor)
- [All Contributors](../../contributors)

This package is a PHP port of [pusher-js](https://github.com/pusher/pusher-js) by [Pusher](https://pusher.com).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
