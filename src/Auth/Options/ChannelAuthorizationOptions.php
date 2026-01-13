<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

use Closure;

class ChannelAuthorizationOptions
{
    // export interface AuthOptionsT<AuthHandler> {
    //     transport: 'ajax' | 'jsonp';
    //     endpoint: string;
    //     params?: any;
    //     headers?: any;
    //     paramsProvider?: () => any;
    //     headersProvider?: () => any;
    //     customHandler?: AuthHandler;
    // }
    // export declare type ChannelAuthorizationOptions = AuthOptionsT<
    //     ChannelAuthorizationHandler
    // >;

    public function __construct(
        public string $transport,
        public string $endpoint,
        public array $params = [],
        public array $headers = [],
        public ?Closure $paramsProvider = null,
        public ?Closure $headersProvider = null,
        public ?Closure $customHandler = null,
    ) {}
}
