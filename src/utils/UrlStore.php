<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Utils;

/**
 * A place to store help URLs for error messages etc
 */
class UrlStore
{
    const BASE_URL = 'https://pusher.com';

    const URLS = [
        'authenticationEndpoint' => [
            'path' => '/docs/channels/server_api/authenticating_users',
        ],
        'authorizationEndpoint' => [
            'path' => '/docs/channels/server_api/authorizing-users/',
        ],
        'javascriptQuickStart' => [
            'path' => '/docs/javascript_quick_start',
        ],
        'triggeringClientEvents' => [
            'path' => '/docs/client_api_guide/client_events#trigger-events',
        ],
        'encryptedChannelSupport' => [
            'fullUrl' => 'https://github.com/pusher/pusher-js/tree/cc491015371a4bde5743d1c87a0fbac0feb53195#encrypted-channel-support',
        ],
    ];

    /** Builds a consistent string with links to pusher documentation
     *
     * @param  string  $key  - relevant key in self::URLS
     * @return string suffix string to append to log message
     */
    public static function buildLogSuffix(string $key): string
    {
        if (! array_key_exists($key, self::URLS)) {
            return '';
        }

        $urlPrefix = 'See:';
        $urlObj = (object) self::URLS[$key];

        $url = '';
        if ($urlObj->fullUrl) {
            $url = $urlObj->fullUrl;
        } elseif ($urlObj->path) {
            $url = self::BASE_URL.$urlObj->path;
        }

        if ($url === '') {
            return '';
        }

        return "{$urlPrefix} {$url}";
    }
}
