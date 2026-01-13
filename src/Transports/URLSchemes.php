<?php

declare(strict_types=1);

namespace ArtisanBuild\Pusher\Transports;

use ArtisanBuild\Pusher\Defaults;

class URLSchemes
{
    public static function ws(): URLScheme
    {
        return new class implements URLScheme {
            public function getInitial(string $key, mixed $params): string
            {
                $path = ($params->httpPath ?? '').URLSchemes::getGenericPath($key, 'flash=false');

                return URLSchemes::getGenericURL('ws', $params, $path);
            }

            public function getPath(string $key, mixed $options): ?string
            {
                return null;
            }
        };
    }

    public static function http(): URLScheme
    {
        return new class implements URLScheme {
            public function getInitial(string $key, mixed $params): string
            {
                $path = ($params->httpPath ?? '/pusher').URLSchemes::getGenericPath($key);

                return URLSchemes::getGenericURL('http', $params, $path);
            }

            public function getPath(string $key, mixed $options): ?string
            {
                return null;
            }
        };
    }

    public static function sockjs(): URLScheme
    {
        return new class implements URLScheme {
            public function getInitial(string $key, mixed $params): string
            {
                return URLSchemes::getGenericURL('http', $params, $params->httpPath ?? '/pusher');
            }

            public function getPath(string $key, mixed $options): ?string
            {
                return URLSchemes::getGenericPath($key);
            }
        };
    }

    public static function getGenericURL(string $baseScheme, mixed $params, string $path): string
    {
        $scheme = $baseScheme.($params->useTLS ? 's' : '');
        $host = $params->useTLS ? $params->hostTLS : $params->hostNonTLS;

        return $scheme.'://'.$host.$path;
    }

    public static function getGenericPath(string $key, ?string $queryString = null): string
    {
        $path = '/app/'.$key;
        $query = '?protocol='.Defaults::PROTOCOL
            .'&client=php'
            .'&version='.Defaults::VERSION
            .($queryString ? '&'.$queryString : '');

        return $path.$query;
    }
}
