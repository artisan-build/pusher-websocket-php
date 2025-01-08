<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Runtimes;

use ArtisanBuild\Pusher\Auth\AuthTransports;
use ArtisanBuild\Pusher\Runtimes\Auth\HttpAuth;

// TODO: abstract this
class Runtime
{
    // setup(PusherClass: {
    //     new (key: string, options: any): Pusher;
    //     ready(): void;
    // }): void;

    // getProtocol(): string;

    public static function getAuthorizers(): AuthTransports
    {
        return new AuthTransports([
            'ajax' => new HttpAuth,
        ]);
    }

    // getLocalStorage(): any;

    // TimelineTransport: TimelineTransport;

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
