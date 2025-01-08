<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Auth\Options;

use Closure;

class InternalAuthOptions
{
    // export interface InternalAuthOptions {
    //     transport: 'ajax' | 'jsonp';
    //     endpoint: string;
    //     params?: any;
    //     headers?: any;
    //     paramsProvider?: () => any;
    //     headersProvider?: () => any;
    // }

    public function __construct(
        public string $transport,
        public string $endpoint,
        public array $params = [],
        public array $headers = [],
        public ?Closure $paramsProvider = null,
        public ?Closure $headersProvider = null,
    ) {}
}
