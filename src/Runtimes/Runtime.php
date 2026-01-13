<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Runtimes;

use ArtisanBuild\Pusher\Auth\AuthTransport;
use ArtisanBuild\Pusher\Auth\AuthTransports;
use ArtisanBuild\Pusher\Config;
use ArtisanBuild\Pusher\Runtimes\Auth\HttpAuth;
use ArtisanBuild\Pusher\Strategies\Strategy;
use ArtisanBuild\Pusher\Strategies\StrategyOptions;
use ArtisanBuild\Pusher\Strategies\TransportStrategy;
use ArtisanBuild\Pusher\Timeline\TimelineTransport;
use ArtisanBuild\Pusher\Transports\Transport;
use ArtisanBuild\Pusher\Transports\WebSocketTransportHooks;

// TODO: abstract this
class Runtime
{
    private static ?AuthTransports $authorizers = null;

    // setup(PusherClass: {
    //     new (key: string, options: any): Pusher;
    //     ready(): void;
    // }): void;

    // getProtocol(): string;

    public static function getAuthorizers(): AuthTransports
    {
        if (self::$authorizers === null) {
            self::$authorizers = new AuthTransports([
                'ajax' => new HttpAuth,
            ]);
        }

        return self::$authorizers;
    }

    /**
     * Register a custom auth transport.
     *
     * Useful for testing - register a mock transport without HTTP.
     */
    public static function registerAuthTransport(string $name, AuthTransport $transport): void
    {
        self::getAuthorizers()[$name] = $transport;
    }

    public static function getLocalStorage(): ?\ArrayAccess
    {
        // In PHP we don't have browser localStorage
        // Return null to skip caching functionality
        return null;
    }

    public static function getTransports(): array
    {
        // TODO: return actual transports table
        return [];
    }

    /**
     * Returns the default connection strategy.
     */
    public static function getDefaultStrategy(Config $config, array $options): Strategy
    {
        $wsTransport = new Transport(new WebSocketTransportHooks);

        $strategyOptions = new StrategyOptions;
        $strategyOptions->key = $options['key'] ?? '';
        $strategyOptions->timeline = $options['timeline'] ?? null;
        $strategyOptions->useTLS = $options['useTLS'] ?? $config->useTLS;
        $strategyOptions->hostNonTLS = $config->wsHost.':'.$config->wsPort;
        $strategyOptions->hostTLS = $config->wsHost.':'.$config->wssPort;

        return new TransportStrategy(
            name: 'ws',
            priority: 3,
            transport: $wsTransport,
            options: $strategyOptions,
        );
    }

    public static TimelineTransport $TimelineTransport;

    // createXHR(): Ajax;

    // createWebSocket(url: string): Socket;

    // getNetwork(): Reachability;

    // getDefaultStrategy(
    // config: Config,
    // options: StrategyOptions,
    // defineTransport: Function
    // ): Strategy;

    // Transports: TransportsTable;

    // getWebSocketAPI(): new (url: string) => Socket;

    // getXHRAPI(): new () => Ajax;

    // addUnloadListener(listener: Function): void;

    // removeUnloadListener(listener: Function): void;

    // transportConnectionInitializer: Function;

    // HTTPFactory: HTTPFactory;

    // isXHRSupported(): boolean;

    // createSocketRequest(method: string, url: string): HTTPRequest;

    // randomInt(max: number): number;

    /**
     * these methods/types are only implemented in the web Runtime, so they're
     * optional but must be included in the interface
     */
    // getDocument?(): Document;
    // createScriptRequest?(url: string): any;
    // createJSONPRequest?(url: string, data: any): JSONPRequest;
    // ScriptReceivers?: any;
    // isXDRSupported?(useTLS?: boolean): boolean;
}
